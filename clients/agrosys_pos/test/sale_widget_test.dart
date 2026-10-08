import 'package:agrosys_pos/app.dart';
import 'package:agrosys_pos/core/app_controller.dart';
import 'package:agrosys_pos/core/session_vault.dart';
import 'package:agrosys_pos/data/database.dart';
import 'package:drift/native.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/fixtures.dart';
import 'support/navigation.dart';

void main() {
  for (final size in [const Size(360, 740), const Size(1200, 800)]) {
    for (final type in ['Contado', 'Credito']) {
      testWidgets(
        'AC-04/15 responsive $type checkout, ticket and history ${size.width}',
        (tester) async {
          final api = FixtureApi()..salesEnabled = true;
          final controller = AppController(
            enableAutomaticSync: false,
            vault: SessionVault(MemorySecretStore()),
            openDatabase: (id) async =>
                PosDatabase(NativeDatabase.memory(), id),
            apiFactory: (_) => api,
          );
          await tester.runAsync(() async {
            await controller.initialize();
            await controller.signIn(
              'https://pos.example/api/v1/pos',
              'qa@example.com',
              'not-stored',
            );
            await controller.selectBranch(controller.branches.single);
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
                overrides: [
                  appControllerProvider.overrideWithValue(controller),
                ],
                child: const AgrosysPosApp(),
              ),
            );
            await settle();
            await navigate(tester, 'Venta');
            await settle();
            expect(find.text('Punto de venta'), findsOneWidget);
            await tester.tap(find.byTooltip('Agregar Semillas de maíz'));
            await settle();
            await tester.tap(find.byTooltip('Agregar Semillas de maíz'));
            await settle();
            await navigate(tester, 'Productos');
            await settle();
            await navigate(tester, 'Venta');
            await settle();
            if (size.width < 900) {
              await tester.tap(find.text('Ver venta · 1'));
              await settle();
            }
            expect(find.text('Carrito · 1 productos'), findsOneWidget);
            if (type == 'Credito') {
              final saleType = find.byType(DropdownButtonFormField<String>);
              await tester.ensureVisible(saleType);
              await tester.pumpAndSettle();
              await tester.tap(saleType);
              await tester.pumpAndSettle();
              await tester.tap(find.text('Crédito').last);
              await tester.pumpAndSettle();
            }
            await tester.tap(find.text('Cobrar'));
            await settle();
            if (type == 'Credito') {
              final initial = find.widgetWithText(
                TextField,
                'Abono inicial (0 hasta el total)',
              );
              await tester.enterText(initial, '30.00');
            }
            final received = find.widgetWithText(
              TextField,
              'Efectivo recibido',
            );
            await tester.enterText(
              received,
              type == 'Credito' ? '50.00' : '150.00',
            );
            await tester.pump();
            final finish = find.text('Finalizar venta');
            await tester.ensureVisible(finish);
            await tester.tap(finish);
            await settle();
            expect(find.text('Comprobante de venta'), findsOneWidget);
            expect(find.textContaining('TOTAL: \$95.00'), findsOneWidget);
            expect(
              find.textContaining(
                type == 'Credito' ? 'Cambio: \$20.00' : 'Cambio: \$55.00',
              ),
              findsOneWidget,
            );
            expect(tester.takeException(), isNull);
            await tester.tap(find.text('Cerrar'));
            await settle();
            await navigate(tester, 'Historial');
            await settle();
            expect(find.text('José Pérez · \$95.00'), findsOneWidget);
            expect(find.text('Eliminar venta'), findsNothing);
            await tester.runAsync(() async {
              expect((await controller.sales!.history()).length, 1);
              expect(
                (await controller.repository!.products(''))
                    .single
                    .estimatedQuantity,
                800,
              );
              expect(
                (await controller.repository!.customers(''))
                    .single
                    .balanceCents,
                type == 'Credito' ? 10500 : 4000,
              );
            });
            expect(tester.takeException(), isNull);
          } finally {
            await tester.pumpWidget(const SizedBox());
            controller.dispose();
            await tester.runAsync(() async {
              await controller.repository?.db.close();
            });
            await tester.binding.setSurfaceSize(null);
          }
        },
      );
    }
  }
}
