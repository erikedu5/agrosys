import 'package:agrosys_pos/core/app_controller.dart';
import 'package:agrosys_pos/core/pos_api.dart';
import 'package:agrosys_pos/core/session_vault.dart';
import 'package:agrosys_pos/data/database.dart';
import 'package:agrosys_pos/features/auth/access_screen.dart';
import 'package:drift/native.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/fixtures.dart';

class ReplacementApi extends FixtureApi {
  int confirmedCalls = 0;
  @override
  Future<Map<String, dynamic>> request(
    String path, {
    Map<String, dynamic>? body,
    String? token,
    String method = 'POST',
  }) async {
    if (path == 'devices/activate') {
      if (body?['close_other_sessions'] != true) {
        throw ApiFailure(
          409,
          'Confirma el cierre de las demás sesiones.',
          code: 'DEVICE_SESSION_CONFIRMATION_REQUIRED',
        );
      }
      confirmedCalls++;
    }
    return super.request(path, body: body, token: token, method: method);
  }
}

void main() {
  testWidgets(
    'replacing sessions requires explicit confirmation; cancel sends nothing',
    (tester) async {
      final api = ReplacementApi();
      final controller = AppController(
        vault: SessionVault(MemorySecretStore()),
        enableAutomaticSync: false,
        apiFactory: (_) => api,
        openDatabase: (id) async => PosDatabase(NativeDatabase.memory(), id),
      );
      try {
        await tester.runAsync(() async {
          await controller.initialize();
          await controller.signIn(
            'https://pos.example/api/v1/pos',
            'qa@example.com',
            'password',
          );
          await controller.selectBranch(controller.branches.single);
        });
        expect(controller.deviceSessionConfirmationRequired, isTrue);
        expect(controller.session!.lease, isNull);
        expect(api.confirmedCalls, 0);
        await tester.binding.setSurfaceSize(const Size(1000, 1000));
        await tester.pumpWidget(
          MaterialApp(
            home: ListenableBuilder(
              listenable: controller,
              builder: (_, _) => AccessScreen(controller: controller),
            ),
          ),
        );
        final replace = find.text('Cerrar las demás sesiones y entrar');
        await tester.tap(replace);
        await tester.pumpAndSettle();
        expect(find.byType(AlertDialog), findsOneWidget);
        await tester.tap(find.text('Cancelar'));
        await tester.pumpAndSettle();
        expect(api.confirmedCalls, 0);
        expect(controller.deviceSessionConfirmationRequired, isTrue);
        await tester.tap(replace);
        await tester.pumpAndSettle();
        await tester.runAsync(() async {
          await tester.tap(find.text('Cerrar y entrar'));
          await Future<void>.delayed(const Duration(milliseconds: 200));
        });
        await tester.pumpAndSettle();
        expect(api.confirmedCalls, 1);
        expect(controller.deviceSessionConfirmationRequired, isFalse);
        expect(controller.session!.lease, isNotNull);
        expect(controller.canConsult, isTrue);
      } finally {
        await tester.pumpWidget(const SizedBox.shrink());
        controller.dispose();
        await controller.repository?.db.close();
        await tester.binding.setSurfaceSize(null);
      }
    },
  );
}
