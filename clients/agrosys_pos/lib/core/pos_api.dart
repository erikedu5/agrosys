import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

import 'models.dart';

class ApiFailure implements Exception {
  final int? status;
  final String message;
  ApiFailure(this.status, this.message);
  bool get blocksAccess => status == 401 || status == 402 || status == 403;
  bool get isConnectionFailure => status == null;
  @override
  String toString() => message;
}

String normalizeApiUrl(
  String raw, {
  bool allowDevelopmentHttp = !kReleaseMode,
}) {
  final uri = Uri.tryParse(raw.trim());
  if (uri == null ||
      !uri.hasAuthority ||
      uri.host.isEmpty ||
      uri.userInfo.isNotEmpty ||
      uri.hasQuery ||
      uri.hasFragment ||
      !['http', 'https'].contains(uri.scheme)) {
    throw const FormatException('Indica una URL válida del servidor POS.');
  }
  if (uri.scheme != 'https' && !allowDevelopmentHttp) {
    throw const FormatException('La versión de producción requiere HTTPS.');
  }
  return uri.toString().replaceAll(RegExp(r'/+$'), '');
}

class PosApi {
  final Dio dio;
  PosApi(String apiUrl, {Dio? client})
    : dio =
          client ??
          Dio(
            BaseOptions(
              baseUrl: '${normalizeApiUrl(apiUrl)}/',
              connectTimeout: const Duration(seconds: 10),
              receiveTimeout: const Duration(seconds: 20),
              headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
              },
            ),
          );
  Future<Map<String, dynamic>> request(
    String path, {
    Map<String, dynamic>? body,
    String? token,
    String method = 'POST',
  }) async {
    try {
      final response = await dio.request<Object?>(
        path,
        data: body,
        options: Options(
          method: method,
          headers: token == null ? {} : {'Authorization': 'Bearer $token'},
        ),
      );
      return object(response.data);
    } on DioException catch (e) {
      final status = e.response?.statusCode;
      final messages = {
        401: path == 'auth/login'
            ? 'No se pudo autenticar. Revisa tu correo y contraseña.'
            : 'Tu sesión requiere iniciar sesión nuevamente.',
        402: 'La empresa necesita una suscripción activa.',
        403: 'El dispositivo o la sucursal no están autorizados.',
        404: path == 'auth/login'
            ? 'El servidor no tiene disponible la API de AgroSys POS. Contacta al administrador para habilitarla.'
            : 'El recurso solicitado no está disponible en el servidor.',
        410: 'La descarga venció; se reiniciará conservando datos locales.',
        429: 'Demasiados intentos. Espera antes de reintentar.',
      };
      final response = e.response?.data;
      final serverMessage = response is Map && response['message'] is String
          ? response['message'] as String
          : null;
      throw ApiFailure(
        status,
        messages[status] ??
            (status == null
                ? 'No fue posible conectar. Puedes consultar el catálogo descargado.'
                : status >= 500
                ? 'El servidor no pudo completar la solicitud. Reintenta.'
                : serverMessage ?? 'La solicitud no pudo completarse.'),
      );
    } on FormatException {
      rethrow;
    }
  }

  Future<Map<String, dynamic>> login(
    String email,
    String password,
    String deviceId,
  ) => request(
    'auth/login',
    body: {
      'email': email,
      'password': password,
      'device_id': deviceId,
      'device_name': 'Agrosys POS',
    },
  );
  Future<Map<String, dynamic>> verify(
    String challenge,
    String code,
    bool recovery,
  ) => request(
    'auth/verify-2fa',
    body: {'challenge': challenge, recovery ? 'recovery_code' : 'code': code},
  );
  Future<Map<String, dynamic>> exchange(
    String token,
    Map<String, dynamic> context,
  ) => request('auth/context', token: token, body: context);
  Future<Map<String, dynamic>> activate(Session s) =>
      request('devices/activate', token: s.token, body: s.requestContext);
  Future<Map<String, dynamic>> renew(Session s) =>
      request('devices/renew', token: s.token, body: s.requestContext);
  Future<Map<String, dynamic>> page(
    Session s, {
    String? pageToken,
    String? cursor,
    required bool incremental,
    List<String> knownIds = const [],
  }) => request(
    incremental ? 'sync/pull' : 'bootstrap',
    token: s.token,
    body: {
      ...s.requestContext,
      'page_token': ?pageToken,
      'cursor': ?cursor,
      'page_size': 100,
      'known_operation_ids': knownIds,
    },
  );
  Future<Map<String, dynamic>> push(
    Session s,
    List<Map<String, dynamic>> operations,
  ) => request(
    'sync/push',
    token: s.token,
    body: {'schemaVersion': 2, ...s.requestContext, 'operations': operations},
  );
  Future<Map<String, dynamic>> operation(Session s, String id) =>
      request('operations/$id', token: s.token, method: 'GET');
  Future<void> logout(Session s) async {
    await request('auth/logout', token: s.token);
  }

  void close() => dio.close();
}
