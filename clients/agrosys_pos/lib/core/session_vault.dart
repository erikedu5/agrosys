import 'dart:convert';

import 'package:crypto/crypto.dart';
import 'package:cryptography/cryptography.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:uuid/uuid.dart';

import 'models.dart';

abstract interface class SecretStore {
  Future<String?> read(String key);
  Future<void> write(String key, String value);
  Future<void> delete(String key);
}

class PlatformSecretStore implements SecretStore {
  final FlutterSecureStorage storage;
  PlatformSecretStore([FlutterSecureStorage? storage])
    : storage =
          storage ??
          const FlutterSecureStorage(
            iOptions: IOSOptions(
              accountName: 'mx.agrosys.pos.credentials.v1',
              accessibility: KeychainAccessibility.first_unlock_this_device,
            ),
            mOptions: MacOsOptions(
              accountName: 'mx.agrosys.pos.credentials.v1',
              usesDataProtectionKeychain: false,
              accessibility: KeychainAccessibility.first_unlock_this_device,
            ),
          );
  @override
  Future<String?> read(String key) => storage.read(key: key);
  @override
  Future<void> write(String key, String value) =>
      storage.write(key: key, value: value);
  @override
  Future<void> delete(String key) => storage.delete(key: key);
}

Object? _canonical(Object? value) {
  if (value is Map) {
    final keys = value.keys.cast<String>().toList()..sort();
    return {for (final key in keys) key: _canonical(value[key])};
  }
  if (value is List) return value.map(_canonical).toList();
  return value;
}

class SessionVault {
  final SecretStore store;
  SessionVault(this.store);
  Future<String> deviceFor(String apiUrl, String owner, String branch) async {
    final key =
        'device_${sha256.convert(utf8.encode('$apiUrl|$owner|$branch'))}';
    final saved = await store.read(key);
    if (saved != null) return saved;
    final id = const Uuid().v4();
    await store.write(key, id);
    return id;
  }

  Future<Session?> load() async {
    final text = await store.read('active_session');
    return text == null ? null : Session.fromJson(object(jsonDecode(text)));
  }

  Future<void> save(Session session) =>
      store.write('active_session', jsonEncode(session.toJson()));
  Future<void> lock() => store.delete('active_session');
  Future<void> verifyLease(Session session, Map<String, dynamic> lease) async {
    if (lease['algorithm'] != 'Ed25519') {
      throw const FormatException('Firma offline no soportada.');
    }
    final payload = lease['payload'] as String;
    final bytes = base64Decode(lease['publicKey'] as String);
    final valid = await Ed25519().verify(
      utf8.encode(payload),
      signature: Signature(
        base64Decode(lease['signature'] as String),
        publicKey: SimplePublicKey(bytes, type: KeyPairType.ed25519),
      ),
    );
    if (!valid) {
      throw const FormatException(
        'La firma de autorización offline no es válida.',
      );
    }
    final claims = object(jsonDecode(payload));
    if (jsonEncode(_canonical(claims)) !=
            jsonEncode(_canonical(lease['claims'])) ||
        claims['userId'] != session.userId ||
        claims['companyId'] != session.branch.companyId ||
        claims['branchId'] != session.branch.id ||
        claims['deviceId'] != session.deviceId ||
        claims['version'] != 1) {
      throw const FormatException('La autorización pertenece a otro contexto.');
    }
    final start = DateTime.parse(claims['issuedAt'] as String).toUtc();
    final end = DateTime.parse(claims['expiresAt'] as String).toUtc();
    if (!end.isAfter(start) ||
        start.isAfter(DateTime.now().toUtc().add(const Duration(minutes: 5)))) {
      throw const FormatException('Vigencia offline inválida.');
    }
    if (!(claims['permissions'] as List).contains('catalog.read')) {
      throw const FormatException('No hay permiso para consultar el catálogo.');
    }
    final pinKey = 'key_${sha256.convert(utf8.encode(session.apiUrl))}';
    final pinned = await store.read(pinKey);
    if (pinned != null && pinned != lease['publicKey']) {
      throw const FormatException(
        'Cambió la clave del servidor. Se requiere revisión de la instalación.',
      );
    }
    if (pinned == null) await store.write(pinKey, lease['publicKey'] as String);
  }
}
