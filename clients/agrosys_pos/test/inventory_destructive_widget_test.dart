import 'package:agrosys_pos/core/app_controller.dart';
import 'package:agrosys_pos/core/session_vault.dart';
import 'package:agrosys_pos/data/database.dart';
import 'package:agrosys_pos/features/inventory/inventory_screen.dart';
import 'package:drift/native.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/fixtures.dart';
import 'support/inventory_server.dart';

void main() {
  for (final size in [const Size(360, 740), const Size(1200, 800)]) {
    for (final authorized in [false, true]) {
      testWidgets(
        'destructive confirmation, branch stock and permissions authorized=$authorized at ${size.width}',
        (tester) async {
          final api = InventoryServer(destructive: authorized);
          final c = AppController(
            enableAutomaticSync: false,
            vault: SessionVault(MemorySecretStore()),
            apiFactory: (_) => api,
            openDatabase: (id) async =>
                PosDatabase(NativeDatabase.memory(), id),
          );
          Future<void> settle() async {
            await tester.runAsync(
              () => Future<void>.delayed(const Duration(milliseconds: 50)),
            );
            await tester.pumpAndSettle();
          }

          Future<void> visible(String key, String scrollKey) async {
            await tester.scrollUntilVisible(
              find.byKey(ValueKey(key)),
              100,
              scrollable: find
                  .descendant(
                    of: find.byKey(ValueKey(scrollKey)),
                    matching: find.byType(Scrollable),
                  )
                  .first,
            );
            await tester.pumpAndSettle();
          }

          Future<void> action(String name) async {
            await visible('inventory-action-$name', 'inventory-detail-scroll');
            await tester.tap(find.byKey(ValueKey('inventory-action-$name')));
            await settle();
          }

          await tester.runAsync(() async {
            await c.initialize();
            await c.signIn(
              'https://pos.example/api/v1/pos',
              'qa@example.com',
              'password',
            );
            await c.selectBranch(c.branches.single);
          });
          try {
            await tester.binding.setSurfaceSize(size);
            await tester.pumpWidget(
              MaterialApp(
                home: Scaffold(
                  body: ListenableBuilder(
                    listenable: c,
                    builder: (_, _) => InventoryScreen(controller: c),
                  ),
                ),
              ),
            );
            await settle();
            await tester.tap(
              find.byKey(const ValueKey('inventory-product-10')),
            );
            await settle();
            if (!authorized) {
              expect(
                find.byKey(const ValueKey('inventory-action-delete')),
                findsNothing,
              );
              expect(
                find.byKey(const ValueKey('inventory-action-reset')),
                findsNothing,
              );
              return;
            }
            await action('reset');
            expect(find.text('Existencia confirmada: 10.00'), findsOneWidget);
            final save = find.byKey(const Key('inventory-destructive-save'));
            expect(tester.widget<FilledButton>(save).onPressed, isNull);
            await visible(
              'inventory-destructive-confirm',
              'inventory-destructive-scroll',
            );
            await tester.tap(
              find.byKey(const Key('inventory-destructive-confirm')),
            );
            await tester.pumpAndSettle();
            // Losing connectivity after confirmation still prevents sending it.
            c.online = false;
            c.notifyListeners();
            await tester.pumpAndSettle();
            await visible(
              'inventory-destructive-save',
              'inventory-destructive-scroll',
            );
            expect(tester.widget<FilledButton>(save).onPressed, isNull);
            expect(api.mutations, 0);
            c.online = true;
            c.notifyListeners();
            await tester.pumpAndSettle();
            api.stale = true;
            await tester.tap(save);
            await settle();
            expect(api.mutations, 0);
            expect(
              find.byKey(const Key('inventory-destructive-confirm')),
              findsNothing,
            );
            api.stale = false;
            await tester.tap(find.text('Recargar datos'));
            await settle();
            expect(tester.widget<FilledButton>(save).onPressed, isNull);
            await visible(
              'inventory-destructive-confirm',
              'inventory-destructive-scroll',
            );
            await tester.tap(
              find.byKey(const Key('inventory-destructive-confirm')),
            );
            await tester.pumpAndSettle();
            await visible(
              'inventory-destructive-save',
              'inventory-destructive-scroll',
            );
            await tester.tap(save);
            await settle();
            expect(api.mutations, 1);
            expect(api.products['10']!['serverQuantity'], '0.00');
            api.branchesWithStock = [
              {'id': '2', 'name': 'Sucursal Norte', 'quantity': '4.00'},
            ];
            await action('delete');
            expect(find.text('Sucursal Norte: 4.00'), findsOneWidget);
            expect(
              find.byKey(const Key('inventory-destructive-save')),
              findsNothing,
            );
            await tester.tap(find.text('Volver'));
            await settle();
            api.branchesWithStock = [];
            await action('delete');
            await visible(
              'inventory-destructive-confirm',
              'inventory-destructive-scroll',
            );
            await tester.tap(
              find.byKey(const Key('inventory-destructive-confirm')),
            );
            await tester.pumpAndSettle();
            await visible(
              'inventory-destructive-save',
              'inventory-destructive-scroll',
            );
            await tester.tap(save);
            await settle();
            expect(api.mutations, 2);
            expect(
              find.byKey(const ValueKey('inventory-product-10')),
              findsNothing,
            );
            expect(find.text('Producto eliminado.'), findsOneWidget);
            expect(tester.takeException(), isNull);
          } finally {
            await tester.pumpWidget(const SizedBox.shrink());
            await tester.binding.setSurfaceSize(null);
            c.dispose();
            await tester.runAsync(() => c.repository!.db.close());
          }
        },
      );
    }
  }
}
