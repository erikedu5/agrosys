import 'dart:convert';

import 'package:cryptography/cryptography.dart';
import 'package:agrosys_pos/core/models.dart';
import 'package:agrosys_pos/core/pos_api.dart';
import 'package:agrosys_pos/core/session_vault.dart';

Session fixtureSession({
  String user = '1',
  String device = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
  Map<String, dynamic>? lease,
  bool blocked = false,
}) => Session(
  apiUrl: 'https://pos.example/api/v1/pos',
  userId: user,
  userName: 'Operador',
  deviceId: device,
  token: 'test-token',
  branch: Branch('1', '1', 'Centro'),
  tokenExpiresAt: DateTime.now().add(const Duration(days: 30)),
  lease: lease,
  blocked: blocked,
);
Future<Map<String, dynamic>> signedLease(
  Session s, {
  SimpleKeyPair? pair,
  List<String>? permissions,
  DateTime? issuedAt,
  DateTime? expiresAt,
}) async {
  final algorithm = Ed25519();
  final key = pair ?? await algorithm.newKeyPair();
  final claims = {
    'leaseId': 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
    'version': 1,
    'userId': s.userId,
    'companyId': s.branch.companyId,
    'branchId': s.branch.id,
    'deviceId': s.deviceId,
    'permissions':
        permissions ??
        [
          'catalog.read',
          'product.search',
          'stock.read_estimated',
          'account.read_estimated',
        ],
    'issuedAt':
        (issuedAt ??
                DateTime.now().toUtc().subtract(const Duration(minutes: 1)))
            .toIso8601String(),
    'expiresAt':
        (expiresAt ?? DateTime.now().toUtc().add(const Duration(days: 7)))
            .toIso8601String(),
    'lateAcceptanceHours': 24,
  };
  final payload = jsonEncode(claims);
  final signature = await algorithm.sign(utf8.encode(payload), keyPair: key);
  return {
    'claims': claims,
    'payload': payload,
    'signature': base64Encode(signature.bytes),
    'publicKey': base64Encode((await key.extractPublicKey()).bytes),
    'algorithm': 'Ed25519',
  };
}

Map<String, dynamic> product({
  String id = '10',
  String name = 'Semillas de maíz',
  String quantity = '10.00',
  bool active = true,
}) => {
  'id': id,
  'name': name,
  'barcode': '7501234567890',
  'size': '1 kg',
  'price': '50.00',
  'unitPrice': '50.00',
  'active': active,
  'branchId': '1',
  'serverQuantity': quantity,
};
Map<String, dynamic> customer({String balance = '40.00', bool active = true}) =>
    {
      'id': '20',
      'name': 'José Pérez',
      'branchId': '1',
      'active': active,
      'discountPercentage': '5.00',
      'debtTotal': balance,
      'paymentTotal': '0.00',
      'balance': balance,
    };
Map<String, dynamic> snapshot({
  String revision = '11111111-1111-4111-8111-111111111111',
  bool more = false,
  List<Map<String, dynamic>>? products,
  List<Map<String, dynamic>>? customers,
  List<Map<String, dynamic>> receipts = const [],
}) => {
  'schemaVersion': 2,
  'snapshotRevision': revision,
  'syncedAt': DateTime.now().toUtc().toIso8601String(),
  'user': {
    'id': '1',
    'permissions': ['catalog.read'],
  },
  'branch': {
    'id': '1',
    'companyId': '1',
    'name': 'Centro',
    'defaultCustomerId': '20',
  },
  'products': products ?? [product()],
  'customers': customers ?? [customer()],
  'operationReceipts': receipts,
  'hasMore': more,
  'pageToken': more ? 'page-two' : null,
  'nextCursor': more ? null : 'cursor-$revision',
};
Map<String, dynamic> receipt(String id) => {
  'operationId': id,
  'originalStatus': 'confirmed',
  'includedInSnapshot': true,
  'result': {'originalStatus': 'confirmed', 'status': 'confirmed'},
};

class MemorySecretStore implements SecretStore {
  final values = <String, String>{};
  @override
  Future<String?> read(String key) async => values[key];
  @override
  Future<void> write(String key, String value) async {
    values[key] = value;
  }

  @override
  Future<void> delete(String key) async {
    values.remove(key);
  }
}

class FixtureApi extends PosApi {
  bool disconnected = false;
  bool salesEnabled = false;
  List<String>? permissions;
  int? deniedStatus;
  bool requiresTwoFactor = false;
  final calls = <String>[];
  final bodies = <Map<String, dynamic>>[];
  SimpleKeyPair? key;
  String device = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
  final List<Object> pages;
  FixtureApi({List<Object>? pages})
    : pages = pages ?? [],
      super('https://pos.example/api/v1/pos');
  @override
  Future<Map<String, dynamic>> request(
    String path, {
    Map<String, dynamic>? body,
    String? token,
    String method = 'POST',
  }) async {
    calls.add(path);
    bodies.add(body ?? {});
    if (disconnected) throw ApiFailure(null, 'Sin conexión');
    if (deniedStatus != null) {
      throw ApiFailure(deniedStatus, 'Acceso bloqueado');
    }
    if (path == 'auth/login' && requiresTwoFactor) {
      return {'requiresTwoFactor': true, 'challenge': 'challenge'};
    }
    if (path == 'auth/login' ||
        path == 'auth/verify-2fa' ||
        path == 'auth/context') {
      device = body?['device_id'] as String? ?? device;
      return {
        'requiresTwoFactor': false,
        'token': 'test-token',
        'expiresAt': DateTime.now()
            .add(const Duration(days: 30))
            .toIso8601String(),
        'user': {'id': '1', 'name': 'Operador'},
        'branches': [
          {'id': '1', 'companyId': '1', 'name': 'Centro'},
        ],
      };
    }
    if (path == 'devices/activate' || path == 'devices/renew') {
      key ??= await Ed25519().newKeyPair();
      return {
        'authorized': true,
        'offlineLease': await signedLease(
          fixtureSession(device: device),
          pair: key,
          permissions:
              permissions ??
              (salesEnabled
                  ? [
                      'catalog.read',
                      'product.search',
                      'stock.read_estimated',
                      'account.read_estimated',
                      'sale.create',
                      'sale.credit',
                      'sale.print_local_ticket',
                      'customer.read',
                      'sale.history',
                    ]
                  : null),
        ),
      };
    }
    if (path == 'bootstrap' || path == 'sync/pull') {
      if (pages.isEmpty) return snapshot();
      final next = pages.removeAt(0);
      if (next is Exception) throw next;
      return object(next);
    }
    return {'authorized': true};
  }

  @override
  void close() {}
}
