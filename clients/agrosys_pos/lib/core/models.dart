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
  final int priceCents, estimatedQuantity;
  ProductView(
    this.id,
    this.name,
    this.barcode,
    this.size,
    this.priceCents,
    this.estimatedQuantity,
    this.revision,
  );
}

class CustomerView {
  final String id, name, revision;
  final int discountBasisPoints, balanceCents;
  CustomerView(
    this.id,
    this.name,
    this.discountBasisPoints,
    this.balanceCents,
    this.revision,
  );
}

class SyncInfo {
  final String? cursor, revision;
  final DateTime? syncedAt;
  SyncInfo(this.cursor, this.revision, this.syncedAt);
}
