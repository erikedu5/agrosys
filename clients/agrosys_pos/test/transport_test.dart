import 'dart:convert';
import 'dart:io';

import 'package:agrosys_pos/core/models.dart';
import 'package:agrosys_pos/core/pos_api.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/fixtures.dart';

void main() {
  test('transport preserves the session confirmation code and sends approval only explicitly', () async {
    final server = await HttpServer.bind(InternetAddress.loopbackIPv4, 0);
    final api = PosApi('http://127.0.0.1:${server.port}/api/v1/pos');
    final bodies = <Map<String, dynamic>>[];
    server.listen((request) async {
      bodies.add(
        jsonDecode(await utf8.decoder.bind(request).join())
            as Map<String, dynamic>,
      );
      request.response.statusCode = 409;
      request.response.headers.contentType = ContentType.json;
      request.response.write(
        jsonEncode({
          'code': 'DEVICE_SESSION_CONFIRMATION_REQUIRED',
          'message': 'Confirma el cierre.',
        }),
      );
      await request.response.close();
    });
    try {
      for (final confirmed in [false, true]) {
        await expectLater(
          api.activate(fixtureSession(), closeOtherSessions: confirmed),
          throwsA(
            isA<ApiFailure>().having(
              (e) => e.code,
              'code',
              'DEVICE_SESSION_CONFIRMATION_REQUIRED',
            ),
          ),
        );
      }
      expect(bodies.first.containsKey('close_other_sessions'), isFalse);
      expect(bodies.last['close_other_sessions'], isTrue);
    } finally {
      api.close();
      await server.close(force: true);
    }
  });

  test(
    'rate limit honors retry-after without sending repeated requests',
    () async {
      final server = await HttpServer.bind(InternetAddress.loopbackIPv4, 0);
      final api = PosApi('http://127.0.0.1:${server.port}/api/v1/pos');
      var requests = 0;
      server.listen((request) async {
        requests++;
        await request.drain<void>();
        request.response.statusCode = 429;
        request.response.headers.set('Retry-After', '30');
        request.response.headers.contentType = ContentType.json;
        request.response.write(jsonEncode({'message': 'Too many requests'}));
        await request.response.close();
      });
      try {
        for (var i = 0; i < 2; i++) {
          await expectLater(
            api.page(fixtureSession(), incremental: false),
            throwsA(
              isA<ApiFailure>()
                  .having((e) => e.status, 'status', 429)
                  .having(
                    (e) => e.message,
                    'wait time',
                    contains('30 segundos'),
                  ),
            ),
          );
        }
        expect(requests, 1);
      } finally {
        api.close();
        await server.close(force: true);
      }
    },
  );
  test(
    'login distinguishes unavailable native API from rejected credentials',
    () async {
      final server = await HttpServer.bind(InternetAddress.loopbackIPv4, 0);
      final api = PosApi('http://127.0.0.1:${server.port}/api/v1/pos');
      var status = 404;
      server.listen((request) async {
        expect(request.uri.path, '/api/v1/pos/auth/login');
        expect(request.headers.value('Authorization'), isNull);
        await request.drain<void>();
        request.response.statusCode = status;
        request.response.headers.contentType = ContentType.html;
        request.response.write('<html>Error</html>');
        await request.response.close();
      });
      try {
        await expectLater(
          api.login('qa@example.com', 'invalid', fixtureSession().deviceId),
          throwsA(
            isA<ApiFailure>()
                .having((e) => e.status, 'status', 404)
                .having((e) => e.message, 'API unavailable', contains('API')),
          ),
        );
        status = 401;
        await expectLater(
          api.login('qa@example.com', 'invalid', fixtureSession().deviceId),
          throwsA(
            isA<ApiFailure>()
                .having((e) => e.status, 'status', 401)
                .having(
                  (e) => e.message,
                  'credentials',
                  contains('contraseña'),
                ),
          ),
        );
      } finally {
        api.close();
        await server.close(force: true);
      }
    },
  );
  test('API sends scoped bearer/context and distinguishes revocation from no network', () async {
    final server = await HttpServer.bind(InternetAddress.loopbackIPv4, 0);
    final api = PosApi('http://127.0.0.1:${server.port}/api/v1/pos');
    var denied = false;
    server.listen((request) async {
      expect(request.uri.path, '/api/v1/pos/bootstrap');
      expect(request.headers.value('Authorization'), 'Bearer test-token');
      final body = object(jsonDecode(await utf8.decoder.bind(request).join()));
      expect(body['device_id'], fixtureSession().deviceId);
      expect(body['branch_id'], '1');
      request.response.headers.contentType = ContentType.json;
      request.response.statusCode = denied ? 403 : 200;
      request.response.write(
        jsonEncode(denied ? {'message': 'Revoked'} : snapshot()),
      );
      await request.response.close();
    });
    try {
      expect(
        (await api.page(fixtureSession(), incremental: false))['schemaVersion'],
        2,
      );
      denied = true;
      await expectLater(
        api.page(fixtureSession(), incremental: false),
        throwsA(
          isA<ApiFailure>()
              .having((e) => e.blocksAccess, 'authorization failure', true)
              .having((e) => e.isConnectionFailure, 'not offline', false),
        ),
      );
      await server.close(force: true);
      await expectLater(
        api.page(fixtureSession(), incremental: false),
        throwsA(
          isA<ApiFailure>().having(
            (e) => e.isConnectionFailure,
            'offline',
            true,
          ),
        ),
      );
    } finally {
      api.close();
      await server.close(force: true);
    }
  });
}
