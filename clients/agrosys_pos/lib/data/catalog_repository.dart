import 'dart:convert';

import 'package:drift/drift.dart';

import '../core/models.dart';
import 'database.dart';
import '../features/sync/sync_results.dart';

String normalizedName(String input) {
  const accents = 'áéíóúüñàèìòùäëïöâêîôû';
  const plain = 'aeiouunaeiouaeioaeiou';
  final lower = input.toLowerCase();
  return lower.split('').map((c) {
    final i = accents.indexOf(c);
    return i < 0 ? c : plain[i];
  }).join();
}

String searchPattern(String value) =>
    '%${normalizedName(value.trim()).replaceAll('\\', '\\\\').replaceAll('%', '\\%').replaceAll('_', '\\_')}%';

class CatalogRepository {
  final PosDatabase db;
  final Session session;
  CatalogRepository(this.db, this.session);
  Future<bool> isLocked() async =>
      (await db.select(db.syncMetadata).getSingle()).authLocked;
  Future<void> setLocked(bool locked) =>
      (db.update(db.syncMetadata)..where((t) => t.id.equals(1)))
          .write(SyncMetadataCompanion(authLocked: Value(locked)))
          .then((_) {});
  Future<void> markRevalidated() =>
      (db.update(db.syncMetadata)..where((t) => t.id.equals(1)))
          .write(
            SyncMetadataCompanion(
              authLocked: const Value(false),
              lastObservedAt: Value(DateTime.now().toUtc().toIso8601String()),
            ),
          )
          .then((_) {});

  Future<SyncInfo> info() async {
    final data = await db.select(db.syncMetadata).getSingle();
    return SyncInfo(
      data.cursor,
      data.revision,
      data.syncedAt == null ? null : DateTime.parse(data.syncedAt!),
    );
  }

  Future<bool> observeClock(DateTime now) async {
    final previous = await db.select(db.syncMetadata).getSingle();
    final last = previous.lastObservedAt == null
        ? null
        : DateTime.parse(previous.lastObservedAt!);
    if (last != null &&
        now.toUtc().isBefore(last.subtract(const Duration(minutes: 5)))) {
      return false;
    }
    await (db.update(db.syncMetadata)..where((t) => t.id.equals(1))).write(
      SyncMetadataCompanion(
        lastObservedAt: Value(now.toUtc().toIso8601String()),
      ),
    );
    return true;
  }

  Future<ProductView?> productById(String id) async {
    final product =
        await (db.select(db.localProducts)
              ..where((t) => t.serverId.equals(id) & t.active.equals(true)))
            .getSingleOrNull();
    if (product == null) return null;
    final stock = await (db.select(
      db.stockSnapshots,
    )..where((t) => t.productId.equals(id))).getSingleOrNull();
    final delta = await db
        .customSelect(
          "SELECT COALESCE(SUM(stock_delta), 0) AS delta FROM local_effects WHERE entity_id=? AND kind='stock' AND reflected=0",
          variables: [Variable(id)],
        )
        .getSingle();
    return ProductView(
      product.serverId,
      product.name,
      product.barcode,
      product.size,
      product.priceCents,
      (stock?.quantityUnits ?? 0) + delta.read<int>('delta'),
      product.revision,
    );
  }

  Future<CustomerView?> customerById(String id) async {
    final customer =
        await (db.select(db.localCustomers)
              ..where((t) => t.serverId.equals(id) & t.active.equals(true)))
            .getSingleOrNull();
    if (customer == null) return null;
    final account = await (db.select(
      db.accountSnapshots,
    )..where((t) => t.customerId.equals(id))).getSingleOrNull();
    final delta = await db
        .customSelect(
          "SELECT COALESCE(SUM(debt_delta-payment_delta), 0) AS delta FROM local_effects WHERE entity_id=? AND kind='account' AND reflected=0",
          variables: [Variable(id)],
        )
        .getSingle();
    return CustomerView(
      customer.serverId,
      customer.name,
      customer.discountBasisPoints,
      (account?.balanceCents ?? 0) + delta.read<int>('delta'),
      customer.revision,
    );
  }

  Future<String?> defaultCustomer() async =>
      (await db.select(db.syncMetadata).getSingle()).defaultCustomerId;

  Future<List<ProductView>> products(String query) async {
    final rows = await db
        .customSelect(
          r'''SELECT p.*, COALESCE(s.quantity_units,0) + COALESCE((SELECT SUM(e.stock_delta) FROM local_effects e WHERE e.entity_id=p.server_id AND e.kind='stock' AND e.reflected=0),0) AS estimated_quantity
      FROM local_products p LEFT JOIN stock_snapshots s ON s.product_id=p.server_id
      WHERE p.active=1 AND (p.search_name LIKE ? ESCAPE '\' OR p.server_id=? OR p.barcode=?) ORDER BY p.search_name LIMIT 200''',
          variables: [
            Variable(searchPattern(query)),
            Variable(query.trim()),
            Variable(query.trim()),
          ],
          readsFrom: {db.localProducts, db.stockSnapshots, db.localEffects},
        )
        .get();
    return rows
        .map(
          (r) => ProductView(
            r.read<String>('server_id'),
            r.read<String>('name'),
            r.read<String>('barcode'),
            r.read<String>('size'),
            r.read<int>('price_cents'),
            r.read<int>('estimated_quantity'),
            r.read<String>('revision'),
          ),
        )
        .toList();
  }

  Future<List<CustomerView>> customers(String query) async {
    final rows = await db
        .customSelect(
          r'''SELECT c.*, COALESCE(a.balance_cents,0) + COALESCE((SELECT SUM(e.debt_delta-e.payment_delta) FROM local_effects e WHERE e.entity_id=c.server_id AND e.kind='account' AND e.reflected=0),0) AS estimated_balance
      FROM local_customers c LEFT JOIN account_snapshots a ON a.customer_id=c.server_id
      WHERE c.active=1 AND (c.search_name LIKE ? ESCAPE '\' OR c.server_id=?) ORDER BY c.search_name LIMIT 200''',
          variables: [Variable(searchPattern(query)), Variable(query.trim())],
          readsFrom: {db.localCustomers, db.accountSnapshots, db.localEffects},
        )
        .get();
    return rows
        .map(
          (r) => CustomerView(
            r.read<String>('server_id'),
            r.read<String>('name'),
            r.read<int>('discount_basis_points'),
            r.read<int>('estimated_balance'),
            r.read<String>('revision'),
          ),
        )
        .toList();
  }

  Future<List<String>> knownOperationIds() async {
    final rows = await db
        .customSelect(
          "SELECT DISTINCT e.operation_id FROM local_effects e LEFT JOIN outbox o ON o.operation_id=e.operation_id WHERE e.reflected=0 ORDER BY CASE o.status WHEN 'confirmed' THEN 0 WHEN 'conflict' THEN 2 ELSE 1 END, o.sequence LIMIT 2000",
          readsFrom: {db.localEffects},
        )
        .get();
    return rows.map((r) => r.read<String>('operation_id')).toList();
  }

  Future<Download?> download() => db.select(db.downloads).getSingleOrNull();
  Future<void> startDownload({required bool incremental, String? cursor}) =>
      db.transaction(() async {
        await db.delete(db.downloadPages).go();
        await db.delete(db.downloads).go();
        await db
            .into(db.downloads)
            .insert(
              DownloadsCompanion.insert(
                id: const Value(1),
                incremental: incremental,
                baseCursor: Value(cursor),
              ),
            );
      });
  Future<void> stage(Map<String, dynamic> page) => db.transaction(() async {
    final download = await db.select(db.downloads).getSingle();
    final revision = page['snapshotRevision'] as String;
    if (page['schemaVersion'] != 2 ||
        object(page['branch'])['id'] != session.branch.id ||
        object(page['branch'])['companyId'] != session.branch.companyId ||
        object(page['user'])['id'] != session.userId ||
        !(object(page['user'])['permissions'] as List).contains(
          'catalog.read',
        ) ||
        (download.revision != null && download.revision != revision)) {
      throw const FormatException('Descarga de otro contexto o revisión.');
    }
    final more = page['hasMore'] as bool;
    if (download.complete ||
        (more &&
            (page['pageToken'] is! String ||
                page['pageToken'] == download.nextPageToken)) ||
        (!more && page['nextCursor'] is! String) ||
        (more &&
            (page['nextCursor'] != null ||
                (page['operationReceipts'] as List).isNotEmpty))) {
      throw const FormatException('Continuación de descarga inválida.');
    }
    for (final raw in page['products'] as List) {
      final product = object(raw);
      if (product['branchId'] != session.branch.id) {
        throw const FormatException('Producto de otra sucursal.');
      }
      if (product['deleted'] != true) {
        decimalUnits(product['price']);
        decimalUnits(product['serverQuantity'], signed: true);
      }
    }
    for (final raw in page['customers'] as List) {
      final customer = object(raw);
      if (customer['branchId'] != session.branch.id) {
        throw const FormatException('Cliente de otra sucursal.');
      }
      if (customer['deleted'] != true) {
        decimalUnits(customer['balance'], signed: true);
        decimalUnits(customer['discountPercentage']);
      }
    }
    await db
        .into(db.downloadPages)
        .insert(
          DownloadPagesCompanion.insert(
            ordinal: Value(download.pageCount),
            payload: jsonEncode(page),
          ),
        );
    await (db.update(db.downloads)..where((t) => t.id.equals(1))).write(
      DownloadsCompanion(
        revision: Value(revision),
        pageCount: Value(download.pageCount + 1),
        nextPageToken: Value(more ? page['pageToken'] as String : null),
        complete: Value(!more),
      ),
    );
  });
  Future<void> commitDownload() => db.transaction(() async {
    final download = await db.select(db.downloads).getSingle();
    if (!download.complete) {
      throw StateError('La descarga aún no está completa.');
    }
    final pages = await (db.select(
      db.downloadPages,
    )..orderBy([(t) => OrderingTerm.asc(t.ordinal)])).get();
    if (pages.length != download.pageCount || pages.isEmpty) {
      throw StateError('Faltan páginas de la descarga.');
    }
    if (!download.incremental) {
      await db.delete(db.localProducts).go();
      await db.delete(db.localCustomers).go();
      await db.delete(db.stockSnapshots).go();
      await db.delete(db.accountSnapshots).go();
    }
    Map<String, dynamic>? last;
    for (final stored in pages) {
      final page = object(jsonDecode(stored.payload));
      last = page;
      for (final raw in page['products'] as List) {
        final product = object(raw);
        final id = product['id'] as String;
        if (product['deleted'] == true) {
          await (db.update(
            db.localProducts,
          )..where((t) => t.serverId.equals(id))).write(
            LocalProductsCompanion(
              active: const Value(false),
              revision: Value(download.revision!),
            ),
          );
          continue;
        }
        await db
            .into(db.localProducts)
            .insertOnConflictUpdate(
              LocalProductsCompanion.insert(
                serverId: id,
                name: product['name'] as String,
                searchName: normalizedName(product['name'] as String),
                barcode: product['barcode'] as String,
                size: product['size'] as String? ?? '',
                priceCents: decimalUnits(product['price']),
                active: product['active'] as bool,
                revision: download.revision!,
              ),
            );
        await db
            .into(db.stockSnapshots)
            .insertOnConflictUpdate(
              StockSnapshotsCompanion.insert(
                productId: id,
                quantityUnits: decimalUnits(
                  product['serverQuantity'],
                  signed: true,
                ),
                revision: download.revision!,
              ),
            );
      }
      for (final raw in page['customers'] as List) {
        final customer = object(raw);
        final id = customer['id'] as String;
        if (customer['deleted'] == true) {
          await (db.update(
            db.localCustomers,
          )..where((t) => t.serverId.equals(id))).write(
            LocalCustomersCompanion(
              active: const Value(false),
              revision: Value(download.revision!),
            ),
          );
          continue;
        }
        await db
            .into(db.localCustomers)
            .insertOnConflictUpdate(
              LocalCustomersCompanion.insert(
                serverId: id,
                name: customer['name'] as String,
                searchName: normalizedName(customer['name'] as String),
                discountBasisPoints: decimalUnits(
                  customer['discountPercentage'],
                ),
                active: customer['active'] as bool,
                revision: download.revision!,
              ),
            );
        await db
            .into(db.accountSnapshots)
            .insertOnConflictUpdate(
              AccountSnapshotsCompanion.insert(
                customerId: id,
                debtCents: decimalUnits(customer['debtTotal']),
                paymentCents: decimalUnits(customer['paymentTotal']),
                balanceCents: decimalUnits(customer['balance'], signed: true),
                revision: download.revision!,
              ),
            );
      }
    }
    for (final raw in last!['operationReceipts'] as List) {
      final receipt = object(raw);
      if (receipt['includedInSnapshot'] == true) {
        if (receipt['originalStatus'] != 'confirmed' ||
            object(receipt['result'])['originalStatus'] != 'confirmed') {
          throw const FormatException(
            'El recibo no confirma incorporación del movimiento.',
          );
        }
        final row =
            await (db.select(db.outbox)..where(
                  (t) => t.operationId.equals(receipt['operationId'] as String),
                ))
                .getSingleOrNull();
        if (row != null &&
            [
              'IDEMPOTENCY_PAYLOAD_MISMATCH',
              'LOCAL_PAYLOAD_CORRUPT',
            ].contains(row.errorCode)) {
          // A central receipt cannot prove inclusion of these different local bytes.
          continue;
        }
        if (row != null) {
          await confirmOperation(db, row, object(receipt['result']));
        }
        await (db.update(db.localEffects)..where(
              (t) => t.operationId.equals(receipt['operationId'] as String),
            ))
            .write(
              const LocalEffectsCompanion(
                reflected: Value(true),
                originalStatus: Value('confirmed'),
              ),
            );
      }
    }
    await (db.update(db.syncMetadata)..where((t) => t.id.equals(1))).write(
      SyncMetadataCompanion(
        cursor: Value(last['nextCursor'] as String),
        revision: Value(download.revision),
        syncedAt: Value(last['syncedAt'] as String),
        defaultCustomerId: Value(
          object(last['branch'])['defaultCustomerId'] as String?,
        ),
      ),
    );
    await db.delete(db.downloadPages).go();
    await db.delete(db.downloads).go();
  });
}
