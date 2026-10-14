import 'dart:convert';
import 'dart:io';

import 'package:agrosys_pos/app.dart';
import 'package:agrosys_pos/core/app_controller.dart';
import 'package:agrosys_pos/core/models.dart';
import 'package:agrosys_pos/core/session_vault.dart';
import 'package:agrosys_pos/data/database.dart';
import 'package:agrosys_pos/features/sales/sale_domain.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';
import 'package:path_provider/path_provider.dart';
import 'package:uuid/uuid.dart';

import '../test/support/fixtures.dart';
import '../test/support/navigation.dart';

class IsolatedPlatformSecrets implements SecretStore {
  final delegate = PlatformSecretStore();
  final prefix = 'qa_${const Uuid().v4()}_';
  final keys = <String>{};
  @override
  Future<String?> read(String key) => delegate.read('$prefix$key');
  @override
  Future<void> write(String key, String value) {
    keys.add(key);
    return delegate.write('$prefix$key', value);
  }

  @override
  Future<void> delete(String key) => delegate.delete('$prefix$key');
  Future<void> clean() async {
    for (final key in keys) {
      await delete(key);
    }
  }
}

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'AC-02/03/04 native secrets, cash and credit survive offline reopen',
    (tester) async {
      final secrets = IsolatedPlatformSecrets();
      final directory = await Directory(
        '${(await getTemporaryDirectory()).path}/pos-qa-${const Uuid().v4()}',
      ).create(recursive: true);
      final fixture = FixtureApi()..salesEnabled = true;
      final server = await HttpServer.bind(InternetAddress.loopbackIPv4, 0);
      final url = 'http://127.0.0.1:${server.port}/api/v1/pos';
      server.listen((request) async {
        try {
          final body = object(
            jsonDecode(await utf8.decoder.bind(request).join()),
          );
          final response = await fixture.request(
            request.uri.path.replaceFirst('/api/v1/pos/', ''),
            body: body,
            token: request.headers.value('Authorization'),
          );
          request.response.headers.contentType = ContentType.json;
          request.response.write(jsonEncode(response));
          await request.response.close();
        } catch (_) {
          request.response.statusCode = 500;
          await request.response.close();
        }
      });
      Future<PosDatabase> open(String id) =>
          PosDatabase.open(id, storageDirectory: directory);
      AppController makeController() => AppController(
        enableAutomaticSync: false,
        vault: SessionVault(secrets),
        openDatabase: open,
      );
      var controller = makeController();
      try {
        await tester.runAsync(() async {
          await controller.initialize();
          await controller.signIn(
            url,
            'qa@example.com',
            'not-persisted-password',
          );
          await controller.selectBranch(controller.branches.single);
        });
        expect(controller.error, isNull);
        expect(controller.canConsult, isTrue);
        final context = controller.session!.contextId;
        await tester.pumpWidget(
          ProviderScope(
            overrides: [appControllerProvider.overrideWithValue(controller)],
            child: const AgrosysPosApp(),
          ),
        );
        await tester.pumpAndSettle();
        expect(find.text('Semillas de maíz'), findsOneWidget);
        await tester.pumpWidget(const SizedBox());
        await tester.runAsync(() async {
          await server.close(force: true);
          await controller.repository!.db.close();
        });
        controller.dispose();
        controller = makeController();
        await tester.runAsync(() async {
          await controller.initialize();
          await controller.checkAccess();
        });
        expect(controller.session!.contextId, context);
        expect(controller.canConsult, isTrue);
        expect(controller.online, isFalse);
        await tester.pumpWidget(
          ProviderScope(
            overrides: [appControllerProvider.overrideWithValue(controller)],
            child: const AgrosysPosApp(),
          ),
        );
        await tester.pumpAndSettle();
        expect(find.text('Semillas de maíz'), findsOneWidget);
        await navigate(tester, 'Clientes');
        await tester.pumpAndSettle();
        expect(find.text('José Pérez'), findsOneWidget);
        await tester.pumpWidget(const SizedBox());
        await tester.runAsync(() async {
          for (final type in ['Contado', 'Credito']) {
            final quote = await controller.sales!.quote('20', [
              const CartLine('10', 200),
            ]);
            final saved = await controller.completeSale(
              SaleRequest(
                saleId: const Uuid().v4(),
                customerId: '20',
                type: type,
                lines: [const CartLine('10', 200)],
                initialCents: type == 'Credito' ? 3000 : 0,
                receivedCents: type == 'Credito' ? 5000 : 15000,
                expectedPriceFingerprint: quote.priceFingerprint,
              ),
            );
            expect(saved, isNotNull);
            expect(controller.error, isNull);
          }
          expect(
            (await controller.repository!.products(''))
                .single
                .estimatedQuantity,
            600,
          );
          expect(
            (await controller.repository!.customers('')).single.balanceCents,
            10500,
          );
          await controller.repository!.db.close();
        });
        controller.dispose();
        controller = makeController();
        await tester.runAsync(() async {
          await controller.initialize();
          await controller.checkAccess();
          expect(controller.canSell, isTrue);
          final history = await controller.sales!.history();
          expect(history.length, 2);
          expect(
            (await controller.repository!.db
                    .select(controller.repository!.db.outbox)
                    .get())
                .length,
            2,
          );
          expect(
            (await controller.repository!.products(''))
                .single
                .estimatedQuantity,
            600,
          );
          expect(
            (await controller.repository!.customers('')).single.balanceCents,
            10500,
          );
          expect(
            (await controller.sales!.receipt(history.first.id))
                .sale
                .balanceCents,
            6500,
          );
          await controller.signOut();
        });
        expect(await secrets.read('active_session'), isNull);
        expect(
          await File('${directory.path}/contexts/$context.sqlite').exists(),
          isTrue,
        );
      } finally {
        await tester.pumpWidget(const SizedBox());
        await tester.runAsync(() async {
          if (controller.repository != null) {
            await controller.repository!.db.close();
          }
          await server.close(force: true);
          await secrets.clean();
          await directory.delete(recursive: true);
        });
        controller.dispose();
      }
    },
  );
}
