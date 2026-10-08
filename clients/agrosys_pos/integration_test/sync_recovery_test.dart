import 'dart:convert';
import 'dart:io';

import 'package:agrosys_pos/core/app_controller.dart';
import 'package:agrosys_pos/core/models.dart';
import 'package:agrosys_pos/core/pos_api.dart';
import 'package:agrosys_pos/core/session_vault.dart';
import 'package:agrosys_pos/data/database.dart';
import 'package:agrosys_pos/features/sales/sale_domain.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';
import 'package:path_provider/path_provider.dart';
import 'package:uuid/uuid.dart';

import '../test/support/sync_server.dart';
import 'offline_reopen_test.dart' show IsolatedPlatformSecrets;

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'native lost response/reopen/query/pull reconciles cash and credit once',
    (tester) async {
      await tester.runAsync(() async {
        final secrets = IsolatedPlatformSecrets();
        final dir = await Directory(
          '${(await getTemporaryDirectory()).path}/sync-qa-${const Uuid().v4()}',
        ).create(recursive: true);
        final fixture = SyncFixtureApi();
        final server = await HttpServer.bind(InternetAddress.loopbackIPv4, 0);
        final url = 'http://127.0.0.1:${server.port}/api/v1/pos';
        server.listen((request) async {
          try {
            final raw = await utf8.decoder.bind(request).join();
            final result = await fixture.request(
              request.uri.path.replaceFirst('/api/v1/pos/', ''),
              body: raw.isEmpty ? null : object(jsonDecode(raw)),
              token: request.headers.value('Authorization'),
              method: request.method,
            );
            request.response.headers.contentType = ContentType.json;
            request.response.write(jsonEncode(result));
          } on ApiFailure catch (e) {
            request.response.statusCode = e.status ?? 503;
            request.response.headers.contentType = ContentType.json;
            request.response.write(jsonEncode({'message': e.message}));
          } catch (e) {
            request.response.statusCode = 500;
            request.response.write(jsonEncode({'message': e.toString()}));
          }
          await request.response.close();
        });
        AppController make() => AppController(
          enableAutomaticSync: false,
          vault: SessionVault(secrets),
          openDatabase: (id) => PosDatabase.open(id, storageDirectory: dir),
        );
        var c = make();
        try {
          await c.initialize();
          await c.signIn(url, 'qa@example.com', 'password');
          await c.selectBranch(c.branches.single);
          expect(c.error, isNull);
          fixture.disconnected = true;
          for (final type in ['Contado', 'Credito']) {
            final q = await c.sales!.quote('20', [const CartLine('10', 100)]);
            expect(
              await c.completeSale(
                SaleRequest(
                  saleId: const Uuid().v4(),
                  customerId: '20',
                  type: type,
                  lines: [const CartLine('10', 100)],
                  initialCents: type == 'Credito' ? 1000 : 0,
                  receivedCents: type == 'Credito' ? 1000 : 5000,
                  expectedPriceFingerprint: q.priceFingerprint,
                ),
              ),
              isNotNull,
            );
          }
          final original = {
            for (final r
                in await c.repository!.db.select(c.repository!.db.outbox).get())
              r.operationId: r.payload,
          };
          fixture.disconnected = false;
          fixture.central.cutAfter = 1;
          fixture.central.cutBudget = 1;
          await c.synchronize();
          expect(c.error, isNotNull);
          expect(fixture.central.results.length, 1);
          await c.repository!.db.close();
          c.dispose();
          c = make();
          await c.initialize();
          await c.checkAccess();
          await c.synchronize();
          expect(c.error, isNull);
          expect(fixture.central.results.length, 2);
          final rows = await c.repository!.db
              .select(c.repository!.db.outbox)
              .get();
          expect(rows.every((r) => r.status == 'confirmed'), true);
          expect({for (final r in rows) r.operationId: r.payload}, original);
          expect(
            (await c.repository!.db.select(c.repository!.db.localEffects).get())
                .every((e) => e.reflected),
            true,
          );
          expect(
            (await c.repository!.products('')).single.estimatedQuantity,
            199800,
          );
          expect((await c.repository!.customers('')).single.balanceCents, 8000);
          expect(fixture.central.queries, 2);
          expect(fixture.central.cuts, 1);
          await c.signOut();
        } finally {
          c.dispose();
          await server.close(force: true);
          fixture.central.close();
          await secrets.clean();
          await dir.delete(recursive: true);
        }
      });
    },
  );
}
