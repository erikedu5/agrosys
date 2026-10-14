import 'dart:io';

import 'package:agrosys_pos/core/pos_api.dart';
import 'package:agrosys_pos/data/database.dart';
import 'package:agrosys_pos/data/catalog_repository.dart';
import 'package:agrosys_pos/data/catalog_sync.dart';
import 'package:drift/drift.dart' hide isNull, isNotNull;
import 'package:drift/native.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:sqlite3/sqlite3.dart' as sqlite;

import 'support/fixtures.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  late Directory directory;
  late PosDatabase db;
  late CatalogRepository repo;
  setUp(() async {
    directory = await Directory.systemTemp.createTemp('agrosys-pos-test-');
    db = PosDatabase(
      NativeDatabase(File('${directory.path}/context.sqlite')),
      fixtureSession().contextId,
    );
    repo = CatalogRepository(db, fixtureSession());
    await repo.startDownload(incremental: false);
    await repo.stage(snapshot());
    await repo.commitDownload();
  });
  tearDown(() async {
    await db.close();
    await directory.delete(recursive: true);
  });
  test('category filtering combines search and survives reopening', () async {
    await repo.startDownload(incremental: false);
    await repo.stage(
      snapshot(
        products: [
          {...product(), 'classification': 'Semillas'},
          {
            ...product(id: '11', name: 'Abono'),
            'classification': 'Fertilizantes',
          },
          product(id: '12', name: 'Sin categoría'),
          {...product(id: '13', active: false), 'classification': 'Oculta'},
        ],
      ),
    );
    await repo.commitDownload();
    await db.close();
    db = PosDatabase(
      NativeDatabase(File('${directory.path}/context.sqlite')),
      fixtureSession().contextId,
    );
    repo = CatalogRepository(db, fixtureSession());
    expect(await repo.productCategories(), ['Fertilizantes', 'Semillas']);
    expect(
      (await repo.products('maiz', classification: 'Semillas')).single.id,
      '10',
    );
    expect(await repo.products('abono', classification: 'Semillas'), isEmpty);
    expect((await repo.products('')).length, 3);
    expect((await repo.productById('10'))!.classification, 'Semillas');
  });
  test('AC-10 corrected clock requires verified revalidation before offline access', () async {
    final now = DateTime.now().toUtc();
    expect(await repo.observeClock(now.add(const Duration(hours: 2))), isTrue);
    expect(await repo.observeClock(now), isFalse);
    await repo.setLocked(true);
    expect(await repo.isLocked(), isTrue);
    await repo.markRevalidated();
    expect(await repo.isLocked(), isFalse);
    expect(await repo.observeClock(now), isTrue);
  });
  test(
    'AC-18 failed SQLite write preserves live catalog and durable download',
    () async {
      final previous = await repo.info();
      await repo.startDownload(incremental: false);
      await repo.stage(snapshot(products: [product(quantity: '3.00')]));
      await db.customStatement('PRAGMA query_only = ON');
      await expectLater(
        repo.commitDownload(),
        throwsA(isA<sqlite.SqliteException>()),
      );
      expect((await repo.products('')).single.estimatedQuantity, 1000);
      expect((await repo.info()).cursor, previous.cursor);
      await db.customStatement('PRAGMA query_only = OFF');
      await db.close();
      db = PosDatabase(
        NativeDatabase(File('${directory.path}/context.sqlite')),
        fixtureSession().contextId,
      );
      repo = CatalogRepository(db, fixtureSession());
      await repo.commitDownload();
      expect((await repo.products('')).single.estimatedQuantity, 300);
    },
  );
  test('AC-02/03 local catalog survives reopen; accented name, id and barcode search', () async {
    await db.close();
    db = PosDatabase(
      NativeDatabase(File('${directory.path}/context.sqlite')),
      fixtureSession().contextId,
    );
    repo = CatalogRepository(db, fixtureSession());
    expect((await repo.products('MAIZ')).single.id, '10');
    expect((await repo.products('10')).single.id, '10');
    expect((await repo.products('7501234567890')).single.priceCents, 5000);
    expect((await repo.customers('jose')).single.balanceCents, 4000);
    expect(await repo.products('%'), isEmpty);
    expect((await repo.info()).revision, isNotNull);
  });
  test('AC-11/23 staged pages and ACK preserve estimates; complete receipts are atomic', () async {
    const operation = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';
    await db
        .into(db.localEffects)
        .insert(
          LocalEffectsCompanion.insert(
            operationId: operation,
            entityId: '10',
            kind: 'stock',
            stockDelta: const Value(-200),
            originalStatus: const Value('confirmed'),
          ),
        );
    await db
        .into(db.localEffects)
        .insert(
          LocalEffectsCompanion.insert(
            operationId: operation,
            entityId: '20',
            kind: 'account',
            debtDelta: const Value(10000),
            paymentDelta: const Value(3000),
            originalStatus: const Value('confirmed'),
          ),
        );
    expect((await repo.products('')).single.estimatedQuantity, 800);
    expect((await repo.customers('')).single.balanceCents, 11000);
    await repo.startDownload(incremental: false);
    const revision = '22222222-2222-4222-8222-222222222222';
    await repo.stage(
      snapshot(
        revision: revision,
        more: true,
        products: [product(quantity: '8.00')],
        customers: [],
      ),
    );
    expect((await repo.products('')).single.estimatedQuantity, 800);
    expect((await repo.customers('')).single.balanceCents, 11000);
    await db.close();
    db = PosDatabase(
      NativeDatabase(File('${directory.path}/context.sqlite')),
      fixtureSession().contextId,
    );
    repo = CatalogRepository(db, fixtureSession());
    final api = FixtureApi(
      pages: [
        snapshot(
          revision: revision,
          products: [],
          customers: [customer(balance: '110.00')],
          receipts: [receipt(operation)],
        ),
      ],
    );
    await CatalogSync(api, repo).synchronize();
    expect(api.bodies.single['page_token'], 'page-two');
    expect((await repo.products('')).single.estimatedQuantity, 800);
    expect((await repo.customers('')).single.balanceCents, 11000);
    expect(await repo.knownOperationIds(), isEmpty);
    expect((await repo.info()).revision, revision);
    // Replaying the same proof does not subtract or charge again.
    await repo.startDownload(
      incremental: true,
      cursor: (await repo.info()).cursor,
    );
    await repo.stage(
      snapshot(
        revision: revision,
        products: [],
        customers: [],
        receipts: [receipt(operation)],
      ),
    );
    await repo.commitDownload();
    expect((await repo.customers('')).single.balanceCents, 11000);
  });
  test(
    'AC-13 malformed final receipt rolls back every live catalog write',
    () async {
      await repo.startDownload(incremental: false);
      await repo.stage(
        snapshot(
          products: [product(name: 'Incorrect replacement')],
          receipts: [
            {
              'operationId': 'bad',
              'includedInSnapshot': true,
              'originalStatus': 'conflict',
              'result': {'originalStatus': 'conflict'},
            },
          ],
        ),
      );
      await expectLater(repo.commitDownload(), throwsFormatException);
      expect((await repo.products('')).single.name, 'Semillas de maíz');
      expect((await repo.download())!.complete, isTrue);
    },
  );
  test(
    'AC-13 mixed revisions are rejected without advancing continuation',
    () async {
      await repo.startDownload(incremental: false);
      await repo.stage(snapshot(more: true));
      await expectLater(
        repo.stage(snapshot(revision: 'other-revision', products: [])),
        throwsFormatException,
      );
      expect((await repo.download())!.pageCount, 1);
      expect((await repo.products('')).single.estimatedQuantity, 1000);
    },
  );
  test(
    'AC-13/24 inactive products and customers disappear while effects stay',
    () async {
      await db
          .into(db.localEffects)
          .insert(
            LocalEffectsCompanion.insert(
              operationId: 'pending',
              entityId: '10',
              kind: 'stock',
              stockDelta: const Value(-100),
            ),
          );
      await repo.startDownload(
        incremental: true,
        cursor: (await repo.info()).cursor,
      );
      await repo.stage(
        snapshot(
          products: [product(active: false)],
          customers: [customer(active: false)],
        ),
      );
      await repo.commitDownload();
      expect(await repo.products(''), isEmpty);
      expect(await repo.customers(''), isEmpty);
      expect(await repo.knownOperationIds(), ['pending']);
    },
  );
  test('AC-13 expired cursor restarts bootstrap preserving effects', () async {
    await db
        .into(db.localEffects)
        .insert(
          LocalEffectsCompanion.insert(
            operationId: 'pending',
            entityId: '10',
            kind: 'stock',
            stockDelta: const Value(-100),
          ),
        );
    final api = FixtureApi(pages: [ApiFailure(410, 'Expired'), snapshot()]);
    await CatalogSync(api, repo).synchronize();
    expect(api.calls, ['sync/pull', 'bootstrap']);
    expect(api.bodies.last.containsKey('cursor'), isFalse);
    expect((await repo.products('')).single.estimatedQuantity, 900);
  });
  test('AC-16 v1 migration preserves catalog, context and unknown pending payloads', () async {
    await db.close();
    final file = '${directory.path}/context.sqlite';
    final legacy = sqlite.sqlite3.open(file);
    for (final table in [
      'sync_workers',
      'receipt_attempts',
      'outbox',
      'local_payments',
      'local_sale_items',
      'local_sales',
      'device_sequence',
    ]) {
      legacy.execute('DROP TABLE $table');
    }
    legacy.execute('DROP TABLE downloads');
    legacy.execute('DROP TABLE download_pages');
    legacy.execute('DROP TABLE local_effects');
    legacy.execute(
      'CREATE TABLE pending_probe(operation_id TEXT PRIMARY KEY,payload TEXT)',
    );
    legacy.execute(
      "INSERT INTO pending_probe VALUES ('stable-id','immutable-payload')",
    );
    legacy.execute('DROP TABLE inventory_requests');
    legacy.execute('ALTER TABLE local_products DROP COLUMN classification');
    legacy.execute('PRAGMA user_version=1');
    legacy.close();
    db = PosDatabase(NativeDatabase(File(file)), fixtureSession().contextId);
    repo = CatalogRepository(db, fixtureSession());
    expect((await repo.products('')).single.id, '10');
    expect(
      (await db.customSelect('SELECT payload FROM pending_probe').getSingle())
          .read<String>('payload'),
      'immutable-payload',
    );
    expect(await repo.download(), isNull);
  });
  test(
    'AC-14 database ownership mismatch blocks access without resetting data',
    () async {
      final other = PosDatabase(
        NativeDatabase(File('${directory.path}/context.sqlite')),
        fixtureSession(user: 'other').contextId,
      );
      try {
        await expectLater(
          other.select(other.syncMetadata).get(),
          throwsStateError,
        );
      } finally {
        await other.close();
      }
      expect((await repo.products('')).single.id, '10');
    },
  );
}
