import 'package:agrosys_pos/core/app_controller.dart';
import 'package:agrosys_pos/core/session_vault.dart';
import 'package:agrosys_pos/data/database.dart';
import 'package:agrosys_pos/features/inventory/inventory_screen.dart';
import 'package:drift/native.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/inventory_server.dart';
import 'support/fixtures.dart';
import 'support/navigation.dart';

void main() {
  for (final size in [const Size(360, 740), const Size(1200, 800)]) {
    for (final costs in [false, true]) {
      testWidgets(
        'create, edit, prices and stock with costs=$costs at ${size.width}',
        (tester) async {
          final api = InventoryServer(costs: costs);
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

          Future<void> visible(Finder finder) async {
            await tester.scrollUntilVisible(
              finder,
              100,
              scrollable: find
                  .descendant(
                    of: find.byKey(const Key('inventory-editor-scroll')),
                    matching: find.byType(Scrollable),
                  )
                  .first,
            );
            await tester.pumpAndSettle();
          }

          Future<void> field(String name, String value) async {
            final finder = find.byKey(ValueKey('inventory-field-$name'));
            await visible(finder);
            await tester.enterText(finder, value);
            await tester.pumpAndSettle();
          }

          Future<void> save() async {
            await visible(find.byKey(const Key('inventory-save')));
            await tester.tap(find.byKey(const Key('inventory-save')));
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
            await tester.tap(find.byKey(const Key('inventory-create')));
            await settle();
            await field('nombre', 'Nuevo insumo');
            await field('tamano', '2L');
            await visible(find.byKey(const ValueKey('category-null')));
            await tester.tap(find.byKey(const ValueKey('category-null')));
            await tester.pumpAndSettle();
            await tester.tap(find.text('Semillas').last);
            await tester.pumpAndSettle();
            await visible(find.byKey(const ValueKey('brand-null')));
            await tester.tap(find.byKey(const ValueKey('brand-null')));
            await tester.pumpAndSettle();
            await tester.tap(find.text('Marca de prueba').last);
            await tester.pumpAndSettle();
            await field('barcode', 'NEW-123');
            await field('ingrediente_activo', 'Activo');
            if (costs) {
              await field('precio_unitario', '40.00');
              await field('precio_publico', '60.10');
              await field('precio_preferencial_1', '55.00');
            } else {
              expect(
                find.byKey(const ValueKey('inventory-field-precio_unitario')),
                findsNothing,
              );
              await field('precio_publico', '60.00');
            }
            await save();
            expect(api.mutations, 1);
            expect(
              api.mutationBodies.single['precio_preferencial_1'],
              costs ? '55.00' : null,
            );
            expect(api.mutationBodies.single['precio_preferencial_2'], isNull);
            expect(api.mutationBodies.single.containsKey('ieps'), isFalse);
            expect(find.text('Cambios guardados.'), findsOneWidget);
            expect(
              api.mutationBodies.single.containsKey('precio_unitario'),
              costs,
            );
            await scrollInventoryTo(
              tester,
              find.byKey(const ValueKey('inventory-product-11')),
            );
            await tester.tap(
              find.byKey(const ValueKey('inventory-product-11')),
            );
            await settle();
            await tester.ensureVisible(
              find.byKey(const ValueKey('inventory-action-update')),
            );
            await tester.pumpAndSettle();
            await tester.tap(
              find.byKey(const ValueKey('inventory-action-update')),
            );
            await settle();
            await field('nombre', 'Insumo editado');
            await save();
            expect(api.products['11']!['name'], 'Insumo editado');
            await tester.ensureVisible(
              find.byKey(const ValueKey('inventory-action-prices')),
            );
            await tester.pumpAndSettle();
            await tester.tap(
              find.byKey(const ValueKey('inventory-action-prices')),
            );
            await settle();
            await field('precio_publico', '75.00');
            await field('precio_preferencial_2', '70.00');
            await save();
            expect(api.products['11']!['price'], '75.00');
            expect(api.products['11']!['prices'], {
              'publico': '75.00',
              'preferencial_1': costs ? '55.00' : '60.00',
              'preferencial_2': '70.00',
            });
            await tester.ensureVisible(
              find.byKey(const ValueKey('inventory-action-add')),
            );
            await tester.pumpAndSettle();
            await tester.tap(
              find.byKey(const ValueKey('inventory-action-add')),
            );
            await settle();
            await field('cantidad', '2.50');
            await save();
            expect(api.products['11']!['serverQuantity'], '2.50');
            expect(inventoryDetailValue('2.50'), findsOneWidget);
            if (costs) {
              await tester.ensureVisible(
                find.byKey(const ValueKey('inventory-action-prices')),
              );
              await tester.pumpAndSettle();
              await tester.tap(
                find.byKey(const ValueKey('inventory-action-prices')),
              );
              await settle();
              await visible(
                find.byKey(const ValueKey('inventory-field-precio_unitario')),
              );
              api.permissions!.remove('product.costs.manage');
              await tester.runAsync(() async {
                final lease = await signedLease(
                  fixtureSession(device: api.device),
                  pair: api.key,
                  permissions: api.permissions,
                );
                c.session = c.session!.copyWith(lease: lease);
                c.notifyListeners();
              });
              await settle();
              expect(
                find.byKey(const ValueKey('inventory-field-precio_unitario')),
                findsNothing,
              );
              await visible(find.text('Cancelar'));
              await tester.tap(find.text('Cancelar'));
              await settle();
            }
            api.disconnected = true;
            await tester.runAsync(c.checkAccess);
            await settle();
            await tester.ensureVisible(
              find.byKey(const ValueKey('inventory-action-add')),
            );
            await tester.pumpAndSettle();
            expect(
              tester
                  .widget<OutlinedButton>(
                    find.byKey(const ValueKey('inventory-action-add')),
                  )
                  .onPressed,
              isNull,
            );
            expect(tester.takeException(), isNull);
          } finally {
            await tester.pumpWidget(const SizedBox());
            c.dispose();
            await tester.runAsync(() async {
              await c.repository?.db.close();
            });
            await tester.binding.setSurfaceSize(null);
          }
        },
      );
    }
  }
}
