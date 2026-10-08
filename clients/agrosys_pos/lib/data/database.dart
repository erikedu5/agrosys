import 'dart:io';

import 'package:drift/drift.dart';
import 'package:drift/native.dart';
import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';
import 'package:sqlite3/sqlite3.dart' as sqlite;
part 'database.g.dart';

class LocalProducts extends Table {
  TextColumn get serverId => text()();
  TextColumn get name => text()();
  TextColumn get searchName => text()();
  TextColumn get barcode => text()();
  TextColumn get size => text()();
  IntColumn get priceCents => integer()();
  BoolColumn get active => boolean()();
  TextColumn get revision => text()();
  @override
  Set<Column> get primaryKey => {serverId};
}

class LocalCustomers extends Table {
  TextColumn get serverId => text()();
  TextColumn get name => text()();
  TextColumn get searchName => text()();
  IntColumn get discountBasisPoints => integer()();
  BoolColumn get active => boolean()();
  TextColumn get revision => text()();
  @override
  Set<Column> get primaryKey => {serverId};
}

class StockSnapshots extends Table {
  TextColumn get productId => text()();
  IntColumn get quantityUnits => integer()();
  TextColumn get revision => text()();
  @override
  Set<Column> get primaryKey => {productId};
}

class AccountSnapshots extends Table {
  TextColumn get customerId => text()();
  IntColumn get debtCents => integer()();
  IntColumn get paymentCents => integer()();
  IntColumn get balanceCents => integer()();
  TextColumn get revision => text()();
  @override
  Set<Column> get primaryKey => {customerId};
}

class SyncMetadata extends Table {
  IntColumn get id => integer()();
  TextColumn get contextId => text()();
  TextColumn get cursor => text().nullable()();
  TextColumn get revision => text().nullable()();
  TextColumn get syncedAt => text().nullable()();
  TextColumn get lastObservedAt => text().nullable()();
  TextColumn get defaultCustomerId => text().nullable()();
  BoolColumn get authLocked => boolean().withDefault(const Constant(false))();
  @override
  Set<Column> get primaryKey => {id};
}

// Introduced in schema v2. A failed download never replaces the live catalog.
class Downloads extends Table {
  IntColumn get id => integer()();
  BoolColumn get incremental => boolean()();
  TextColumn get baseCursor => text().nullable()();
  TextColumn get revision => text().nullable()();
  TextColumn get nextPageToken => text().nullable()();
  IntColumn get pageCount => integer().withDefault(const Constant(0))();
  BoolColumn get complete => boolean().withDefault(const Constant(false))();
  @override
  Set<Column> get primaryKey => {id};
}

class DownloadPages extends Table {
  IntColumn get ordinal => integer()();
  TextColumn get payload => text()();
  @override
  Set<Column> get primaryKey => {ordinal};
}

// Phase 3 writes these effects in the same transaction as sales/outbox.
// An ACK alone NEVER changes reflected; only a complete snapshot receipt does.
class LocalEffects extends Table {
  TextColumn get operationId => text()();
  TextColumn get entityId => text()();
  TextColumn get kind => text()(); // stock or account
  IntColumn get stockDelta => integer().withDefault(const Constant(0))();
  IntColumn get debtDelta => integer().withDefault(const Constant(0))();
  IntColumn get paymentDelta => integer().withDefault(const Constant(0))();
  BoolColumn get reflected => boolean().withDefault(const Constant(false))();
  TextColumn get originalStatus => text().nullable()();
  @override
  Set<Column> get primaryKey => {operationId, entityId, kind};
}

// Schema v3: every completed sale, effect, payment and queued operation commits together.
class LocalSales extends Table {
  TextColumn get id => text()();
  TextColumn get operationId => text().unique()();
  TextColumn get contextId => text()();
  IntColumn get sequence => integer().unique()();
  TextColumn get localFolio => text().unique()();
  TextColumn get serverFolio => text().nullable()();
  TextColumn get customerId => text()();
  TextColumn get customerName => text()();
  TextColumn get branchName => text()();
  TextColumn get operatorName => text()();
  TextColumn get saleType => text()();
  IntColumn get discountBasisPoints => integer()();
  IntColumn get totalCents => integer()();
  IntColumn get appliedCents => integer()();
  IntColumn get receivedCents => integer()();
  IntColumn get changeCents => integer()();
  IntColumn get balanceCents => integer()();
  BoolColumn get paid => boolean()();
  TextColumn get status => text().withDefault(const Constant('pending_sync'))();
  TextColumn get occurredAt => text()();
  TextColumn get offlineLeaseId => text()();
  TextColumn get catalogRevision => text()();
  TextColumn get requestHash => text()();
  @override
  Set<Column> get primaryKey => {id};
}

class LocalSaleItems extends Table {
  TextColumn get id => text()();
  TextColumn get saleId => text().references(LocalSales, #id)();
  TextColumn get productId => text()();
  TextColumn get productName => text()();
  TextColumn get barcode => text()();
  IntColumn get quantityUnits => integer()();
  IntColumn get originalPriceCents => integer()();
  IntColumn get unitPriceCents => integer()();
  IntColumn get totalCents => integer()();
  IntColumn get ordinal => integer()();
  @override
  Set<Column> get primaryKey => {id};
}

class LocalPayments extends Table {
  TextColumn get id => text()();
  TextColumn get saleId => text().unique().references(LocalSales, #id)();
  TextColumn get method => text().withDefault(const Constant('cash'))();
  IntColumn get appliedCents => integer()();
  IntColumn get receivedCents => integer()();
  IntColumn get changeCents => integer()();
  @override
  Set<Column> get primaryKey => {id};
}

class Outbox extends Table {
  TextColumn get operationId => text()();
  TextColumn get saleId => text().unique().references(LocalSales, #id)();
  TextColumn get contextId => text()();
  IntColumn get sequence => integer().unique()();
  TextColumn get payload => text()();
  TextColumn get payloadHash => text()();
  TextColumn get status => text().withDefault(const Constant('pending'))();
  IntColumn get attempts => integer().withDefault(const Constant(0))();
  TextColumn get nextAttemptAt => text().nullable()();
  TextColumn get workerLease => text().nullable()();
  TextColumn get serverResult => text().nullable()();
  TextColumn get errorCode => text().nullable()();
  TextColumn get error => text().nullable()();
  @override
  Set<Column> get primaryKey => {operationId};
}

class DeviceSequence extends Table {
  TextColumn get contextId => text()();
  IntColumn get nextSequence => integer().withDefault(const Constant(1))();
  @override
  Set<Column> get primaryKey => {contextId};
}

class ReceiptAttempts extends Table {
  TextColumn get id => text()();
  TextColumn get saleId => text().references(LocalSales, #id)();
  TextColumn get attemptedAt => text()();
  TextColumn get status =>
      text()(); // requested, submitted, cancelled or failed
  TextColumn get message => text().nullable()();
  @override
  Set<Column> get primaryKey => {id};
}

class SyncWorkers extends Table {
  TextColumn get contextId => text()();
  TextColumn get owner => text().nullable()();
  IntColumn get expiresAt => integer().withDefault(const Constant(0))();
  @override
  Set<Column> get primaryKey => {contextId};
}

@DriftDatabase(
  tables: [
    LocalProducts,
    LocalCustomers,
    StockSnapshots,
    AccountSnapshots,
    SyncMetadata,
    Downloads,
    DownloadPages,
    LocalEffects,
    LocalSales,
    LocalSaleItems,
    LocalPayments,
    Outbox,
    DeviceSequence,
    ReceiptAttempts,
    SyncWorkers,
  ],
)
class PosDatabase extends _$PosDatabase {
  final String contextId;
  PosDatabase(super.executor, this.contextId);
  static Future<PosDatabase> open(
    String contextId, {
    Directory? storageDirectory,
  }) async {
    if (!RegExp(r'^[a-f0-9]{64}$').hasMatch(contextId)) {
      throw const FormatException('Contexto local inválido.');
    }
    final folder = storageDirectory ?? await getApplicationSupportDirectory();
    final directory = Directory(p.join(folder.path, 'contexts'));
    await directory.create(recursive: true);
    final temporary = await getTemporaryDirectory();
    await temporary.create(recursive: true);
    sqlite.sqlite3.tempDirectory = temporary.path;
    return PosDatabase(
      NativeDatabase.createInBackground(
        File(p.join(directory.path, '$contextId.sqlite')),
      ),
      contextId,
    );
  }

  @override
  int get schemaVersion => 4;
  @override
  MigrationStrategy get migration => MigrationStrategy(
    onCreate: (m) async {
      await m.createAll();
    },
    onUpgrade: (m, from, to) async {
      if (from < 1 || from > 3 || to != 4) {
        throw StateError(
          'Migración no soportada. Conserva la base y solicita soporte.',
        );
      }
      if (from == 1) {
        await m.createTable(downloads);
        await m.createTable(downloadPages);
        await m.createTable(localEffects);
      }
      if (from < 3) {
        await m.createTable(localSales);
        await m.createTable(localSaleItems);
        await m.createTable(localPayments);
        await m.createTable(outbox);
        await m.createTable(deviceSequence);
        await m.createTable(receiptAttempts);
      } else {
        await m.addColumn(outbox, outbox.serverResult);
        await m.addColumn(outbox, outbox.errorCode);
      }
      await m.createTable(syncWorkers);
    },
    beforeOpen: (details) async {
      await customStatement('PRAGMA foreign_keys = ON');
      await customStatement('PRAGMA busy_timeout = 5000');
      final info = await select(syncMetadata).getSingleOrNull();
      if (info != null && info.contextId != contextId) {
        throw StateError('La base local pertenece a otro contexto.');
      }
      if (info == null) {
        await into(syncMetadata).insert(
          SyncMetadataCompanion.insert(
            id: const Value(1),
            contextId: contextId,
          ),
        );
      }
      await into(syncWorkers).insert(
        SyncWorkersCompanion.insert(contextId: contextId),
        mode: InsertMode.insertOrIgnore,
      );
      await into(deviceSequence).insert(
        DeviceSequenceCompanion.insert(contextId: contextId),
        mode: InsertMode.insertOrIgnore,
      );
      await customStatement(
        'CREATE INDEX IF NOT EXISTS sale_items_sale ON local_sale_items(sale_id, ordinal)',
      );
      await customStatement(
        "CREATE TRIGGER IF NOT EXISTS preserve_completed_sales BEFORE DELETE ON local_sales BEGIN SELECT RAISE(ABORT, 'Completed sales cannot be deleted'); END",
      );
      await customStatement(
        "CREATE TRIGGER IF NOT EXISTS immutable_outbox BEFORE UPDATE OF payload,payload_hash,operation_id,sale_id,context_id,sequence ON outbox WHEN NEW.payload <> OLD.payload OR NEW.payload_hash <> OLD.payload_hash OR NEW.operation_id <> OLD.operation_id OR NEW.sale_id <> OLD.sale_id OR NEW.context_id <> OLD.context_id OR NEW.sequence <> OLD.sequence BEGIN SELECT RAISE(ABORT, 'Queued sale identity and payload are immutable'); END",
      );
      await customStatement(
        'CREATE INDEX IF NOT EXISTS products_barcode ON local_products(barcode)',
      );
      await customStatement(
        'CREATE INDEX IF NOT EXISTS effects_entity ON local_effects(entity_id, kind, reflected)',
      );
    },
  );
}
