import 'dart:convert';

import 'package:crypto/crypto.dart';

int decimalUnits(Object? value, {bool signed = false}) {
  if (value is! String) {
    throw const FormatException(
      'El servidor debe enviar decimales como texto.',
    );
  }
  final match = RegExp(
    signed
        ? r'^(-?)(\d{1,8})(?:\.(\d{1,2}))?$'
        : r'^()(\d{1,8})(?:\.(\d{1,2}))?$',
  ).firstMatch(value);
  if (match == null) throw const FormatException('Decimal inválido.');
  final amount =
      int.parse(match[2]!) * 100 + int.parse((match[3] ?? '').padRight(2, '0'));
  return match[1] == '-' ? -amount : amount;
}

String decimalText(int units) =>
    '${units < 0 ? '-' : ''}${units.abs() ~/ 100}.${(units.abs() % 100).toString().padLeft(2, '0')}';
String money(int cents) => '\$${decimalText(cents)}';
Map<String, dynamic> object(Object? value) =>
    Map<String, dynamic>.from(value as Map);

class Branch {
  final String id, companyId, name;
  Branch(this.id, this.companyId, this.name);
  factory Branch.fromJson(Map<String, dynamic> j) =>
      Branch(j['id'] as String, j['companyId'] as String, j['name'] as String);
  Map<String, dynamic> toJson() => {
    'id': id,
    'companyId': companyId,
    'name': name,
  };
}

class Session {
  final String apiUrl, userId, userName, deviceId, token;
  final Branch branch;
  final DateTime tokenExpiresAt;
  final Map<String, dynamic>? lease;
  final bool blocked;
  Session({
    required this.apiUrl,
    required this.userId,
    required this.userName,
    required this.deviceId,
    required this.token,
    required this.branch,
    required this.tokenExpiresAt,
    this.lease,
    this.blocked = false,
  });
  String get contextId => sha256
      .convert(
        utf8.encode(
          '$apiUrl|$userId|${branch.companyId}|${branch.id}|$deviceId',
        ),
      )
      .toString();
  Map<String, dynamic> get requestContext => {
    'device_id': deviceId,
    'branch_id': branch.id,
  };
  Set<String> get permissions => lease == null
      ? {}
      : Set<String>.from(object(lease!['claims'])['permissions'] as List);
  DateTime? get leaseExpiresAt => lease == null
      ? null
      : DateTime.parse(object(lease!['claims'])['expiresAt'] as String).toUtc();
  bool get hasOfflineAccess =>
      !blocked &&
      leaseExpiresAt != null &&
      DateTime.now().toUtc().isBefore(leaseExpiresAt!);
  Session copyWith({Map<String, dynamic>? lease, bool? blocked}) => Session(
    apiUrl: apiUrl,
    userId: userId,
    userName: userName,
    deviceId: deviceId,
    token: token,
    branch: branch,
    tokenExpiresAt: tokenExpiresAt,
    lease: lease ?? this.lease,
    blocked: blocked ?? this.blocked,
  );
  Map<String, dynamic> toJson() => {
    'apiUrl': apiUrl,
    'userId': userId,
    'userName': userName,
    'deviceId': deviceId,
    'token': token,
    'branch': branch.toJson(),
    'tokenExpiresAt': tokenExpiresAt.toIso8601String(),
    'lease': lease,
    'blocked': blocked,
  };
  factory Session.fromJson(Map<String, dynamic> j) => Session(
    apiUrl: j['apiUrl'] as String,
    userId: j['userId'] as String,
    userName: j['userName'] as String,
    deviceId: j['deviceId'] as String,
    token: j['token'] as String,
    branch: Branch.fromJson(object(j['branch'])),
    tokenExpiresAt: DateTime.parse(j['tokenExpiresAt'] as String),
    lease: j['lease'] == null ? null : object(j['lease']),
    blocked: j['blocked'] == true,
  );
}

class ProductView {
  final String id, name, barcode, size, revision;
  final String classification;
  final int priceCents, estimatedQuantity;
  final int preferential1Cents, preferential2Cents, preferential3Cents;
  ProductView(
    this.id,
    this.name,
    this.barcode,
    this.size,
    this.priceCents,
    this.estimatedQuantity,
    this.revision, {
    this.classification = '',
    int? preferential1Cents,
    int? preferential2Cents,
    int? preferential3Cents,
  }) : preferential1Cents = preferential1Cents ?? priceCents,
       preferential2Cents = preferential2Cents ?? priceCents,
       preferential3Cents = preferential3Cents ?? priceCents;

  /// Precio de venta para el tipo de precio del cliente (igual que Producto::precioPara).
  int priceFor(String tier) => switch (tier) {
    'preferencial_1' => preferential1Cents,
    'preferencial_2' => preferential2Cents,
    'preferencial_3' => preferential3Cents,
    _ => priceCents,
  };
}

/// Tipos de precio del cliente; mantener igual que Producto::PRECIOS.
const priceTiers = {
  'publico': 'Público en general',
  'preferencial_1': 'Distribuidor 1',
  'preferencial_2': 'Distribuidor 2',
  'preferencial_3': 'Distribuidor 3',
};
String priceTierLabel(String tier) =>
    priceTiers[tier] ?? priceTiers['publico']!;

/// Formas de pago SAT para clientes con factura; mantener igual que Venta::FORMAS_PAGO.
const paymentForms = {
  '01': 'Efectivo',
  '03': 'Transferencia electrónica',
  '04': 'Tarjeta de crédito',
  '28': 'Tarjeta de débito',
  '02': 'Cheque nominativo',
  '99': 'Por definir',
};

class CustomerView {
  final String id, name, revision;
  final int discountBasisPoints, balanceCents;
  final String priceTier;
  final bool requiresInvoice;
  final int? creditLimitCents;
  final String? creditBlockedReason;
  CustomerView(
    this.id,
    this.name,
    this.discountBasisPoints,
    this.balanceCents,
    this.revision, {
    this.priceTier = 'publico',
    this.requiresInvoice = false,
    this.creditLimitCents,
    this.creditBlockedReason,
  });

  /// Motivo por el que no puede comprar a crédito, o null (igual que Clientes::restriccionCredito).
  /// [newCreditCents]: saldo que la venta agregaría a su adeudo.
  String? creditRestriction([int newCreditCents = 0]) {
    if (creditBlockedReason != null) return creditBlockedReason;
    final limit = creditLimitCents;
    if (limit == null) return null;
    final balance = balanceCents < 0 ? 0 : balanceCents;
    if (balance + newCreditCents > limit ||
        (newCreditCents <= 0 && balance >= limit)) {
      return 'El cliente excede su límite de crédito de ${money(limit)} (adeudo actual ${money(balance)}).';
    }
    return null;
  }
}

/// Resumen del cliente para listas: tipo de precio, factura y adeudo.
String customerSummary(CustomerView c, {bool estimated = false}) => [
  'Precio ${priceTierLabel(c.priceTier)}',
  if (c.requiresInvoice) 'Factura',
  'Adeudo${estimated ? ' estimado' : ''} ${money(c.balanceCents)}',
].join(' · ');

class SyncInfo {
  final String? cursor, revision;
  final DateTime? syncedAt;
  SyncInfo(this.cursor, this.revision, this.syncedAt);
}
