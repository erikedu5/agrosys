import 'dart:convert';
import 'dart:io';

import 'package:agrosys_pos/core/models.dart';
import 'package:agrosys_pos/data/catalog_repository.dart';
import 'package:agrosys_pos/data/database.dart';
import 'package:agrosys_pos/features/receipts/receipt_service.dart';
import 'package:agrosys_pos/features/sales/sale_domain.dart';
import 'package:agrosys_pos/features/sales/sales_repository.dart';
import 'package:drift/drift.dart' hide isNull, isNotNull;
import 'package:drift/native.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:sqlite3/sqlite3.dart' as sqlite;
import 'package:uuid/uuid.dart';

import 'support/fixtures.dart';

const salePermissions = [
  'catalog.read',
  'sale.create',
  'sale.credit',
  'sale.print_local_ticket',
];
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  late Directory directory;
  late PosDatabase db;
  late CatalogRepository catalog;
  late SalesRepository sales;
  late Session session;
  Future<void> open() async {
    db = PosDatabase(
      NativeDatabase(File('${directory.path}/pos.sqlite')),
      session.contextId,
    );
    catalog = CatalogRepository(db, session);
    sales = SalesRepository(catalog, session);
  }

  Future<SaleRequest> request({
    String type = 'Contado',
    int initial = 0,
    int received = 15000,
    int quantity = 200,
    String? id,
    bool ack = false,
  }) async {
    final quote = await sales.quote('20', [CartLine('10', quantity)]);
    return SaleRequest(
      saleId: id ?? const Uuid().v4(),
      customerId: '20',
      type: type,
      lines: [CartLine('10', quantity)],
      initialCents: initial,
      receivedCents: received,
      expectedPriceFingerprint: quote.priceFingerprint,
      acknowledgeStock: ack,
    );
  }

  Future<void> empty() async {
    expect(await db.select(db.localSales).get(), isEmpty);
    expect(await db.select(db.localSaleItems).get(), isEmpty);
    expect(await db.select(db.localPayments).get(), isEmpty);
    expect(await db.select(db.outbox).get(), isEmpty);
    expect(await db.select(db.localEffects).get(), isEmpty);
    expect((await db.select(db.deviceSequence).getSingle()).nextSequence, 1);
  }

  setUp(() async {
    directory = await Directory.systemTemp.createTemp('agrosys-sales-');
    session = fixtureSession(
      lease: await signedLease(fixtureSession(), permissions: salePermissions),
    );
    await open();
    await catalog.startDownload(incremental: false);
    await catalog.stage(
      snapshot(
        customers: [
          {...customer(), 'discountPercentage': '0.00'},
        ],
      ),
    );
    await catalog.commitDownload();
  });
  tearDown(() async {
    await db.close();
    await directory.delete(recursive: true);
  });
  test('AC-04/15 cash sale, payment, outbox and estimates survive reopen and double completion', () async {
    final input = await request();
    final results = await Future.wait([
      sales.complete(input),
      sales.complete(input),
    ]);
    expect(results[0].sale.operationId, results[1].sale.operationId);
    expect((await db.select(db.localSales).get()).length, 1);
    expect((await db.select(db.outbox).get()).length, 1);
    expect(
      results[0].sale.localFolio,
      contains(session.deviceId.replaceAll('-', '').toUpperCase()),
    );
    expect(results[0].sale.appliedCents, 10000);
    expect(results[0].sale.changeCents, 5000);
    expect((await catalog.products('')).single.estimatedQuantity, 800);
    expect((await catalog.customers('')).single.balanceCents, 4000);
    final outbox = await db.select(db.outbox).getSingle();
    final operation = object(jsonDecode(outbox.payload));
    expect(operation['sequence'], 1);
    expect(object(operation['payload'])['payments'], [
      {'method': 'cash', 'amount': '100.00'},
    ]);
    await db.close();
    await open();
    final retry = await sales.complete(input);
    expect(retry.sale.operationId, results[0].sale.operationId);
    expect((await db.select(db.outbox).getSingle()).payload, outbox.payload);
    expect((await db.select(db.deviceSequence).getSingle()).nextSequence, 2);
    expect((await catalog.products('')).single.estimatedQuantity, 800);
    expect((await sales.history()).single.status, 'pending_sync');
  });
  for (final initial in [0, 3000, 10000]) {
    test(
      'AC-19/20/22 credit initial $initial persists sale balance and exact account delta',
      () async {
        final input = await request(
          type: 'Credito',
          initial: initial,
          received: initial,
        );
        final first = await sales.complete(input);
        expect(first.sale.balanceCents, 10000 - initial);
        expect(first.sale.paid, initial == 10000);
        expect(
          (await catalog.customers('')).single.balanceCents,
          14000 - initial,
        );
        await db.close();
        await open();
        final restored = await sales.receipt(first.sale.id);
        expect(restored.payment.appliedCents, initial);
        expect(
          (await catalog.customers('')).single.balanceCents,
          14000 - initial,
        );
        expect((await db.select(db.outbox).get()).length, 1);
      },
    );
  }
  test('AC-05/18 failure late in closure rolls back everything including sequence and allows same-ID retry', () async {
    final input = await request(type: 'Credito', initial: 3000, received: 5000);
    await db.customStatement(
      "CREATE TRIGGER fail_outbox BEFORE INSERT ON outbox BEGIN SELECT RAISE(ABORT, 'simulated write failure'); END",
    );
    await expectLater(
      sales.complete(input),
      throwsA(isA<sqlite.SqliteException>()),
    );
    await empty();
    expect((await catalog.products('')).single.estimatedQuantity, 1000);
    expect((await catalog.customers('')).single.balanceCents, 4000);
    await db.customStatement('DROP TRIGGER fail_outbox');
    expect((await sales.complete(input)).sale.sequence, 1);
  });
  test('AC-18 actual SQLITE_FULL rolls back a closure and preserves the same retry', () async {
    await db
        .update(db.localProducts)
        .write(LocalProductsCompanion(name: Value("Producto ${'x' * 8192}")));
    final input = await request();
    final count = (await db.customSelect('PRAGMA page_count').getSingle())
        .read<int>('page_count');
    await db.customStatement('PRAGMA max_page_count = $count');
    await expectLater(
      sales.complete(input),
      throwsA(
        anyOf(
          isA<sqlite.SqliteException>().having(
            (e) => e.resultCode,
            'SQLITE_FULL',
            sqlite.SqlError.SQLITE_FULL,
          ),
          // SQLite can auto-rollback SQLITE_FULL; Drift then wraps the redundant rollback.
          isA<CouldNotRollBackException>().having(
            (e) => e.cause,
            'original SQLite failure',
            isA<sqlite.SqliteException>().having(
              (e) => e.resultCode,
              'SQLITE_FULL',
              sqlite.SqlError.SQLITE_FULL,
            ),
          ),
        ),
      ),
    );
    await empty();
    expect((await catalog.products('')).single.estimatedQuantity, 1000);
    expect((await catalog.customers('')).single.balanceCents, 4000);
    await db.customStatement('PRAGMA max_page_count = 1073741823');
    expect((await sales.complete(input)).sale.sequence, 1);
  });
  test(
    'AC-10/17 stock warning, permissions, expiry and local lock gate closure',
    () async {
      await expectLater(
        sales.complete(await request(quantity: 1100, received: 60000)),
        throwsA(isA<SaleInputException>()),
      );
      await empty();
      final noCredit = SalesRepository(
        catalog,
        fixtureSession(
          lease: await signedLease(
            fixtureSession(),
            permissions: ['catalog.read', 'sale.create'],
          ),
        ),
      );
      await expectLater(
        noCredit.complete(await request(type: 'Credito')),
        throwsA(isA<SaleInputException>()),
      );
      final expired = SalesRepository(
        catalog,
        fixtureSession(
          lease: await signedLease(
            fixtureSession(),
            permissions: salePermissions,
            issuedAt: DateTime.now().subtract(const Duration(days: 8)),
            expiresAt: DateTime.now().subtract(const Duration(days: 1)),
          ),
        ),
      );
      await expectLater(
        expired.complete(await request()),
        throwsA(isA<SaleInputException>()),
      );
      await catalog.setLocked(true);
      await expectLater(
        sales.complete(await request()),
        throwsA(isA<SaleInputException>()),
      );
      await catalog.setLocked(false);
      await empty();
      await sales.complete(
        await request(quantity: 1100, received: 60000, ack: true),
      );
      expect((await catalog.products('')).single.estimatedQuantity, -100);
    },
  );
  test('AC-21 catalog change requires a new review and inactive customer cannot close', () async {
    final input = await request();
    await (db.update(db.localProducts)..where((t) => t.serverId.equals('10')))
        .write(const LocalProductsCompanion(priceCents: Value(6000)));
    await expectLater(
      sales.complete(input),
      throwsA(isA<SaleInputException>()),
    );
    await empty();
    await db
        .update(db.localCustomers)
        .write(const LocalCustomersCompanion(active: Value(false)));
    await expectLater(
      sales.complete(input),
      throwsA(isA<SaleInputException>()),
    );
    await empty();
  });
  test('AC-15 ticket failure, cancellation and reprint preserve historical prices and cannot repeat sale', () async {
    final first = await sales.complete(await request());
    final original = receiptText(first);
    final outbox = await db.select(db.outbox).getSingle();
    await db
        .update(db.localProducts)
        .write(
          const LocalProductsCompanion(
            name: Value('Renamed'),
            priceCents: Value(9000),
          ),
        );
    final failing = ReceiptService(
      sales,
      printer: (_) async => throw StateError('Printer offline'),
    );
    expect(await failing.print(first.sale.id), 'failed');
    expect(
      await ReceiptService(
        sales,
        printer: (_) async => false,
      ).print(first.sale.id),
      'cancelled',
    );
    expect(
      await ReceiptService(
        sales,
        printer: (_) async => true,
      ).print(first.sale.id),
      'submitted',
    );
    expect(receiptText(await sales.receipt(first.sale.id)), original);
    expect((await db.select(db.localSales).get()).length, 1);
    expect((await db.select(db.outbox).getSingle()).payload, outbox.payload);
    expect((await db.select(db.receiptAttempts).get()).map((a) => a.status), [
      'failed',
      'cancelled',
      'submitted',
    ]);
    final pdf = await receiptPdf(first);
    expect(String.fromCharCodes(pdf.take(5)), '%PDF-');
  });
  test('AC-15 same closure ID with different amounts cannot overwrite completed sale', () async {
    final input = await request();
    final first = await sales.complete(input);
    final changed = await request(id: input.saleId, received: 20000);
    await expectLater(
      sales.complete(changed),
      throwsA(isA<SaleInputException>()),
    );
    expect((await sales.receipt(first.sale.id)).sale.receivedCents, 15000);
    expect((await db.select(db.localSales).get()).length, 1);
  });
  test('AC-16 migration v2 to v3 preserves checkpoint, effects and unknown pending payload', () async {
    await db
        .into(db.localEffects)
        .insert(
          LocalEffectsCompanion.insert(
            operationId: 'legacy',
            entityId: '10',
            kind: 'stock',
            stockDelta: const Value(-100),
          ),
        );
    await db.customStatement(
      'CREATE TABLE pending_probe (payload TEXT NOT NULL)',
    );
    await db.customStatement("INSERT INTO pending_probe VALUES ('immutable')");
    final cursor = (await catalog.info()).cursor;
    await db.close();
    final raw = sqlite.sqlite3.open('${directory.path}/pos.sqlite');
    for (final table in [
      'sync_workers',
      'receipt_attempts',
      'outbox',
      'local_payments',
      'local_sale_items',
      'local_sales',
      'device_sequence',
    ]) {
      raw.execute('DROP TABLE $table');
    }
    raw.execute('PRAGMA user_version=2');
    raw.close();
    await open();
    expect((await catalog.info()).cursor, cursor);
    expect((await catalog.products('')).single.estimatedQuantity, 900);
    expect(
      (await db.customSelect('SELECT payload FROM pending_probe').getSingle())
          .read<String>('payload'),
      'immutable',
    );
    expect(await db.select(db.outbox).get(), isEmpty);
  });
  test(
    'AC-15 completed sales cannot be deleted and queued bytes cannot change',
    () async {
      final first = await sales.complete(await request());
      await expectLater(
        (db.delete(
          db.localSales,
        )..where((t) => t.id.equals(first.sale.id))).go(),
        throwsA(isA<sqlite.SqliteException>()),
      );
      await expectLater(
        db.update(db.outbox).write(const OutboxCompanion(payload: Value('{}'))),
        throwsA(isA<sqlite.SqliteException>()),
      );
      expect((await sales.history()).length, 1);
      expect((await db.select(db.outbox).get()).length, 1);
    },
  );
  test('AC-15 multi-page ticket with 200 long product names builds offline', () async {
    final original = await sales.complete(await request());
    final longReceipt = StoredReceipt(original.sale, [
      for (var i = 0; i < 200; i++)
        original.items.single.copyWith(
          id: '$i',
          productName:
              'Artículo $i con nombre largo para verificar la paginación y conservar información histórica',
        ),
    ], original.payment);
    final pdf = await receiptPdf(longReceipt);
    expect(String.fromCharCodes(pdf.take(5)), '%PDF-');
    expect(pdf.length, greaterThan(10000));
    final thermalPdf = await receiptPdf(longReceipt, format: thermal80);
    expect(String.fromCharCodes(thermalPdf.take(5)), '%PDF-');
    expect(thermalPdf.length, greaterThan(10000));
  });
  test(
    'AC-14 wrong database context cannot submit or print previous user sales',
    () async {
      final input = await request();
      await sales.complete(input);
      final otherSession = fixtureSession(
        user: 'other',
        lease: await signedLease(
          fixtureSession(user: 'other'),
          permissions: salePermissions,
        ),
      );
      final other = SalesRepository(catalog, otherSession);
      await expectLater(
        other.complete(input),
        throwsA(isA<SaleInputException>()),
      );
      await expectLater(other.receipt(input.saleId), throwsStateError);
      expect((await db.select(db.localSales).get()).length, 1);
    },
  );
}
