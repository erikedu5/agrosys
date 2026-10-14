import 'package:agrosys_pos/app.dart';
import 'package:agrosys_pos/core/app_controller.dart';
import 'package:agrosys_pos/core/session_vault.dart';
import 'package:agrosys_pos/data/database.dart';
import 'package:agrosys_pos/features/sales/sale_domain.dart';
import 'package:drift/native.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:uuid/uuid.dart';

import 'support/fixtures.dart';
import 'support/navigation.dart';
import 'support/sync_server.dart';

void main() {
  for (final size in [const Size(360, 740), const Size(1200, 800)]) {
    testWidgets(
      'pending/conflict inbox keeps reason, filter and manual retry visible at ${size.width}',
      (tester) async {
        final api = SyncFixtureApi();
        final c = AppController(
          enableAutomaticSync: false,
          vault: SessionVault(MemorySecretStore()),
          apiFactory: (_) => api,
          openDatabase: (id) async => PosDatabase(NativeDatabase.memory(), id),
        );
        await tester.runAsync(() async {
          await c.initialize();
          await c.signIn(
            'https://pos.example/api/v1/pos',
            'qa@example.com',
            'password',
          );
          await c.selectBranch(c.branches.single);
          for (var i = 0; i < 2; i++) {
            final q = await c.sales!.quote('20', [const CartLine('10', 100)]);
            final saved = await c.completeSale(
              SaleRequest(
                saleId: const Uuid().v4(),
                customerId: '20',
                type: 'Contado',
                lines: [const CartLine('10', 100)],
                initialCents: 0,
                receivedCents: 5000,
                expectedPriceFingerprint: q.priceFingerprint,
              ),
            );
            if (i == 0) {
              api.central.rejected[saved!.sale.operationId] = 'PRICE_CHANGED';
            } else {
              api.central.retryOnce.add(saved!.sale.operationId);
            }
          }
          await c.synchronize();
        });
        Future<void> settle() async {
          await tester.runAsync(
            () => Future<void>.delayed(const Duration(milliseconds: 50)),
          );
          await tester.pumpAndSettle();
        }

        try {
          await tester.binding.setSurfaceSize(size);
          await tester.pumpWidget(
            ProviderScope(
              overrides: [appControllerProvider.overrideWithValue(c)],
              child: const AgrosysPosApp(),
            ),
          );
          await settle();
          await navigate(tester, 'Historial');
          await settle();
          expect(find.text('Reintentar pendientes'), findsOneWidget);
          expect(find.textContaining('PRICE_CHANGED'), findsOneWidget);
          expect(
            find.textContaining('Pendiente de sincronizar'),
            findsOneWidget,
          );
          await tester.tap(find.byType(DropdownButton<String>));
          await tester.pumpAndSettle();
          await tester.tap(find.text('En revisión').last);
          await settle();
          expect(find.textContaining('PRICE_CHANGED'), findsOneWidget);
          expect(find.textContaining('Pendiente de sincronizar'), findsNothing);
          expect(tester.takeException(), isNull);
        } finally {
          await tester.pumpWidget(const SizedBox());
          await tester.runAsync(c.signOut);
          c.dispose();
          api.central.close();
          await tester.binding.setSurfaceSize(null);
        }
      },
    );
  }
}
