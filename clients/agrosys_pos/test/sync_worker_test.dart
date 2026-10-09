import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:agrosys_pos/core/models.dart';
import 'package:agrosys_pos/core/pos_api.dart';
import 'package:agrosys_pos/data/catalog_repository.dart';
import 'package:agrosys_pos/data/database.dart';
import 'package:agrosys_pos/features/sales/sale_domain.dart';
import 'package:agrosys_pos/features/sales/sales_repository.dart';
import 'package:agrosys_pos/features/sync/sync_worker.dart' as sync;
import 'package:drift/drift.dart' hide isNull, isNotNull;
import 'package:drift/native.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:sqlite3/sqlite3.dart' as sqlite;
import 'package:uuid/uuid.dart';

import 'support/fixtures.dart';

import 'support/sync_server.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  late Directory dir;
  late PosDatabase db;
  late CatalogRepository catalog;
  late SalesRepository sales;
  late Session session;
  late CentralApi api;
  late DateTime time;
  sync.SyncWorker worker({bool Function()? active}) => sync.SyncWorker(
    api,
    catalog,
    session,
    clock: () => time,
    random: () => 0.5,
    active: active,
  );
  Future<void> open() async {
    db = PosDatabase(
      NativeDatabase(File('${dir.path}/pos.sqlite')),
      session.contextId,
    );
    catalog = CatalogRepository(db, session);
    sales = SalesRepository(catalog, session);
  }

  Future<StoredReceipt> sale({bool credit = false}) async {
    final q = await sales.quote('20', [const CartLine('10', 100)]);
    return sales.complete(
      SaleRequest(
        saleId: const Uuid().v4(),
        customerId: '20',
        type: credit ? 'Credito' : 'Contado',
        lines: [const CartLine('10', 100)],
        initialCents: credit ? 1000 : 0,
        receivedCents: credit ? 1000 : 5000,
        expectedPriceFingerprint: q.priceFingerprint,
        acknowledgeStock: true,
      ),
    );
  }

  setUp(() async {
    dir = await Directory.systemTemp.createTemp('agrosys-sync-');
    session = fixtureSession(
      lease: await signedLease(
        fixtureSession(),
        permissions: ['catalog.read', 'sale.create', 'sale.credit'],
      ),
    );
    api = CentralApi();
    time = DateTime.now().toUtc();
    await open();
    await catalog.startDownload(incremental: false);
    await catalog.stage(await api.page(session, incremental: false));
    await catalog.commitDownload();
  });
  tearDown(() async {
    api.close();
    await db.close();
    await dir.delete(recursive: true);
  });

  test('ACK retains deltas until a snapshot receipt proves inclusion, including after reopen', () async {
    await sale(credit: true);
    api.failPull = true;
    await expectLater(worker().run(), throwsA(isA<ApiFailure>()));
    expect((await db.select(db.outbox).getSingle()).status, 'confirmed');
    expect((await catalog.products('')).single.estimatedQuantity, 199900);
    expect((await catalog.customers('')).single.balanceCents, 8000);
    expect(
      (await db.select(db.localEffects).get()).every((e) => !e.reflected),
      true,
    );
    await db.close();
    await open();
    api.failPull = false;
    await worker().run();
    expect(
      (await db.select(db.localEffects).get()).every((e) => e.reflected),
      true,
    );
    expect((await catalog.products('')).single.estimatedQuantity, 199900);
    expect((await catalog.customers('')).single.balanceCents, 8000);
    expect(api.calls, 1);
  });
  test(
    'pull receipt before ACK confirms the original sale without sending again',
    () async {
      await sale();
      final row = await db.select(db.outbox).getSingle();
      await api.push(session, [object(jsonDecode(row.payload))]);
      await catalog.startDownload(
        incremental: true,
        cursor: (await catalog.info()).cursor,
      );
      await catalog.stage(
        await api.page(session, incremental: true, knownIds: [row.operationId]),
      );
      await catalog.commitDownload();
      await worker().run();
      expect(api.calls, 1);
      expect((await db.select(db.outbox).getSingle()).status, 'confirmed');
      expect((await catalog.products('')).single.estimatedQuantity, 199900);
    },
  );
  test('mixed duplicate confirmed, duplicate conflict and temporary retry remain distinct', () async {
    final a = await sale(), b = await sale(), c = await sale();
    api.rejected[b.sale.operationId] = 'STOCK_CHANGED';
    api.retryOnce.add(c.sale.operationId);
    await worker().run();
    final rows = await (db.select(
      db.outbox,
    )..orderBy([(t) => OrderingTerm.asc(t.sequence)])).get();
    expect(rows.map((r) => r.status), ['confirmed', 'conflict', 'retry']);
    final original = rows.map((r) => r.payload).toList();
    await worker().run();
    expect(
      api.calls,
      1,
    ); // Backoff remains durable, automatic trigger cannot bypass it.
    await worker().run(manual: true);
    expect((await sales.findSale(c.sale.id))!.status, 'confirmed');
    expect((await sales.findSale(b.sale.id))!.status, 'conflict');
    expect((await sales.findSale(a.sale.id))!.status, 'confirmed');
    expect(
      (await (db.select(
        db.outbox,
      )..orderBy([(t) => OrderingTerm.asc(t.sequence)])).get()).map(
        (r) => r.payload,
      ),
      original,
    );
    expect(api.results.length, 3);
  });
  test('payload mismatch stays in review even if a central receipt has the same sale ID', () async {
    final saved = await sale();
    api.mismatches.add(saved.sale.operationId);
    await worker().run();
    await worker().run(manual: true);
    final row = await db.select(db.outbox).getSingle();
    expect(row.status, 'conflict');
    expect(row.errorCode, 'IDEMPOTENCY_PAYLOAD_MISMATCH');
    expect((await sales.findSale(saved.sale.id))!.serverFolio, isNull);
    expect(
      (await db.select(db.localEffects).get()).every((e) => !e.reflected),
      true,
    );
    expect(api.calls, 1);
  });
  test(
    'malformed ACK cannot invent a confirmation; query recovers same identity',
    () async {
      await sale();
      api.malformed = true;
      final before = await db.select(db.outbox).getSingle();
      await expectLater(worker().run(), throwsFormatException);
      expect((await db.select(db.outbox).getSingle()).status, 'retry');
      expect(
        (await db.select(db.localSales).getSingle()).status,
        'pending_sync',
      );
      api.malformed = false;
      await worker().run(manual: true);
      expect((await db.select(db.outbox).getSingle()).payload, before.payload);
      expect(api.calls, 1);
      expect(api.queries, 1);
    },
  );
  for (final status in [401, 402, 403]) {
    test(
      'HTTP $status blocks access durably, revalidation retries the same operation',
      () async {
        await sale();
        final before = await db.select(db.outbox).getSingle();
        api.denied = status;
        await expectLater(worker().run(), throwsA(isA<ApiFailure>()));
        expect(await catalog.isLocked(), true);
        expect((await db.select(db.outbox).getSingle()).status, 'blocked');
        await worker().run(manual: true);
        expect(api.calls, 1);
        api.denied = null;
        await catalog.markRevalidated();
        await worker().run(manual: true);
        expect((await db.select(db.outbox).getSingle()).status, 'confirmed');
        expect(
          (await db.select(db.outbox).getSingle()).payload,
          before.payload,
        );
      },
    );
  }
  test('one durable worker lease prevents overlapping runners and recovers expired sending', () async {
    await sale();
    api.gate = Completer();
    final first = worker().run();
    while (api.calls == 0) {
      await Future<void>.delayed(const Duration(milliseconds: 1));
    }
    await worker().run();
    expect(api.calls, 1);
    api.gate!.complete();
    await first;
    api.gate = null;
    await sale();
    await db
        .update(db.syncWorkers)
        .write(
          SyncWorkersCompanion(
            owner: const Value('crashed'),
            expiresAt: Value(time.millisecondsSinceEpoch - 1),
          ),
        );
    await (db.update(
      db.outbox,
    )..where((t) => t.status.equals('pending'))).write(
      const OutboxCompanion(
        status: Value('sending'),
        workerLease: Value('crashed'),
        attempts: Value(1),
      ),
    );
    await worker().run();
    expect(
      (await db.select(db.outbox).get()).every((r) => r.status == 'confirmed'),
      true,
    );
    expect(api.queries, 1);
    expect(api.results.length, 2);
  });
  test('lost context while network is in flight preserves rows and refuses stale ACK writes', () async {
    await sale();
    var active = true;
    api.gate = Completer();
    final run = worker(active: () => active).run();
    while (api.calls == 0) {
      await Future<void>.delayed(const Duration(milliseconds: 1));
    }
    active = false;
    api.gate!.complete();
    await expectLater(run, throwsA(isA<ApiFailure>()));
    expect((await db.select(db.outbox).getSingle()).status, 'sending');
    expect((await db.select(db.localSales).getSingle()).status, 'pending_sync');
    expect(await catalog.isLocked(), false);
    api.gate = null;
    await worker().run();
    expect((await db.select(db.outbox).getSingle()).status, 'confirmed');
    expect(api.calls, 1);
  });
  test('schema v3 migration preserves queued bytes, sending lease, sequence and effects', () async {
    await sale(credit: true);
    final before = await db.select(db.outbox).getSingle();
    await db
        .update(db.outbox)
        .write(
          const OutboxCompanion(
            status: Value('sending'),
            attempts: Value(2),
            workerLease: Value('crashed'),
          ),
        );
    await db.close();
    final raw = sqlite.sqlite3.open('${dir.path}/pos.sqlite');
    raw.execute('DROP TABLE sync_workers');
    raw.execute('ALTER TABLE outbox DROP COLUMN server_result');
    raw.execute('ALTER TABLE outbox DROP COLUMN error_code');
    raw.execute('DROP TABLE inventory_requests');
    raw.execute('ALTER TABLE local_products DROP COLUMN classification');
    raw.execute('PRAGMA user_version=3');
    raw.close();
    await open();
    final migrated = await db.select(db.outbox).getSingle();
    expect(migrated.payload, before.payload);
    expect(migrated.operationId, before.operationId);
    expect(migrated.status, 'sending');
    expect(migrated.attempts, 2);
    expect((await db.select(db.deviceSequence).getSingle()).nextSequence, 2);
    expect((await catalog.customers('')).single.balanceCents, 8000);
    await worker().run();
    expect(api.results.length, 1);
  });
  test('incomplete owned receipt snapshot is staged; expired pages restart without deleting pending sales', () async {
    await sale(credit: true);
    api.scriptedPages.addAll([
      snapshot(
        more: true,
        products: [product(quantity: '1999.00')],
        customers: [],
      ),
      ApiFailure(null, 'Page connection cut'),
    ]);
    await expectLater(worker().run(), throwsA(isA<ApiFailure>()));
    expect((await catalog.download())!.pageCount, 1);
    expect((await catalog.products('')).single.estimatedQuantity, 199900);
    final before = await db.select(db.outbox).getSingle();
    await db.close();
    await open();
    api.scriptedPages.add(ApiFailure(410, 'Expired page'));
    await worker().run();
    expect(await catalog.download(), isNull);
    expect((await db.select(db.outbox).getSingle()).payload, before.payload);
    expect(
      (await db.select(db.localEffects).get()).every((e) => e.reflected),
      true,
    );
    expect((await catalog.products('')).single.estimatedQuantity, 199900);
    expect((await catalog.customers('')).single.balanceCents, 8000);
    expect(api.results.length, 1);
  });
  test('invalid snapshot receipt rolls back catalog, confirmations, effects and cursor together', () async {
    await sale();
    final row = await db.select(db.outbox).getSingle();
    final cursor = (await catalog.info()).cursor;
    api.scriptedPages.add(
      snapshot(
        revision: '22222222-2222-4222-8222-222222222222',
        products: [product(quantity: '0.00')],
        receipts: [
          {
            'operationId': row.operationId,
            'originalStatus': 'confirmed',
            'includedInSnapshot': true,
            'result': {
              'operationId': row.operationId,
              'saleId': 'another-sale',
              'originalStatus': 'confirmed',
              'serverFolio': 'wrong',
            },
          },
        ],
      ),
    );
    await expectLater(worker().run(), throwsFormatException);
    expect((await catalog.info()).cursor, cursor);
    expect((await catalog.products('')).single.estimatedQuantity, 199900);
    expect(
      (await db.select(db.localEffects).get()).every((e) => !e.reflected),
      true,
    );
    expect(
      (await db.select(db.outbox).getSingle()).status,
      'confirmed',
    ); // Valid push ACK remains durable.
    await worker().run(full: true);
    expect(
      (await db.select(db.localEffects).get()).every((e) => e.reflected),
      true,
    );
  });
  test('separate database connections recover an expired owner and reject its stale response', () async {
    await sale();
    api.gate = Completer();
    final first = worker().run();
    while (api.calls == 0) {
      await Future<void>.delayed(const Duration(milliseconds: 1));
    }
    final secondDb = PosDatabase(
      NativeDatabase(File('${dir.path}/pos.sqlite')),
      session.contextId,
    );
    final secondCatalog = CatalogRepository(secondDb, session);
    try {
      await sync.SyncWorker(
        api,
        secondCatalog,
        session,
        clock: () => time,
      ).run();
      expect(api.calls, 1);
      time = time.add(const Duration(minutes: 3));
      api.gate!.complete();
      await expectLater(first, throwsStateError);
      api.gate = null;
      await sync.SyncWorker(
        api,
        secondCatalog,
        session,
        clock: () => time,
      ).run();
      expect(api.results.length, 1);
      expect((await db.select(db.outbox).getSingle()).status, 'confirmed');
      expect(api.calls, 1);
    } finally {
      await secondDb.close();
    }
  });
  test('1000 cash/credit operations with 100 interrupted responses reconcile without duplication', () async {
    for (var i = 0; i < 1000; i++) {
      await sale(credit: i.isOdd);
    }
    final original = {
      for (final row in await db.select(db.outbox).get())
        row.operationId: row.payload,
    };
    expect((await catalog.products('')).single.estimatedQuantity, 100000);
    expect((await catalog.customers('')).single.balanceCents, 2004000);
    api.cutBudget = 100;
    for (var i = 0; i < 100; i++) {
      await expectLater(worker().run(manual: true), throwsA(isA<ApiFailure>()));
      if (i == 49) {
        await db.close();
        await open();
      }
    }
    await worker().run(manual: true);
    expect(api.cuts, 100);
    expect(api.results.length, 1000);
    expect(api.stock, 100000);
    expect(api.debt, 5004000);
    expect(api.payments, 3000000);
    final rows = await db.select(db.outbox).get();
    expect(rows.length, 1000);
    expect(rows.every((r) => r.status == 'confirmed'), true);
    expect({for (final r in rows) r.operationId: r.payload}, original);
    expect(
      (await db.select(db.localEffects).get()).every((e) => e.reflected),
      true,
    );
    expect((await catalog.products('')).single.estimatedQuantity, 100000);
    expect((await catalog.customers('')).single.balanceCents, 2004000);
    expect(
      api.sent.every(
        (s) =>
            s.length <= 50 &&
            s.join(',') == (List<int>.from(s)..sort()).join(','),
      ),
      true,
    );
  }, timeout: const Timeout(Duration(minutes: 3)));
  test('two offline tills merge central payments and preserve conflicts for price/product/customer changes', () async {
    final otherSession = fixtureSession(
      device: 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
      lease: await signedLease(
        fixtureSession(device: 'cccccccc-cccc-4ccc-8ccc-cccccccccccc'),
        permissions: ['catalog.read', 'sale.create', 'sale.credit'],
      ),
    );
    final otherDb = PosDatabase(
      NativeDatabase(File('${dir.path}/other.sqlite')),
      otherSession.contextId,
    );
    final otherCatalog = CatalogRepository(otherDb, otherSession);
    try {
      await otherCatalog.startDownload(incremental: false);
      await otherCatalog.stage(
        await api.page(otherSession, incremental: false),
      );
      await otherCatalog.commitDownload();
      await sale(credit: true);
      final otherSales = SalesRepository(otherCatalog, otherSession);
      final q = await otherSales.quote('20', [const CartLine('10', 100)]);
      await otherSales.complete(
        SaleRequest(
          saleId: const Uuid().v4(),
          customerId: '20',
          type: 'Credito',
          lines: [const CartLine('10', 100)],
          initialCents: 1000,
          receivedCents: 1000,
          expectedPriceFingerprint: q.priceFingerprint,
        ),
      );
      api.externalPayment = 3000;
      await worker().run();
      await sync.SyncWorker(api, otherCatalog, otherSession).run();
      await worker().run();
      expect(api.results.length, 2);
      expect((await catalog.customers('')).single.balanceCents, 9000);
      expect(
        (await otherCatalog.products('')).single.estimatedQuantity,
        199800,
      );
      for (final change in ['price', 'product', 'customer']) {
        await sale();
        if (change == 'price') api.price = 6000;
        if (change == 'product') api.inactiveProduct = true;
        if (change == 'customer') api.inactiveCustomer = true;
        await worker().run();
        expect((await sales.history()).first.status, 'conflict');
        api.price = 5000;
        api.inactiveProduct = false;
        api.inactiveCustomer = false;
        await worker().run();
      }
      final conflicts = (await db.select(db.outbox).get())
          .where((r) => r.status == 'conflict')
          .toList();
      expect(conflicts.length, 3);
      expect(conflicts.map((r) => r.errorCode), [
        'PRICE_CHANGED',
        'PRODUCT_INACTIVE',
        'CUSTOMER_INACTIVE',
      ]);
      expect(api.stock, 199800);
      expect(
        (await db.select(db.localEffects).get())
            .where((e) => !e.reflected)
            .length,
        6,
      );
    } finally {
      await otherDb.close();
    }
  });
}
