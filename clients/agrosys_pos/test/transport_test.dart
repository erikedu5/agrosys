import 'dart:convert';
import 'dart:io';

import 'package:agrosys_pos/core/models.dart';
import 'package:agrosys_pos/core/pos_api.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/fixtures.dart';

void main() {
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
