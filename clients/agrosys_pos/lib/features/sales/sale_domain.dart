import 'dart:convert';

import 'package:crypto/crypto.dart';

import '../../core/models.dart';

const maxSaleCents = 9999999999;
const maxQuantityUnits = 99999999;

class SaleInputException implements Exception {
  final String message;
  const SaleInputException(this.message);
  @override
  String toString() => message;
}

int discountedUnitPrice(int price, int discount) {
  if (price < 0 || price > maxSaleCents || discount < 0 || discount > 10000) {
    throw const SaleInputException('Precio o descuento fuera de rango.');
  }
  return (price * (10000 - discount) + 5000) ~/ 10000;
}

int saleLineTotal(int price, int quantity) {
  if (quantity <= 0 || quantity > maxQuantityUnits) {
    throw const SaleInputException(
      'La cantidad debe ser positiva y no superar 999999.99.',
    );
  }
  final total = (price * quantity + 50) ~/ 100;
  if (total > maxSaleCents) {
    throw const SaleInputException('La partida supera el importe máximo.');
  }
  return total;
}

class CartLine {
  final String productId;
  final int quantityUnits;
  const CartLine(this.productId, this.quantityUnits);
  Map<String, dynamic> toJson() => {
    'productId': productId,
    'quantity': decimalText(quantityUnits),
  };
}

class QuotedLine {
  final ProductView product;
  final int quantityUnits, unitPriceCents, totalCents;
  QuotedLine(
    this.product,
    this.quantityUnits,
    this.unitPriceCents,
    this.totalCents,
  );
  Map<String, dynamic> toJson() => {
    'productId': product.id,
    'name': product.name,
    'barcode': product.barcode,
    'quantity': decimalText(quantityUnits),
    'originalPrice': decimalText(product.priceCents),
    'unitPrice': decimalText(unitPriceCents),
    'total': decimalText(totalCents),
  };
}

class SaleQuote {
  final CustomerView customer;
  final List<QuotedLine> lines;
  final int totalCents;
  final String catalogRevision;
  SaleQuote(
    this.customer,
    List<QuotedLine> lines,
    this.totalCents,
    this.catalogRevision,
  ) : lines = List.unmodifiable(lines);
  bool get insufficientStock =>
      lines.any((l) => l.quantityUnits > l.product.estimatedQuantity);
  String get priceFingerprint => sha256
      .convert(
        utf8.encode(
          jsonEncode({
            'customerId': customer.id,
            'discount': customer.discountBasisPoints,
            'lines': lines.map((l) => l.toJson()).toList(),
            'total': totalCents,
          }),
        ),
      )
      .toString();
}

class SalePayment {
  final int appliedCents, receivedCents, changeCents, balanceCents;
  SalePayment._(
    this.appliedCents,
    this.receivedCents,
    this.changeCents,
    this.balanceCents,
  );
  factory SalePayment.calculate({
    required String type,
    required int total,
    required int initial,
    required int received,
  }) {
    if (type != 'Contado' && type != 'Credito') {
      throw const SaleInputException('Tipo de venta inválido.');
    }
    final applied = type == 'Contado' ? total : initial;
    if (total < 0 ||
        total > maxSaleCents ||
        applied < 0 ||
        applied > total ||
        received < applied ||
        received > maxSaleCents) {
      throw const SaleInputException(
        'Revisa el inicial y el efectivo recibido: no puede ser menor al pago ni exceder el importe permitido.',
      );
    }
    return SalePayment._(
      applied,
      received,
      received - applied,
      total - applied,
    );
  }
}

class SaleRequest {
  final String saleId, customerId, type, expectedPriceFingerprint;
  final List<CartLine> lines;
  final int initialCents, receivedCents;
  final bool acknowledgeStock;
  SaleRequest({
    required this.saleId,
    required this.customerId,
    required this.type,
    required List<CartLine> lines,
    required this.initialCents,
    required this.receivedCents,
    required this.expectedPriceFingerprint,
    this.acknowledgeStock = false,
  }) : lines = List.unmodifiable(lines);
  String get fingerprint => sha256
      .convert(
        utf8.encode(
          jsonEncode({
            'saleId': saleId,
            'customerId': customerId,
            'type': type,
            'lines': lines.map((l) => l.toJson()).toList(),
            'initial': initialCents,
            'received': receivedCents,
            'priceFingerprint': expectedPriceFingerprint,
          }),
        ),
      )
      .toString();
}

String saleStatusText(String status) => switch (status) {
  'confirmed' => 'Confirmada',
  'conflict' => 'En revisión',
  'pending_sync' => 'Pendiente de sincronizar',
  _ => 'Estado: $status',
};
