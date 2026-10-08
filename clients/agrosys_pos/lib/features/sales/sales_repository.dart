import 'dart:convert';

import 'package:crypto/crypto.dart';
import 'package:drift/drift.dart';
import 'package:uuid/uuid.dart';

import '../../core/models.dart';
import '../../data/catalog_repository.dart';
import '../../data/database.dart';
import 'sale_domain.dart';

class StoredReceipt {
  final LocalSale sale;
  final List<LocalSaleItem> items;
  final LocalPayment payment;
  StoredReceipt(this.sale, this.items, this.payment);
}

class SalesRepository {
  final CatalogRepository catalog;
  final Session session;
  PosDatabase get db => catalog.db;
  SalesRepository(this.catalog, this.session);

  Future<SaleQuote> quote(String customerId, List<CartLine> lines) =>
      db.transaction(() => _quote(customerId, lines));
  Future<SaleQuote> _quote(String customerId, List<CartLine> lines) async {
    if (lines.isEmpty ||
        lines.length > 200 ||
        lines.map((l) => l.productId).toSet().length != lines.length) {
      throw const SaleInputException(
        'Agrega entre 1 y 200 productos distintos.',
      );
    }
    final customer = await catalog.customerById(customerId);
    if (customer == null) {
      throw const SaleInputException(
        'Selecciona un cliente activo de esta sucursal.',
      );
    }
    final result = <QuotedLine>[];
    var total = 0;
    for (final line in lines) {
      final product = await catalog.productById(line.productId);
      if (product == null) {
        throw const SaleInputException(
          'Un producto dejó de estar disponible. Actualiza el carrito.',
        );
      }
      final price = discountedUnitPrice(
        product.priceCents,
        customer.discountBasisPoints,
      );
      final amount = saleLineTotal(price, line.quantityUnits);
      total += amount;
      if (total > maxSaleCents) {
        throw const SaleInputException('La venta supera el importe máximo.');
      }
      result.add(QuotedLine(product, line.quantityUnits, price, amount));
    }
    final info = await catalog.info();
    if (info.revision == null) {
      throw const SaleInputException(
        'Completa la descarga del catálogo antes de vender.',
      );
    }
    return SaleQuote(customer, result, total, info.revision!);
  }

  Future<void> _authorize(String type) async {
    if (session.contextId != db.contextId ||
        catalog.session.contextId != session.contextId ||
        !session.hasOfflineAccess ||
        await catalog.isLocked() ||
        !session.permissions.contains('sale.create') ||
        (type == 'Credito' && !session.permissions.contains('sale.credit'))) {
      throw const SaleInputException(
        'No hay autorización vigente para esta venta. Revalida online.',
      );
    }
    if (!await catalog.observeClock(DateTime.now())) {
      await catalog.setLocked(true);
      throw const SaleInputException(
        'El reloj retrocedió. Corrígelo y revalida online.',
      );
    }
  }

  Future<StoredReceipt> complete(SaleRequest request) async {
    if (!RegExp(
      r'^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$',
    ).hasMatch(request.saleId)) {
      throw const SaleInputException('Identificador de cierre inválido.');
    }
    await _authorize(request.type);
    return db.transaction(() async {
      final existing = await (db.select(
        db.localSales,
      )..where((t) => t.id.equals(request.saleId))).getSingleOrNull();
      if (existing != null) {
        if (existing.requestHash != request.fingerprint ||
            existing.contextId != session.contextId) {
          throw const SaleInputException(
            'Ese cierre ya pertenece a otra venta. Consulta su ticket.',
          );
        }
        return receipt(existing.id);
      }
      final quote = await _quote(request.customerId, request.lines);
      if (quote.priceFingerprint != request.expectedPriceFingerprint) {
        throw const SaleInputException(
          'Cambió el precio, descuento o nombre del catálogo. Revisa el total antes de finalizar.',
        );
      }
      if (quote.insufficientStock && !request.acknowledgeStock) {
        throw const SaleInputException(
          'La cantidad supera el stock estimado. Revisa y confirma la advertencia.',
        );
      }
      final payment = SalePayment.calculate(
        type: request.type,
        total: quote.totalCents,
        initial: request.initialCents,
        received: request.receivedCents,
      );
      // Recheck at the commit boundary, after asynchronous reads and quote validation.
      if (!session.hasOfflineAccess || await catalog.isLocked()) {
        throw const SaleInputException(
          'La autorización venció o fue bloqueada antes del cierre.',
        );
      }
      final sequence = await (db.select(
        db.deviceSequence,
      )..where((t) => t.contextId.equals(session.contextId))).getSingle();
      final number = sequence.nextSequence;
      final operationId = const Uuid().v4();
      final folio =
          'LOCAL-${session.deviceId.replaceAll('-', '').toUpperCase()}-${number.toString().padLeft(6, '0')}';
      final now = DateTime.now().toUtc().toIso8601String();
      final leaseId = object(session.lease!['claims'])['leaseId'] as String;
      await db
          .into(db.localSales)
          .insert(
            LocalSalesCompanion.insert(
              id: request.saleId,
              operationId: operationId,
              contextId: session.contextId,
              sequence: number,
              localFolio: folio,
              customerId: quote.customer.id,
              customerName: quote.customer.name,
              branchName: session.branch.name,
              operatorName: session.userName,
              saleType: request.type,
              discountBasisPoints: quote.customer.discountBasisPoints,
              totalCents: quote.totalCents,
              appliedCents: payment.appliedCents,
              receivedCents: payment.receivedCents,
              changeCents: payment.changeCents,
              balanceCents: payment.balanceCents,
              paid: payment.balanceCents == 0,
              occurredAt: now,
              offlineLeaseId: leaseId,
              catalogRevision: quote.catalogRevision,
              requestHash: request.fingerprint,
            ),
          );
      for (var i = 0; i < quote.lines.length; i++) {
        final line = quote.lines[i];
        await db
            .into(db.localSaleItems)
            .insert(
              LocalSaleItemsCompanion.insert(
                id: const Uuid().v4(),
                saleId: request.saleId,
                productId: line.product.id,
                productName: line.product.name,
                barcode: line.product.barcode,
                quantityUnits: line.quantityUnits,
                originalPriceCents: line.product.priceCents,
                unitPriceCents: line.unitPriceCents,
                totalCents: line.totalCents,
                ordinal: i,
              ),
            );
        await db
            .into(db.localEffects)
            .insert(
              LocalEffectsCompanion.insert(
                operationId: operationId,
                entityId: line.product.id,
                kind: 'stock',
                stockDelta: Value(-line.quantityUnits),
              ),
            );
      }
      await db
          .into(db.localPayments)
          .insert(
            LocalPaymentsCompanion.insert(
              id: const Uuid().v4(),
              saleId: request.saleId,
              appliedCents: payment.appliedCents,
              receivedCents: payment.receivedCents,
              changeCents: payment.changeCents,
            ),
          );
      await db
          .into(db.localEffects)
          .insert(
            LocalEffectsCompanion.insert(
              operationId: operationId,
              entityId: quote.customer.id,
              kind: 'account',
              debtDelta: Value(quote.totalCents),
              paymentDelta: Value(payment.appliedCents),
            ),
          );
      final payload = jsonEncode({
        'operation_id': operationId,
        'aggregate_type': 'sale',
        'aggregate_id': request.saleId,
        'event_type': 'SALE_COMPLETED',
        'sequence': number,
        'occurred_at': now,
        'offline_lease_id': leaseId,
        'payload': {
          'customerId': quote.customer.id,
          'saleType': request.type,
          'total': decimalText(quote.totalCents),
          'localFolio': folio,
          'items': quote.lines
              .map(
                (l) => {
                  'productId': l.product.id,
                  'quantity': decimalText(l.quantityUnits),
                  'unitPrice': decimalText(l.unitPriceCents),
                  'total': decimalText(l.totalCents),
                },
              )
              .toList(),
          'payments': [
            {'method': 'cash', 'amount': decimalText(payment.appliedCents)},
          ],
        },
      });
      await db
          .into(db.outbox)
          .insert(
            OutboxCompanion.insert(
              operationId: operationId,
              saleId: request.saleId,
              contextId: session.contextId,
              sequence: number,
              payload: payload,
              payloadHash: sha256.convert(utf8.encode(payload)).toString(),
            ),
          );
      await (db.update(db.deviceSequence)
            ..where((t) => t.contextId.equals(session.contextId)))
          .write(DeviceSequenceCompanion(nextSequence: Value(number + 1)));
      return receipt(request.saleId);
    });
  }

  Future<LocalSale?> findSale(String id) =>
      (db.select(db.localSales)..where(
            (t) => t.id.equals(id) & t.contextId.equals(session.contextId),
          ))
          .getSingleOrNull();

  Future<List<LocalSale>> history({
    int limit = 100,
    int offset = 0,
    String? status,
  }) =>
      (db.select(db.localSales)
            ..where(
              (t) =>
                  t.contextId.equals(session.contextId) &
                  (status == null
                      ? const Constant(true)
                      : t.status.equals(status)),
            )
            ..orderBy([(t) => OrderingTerm.desc(t.sequence)])
            ..limit(limit, offset: offset))
          .get();
  Future<StoredReceipt> receipt(String id) async {
    final sale =
        await (db.select(db.localSales)..where(
              (t) => t.id.equals(id) & t.contextId.equals(session.contextId),
            ))
            .getSingle();
    final items =
        await (db.select(db.localSaleItems)
              ..where((t) => t.saleId.equals(id))
              ..orderBy([(t) => OrderingTerm.asc(t.ordinal)]))
            .get();
    final payment = await (db.select(
      db.localPayments,
    )..where((t) => t.saleId.equals(id))).getSingle();
    return StoredReceipt(sale, items, payment);
  }
}
