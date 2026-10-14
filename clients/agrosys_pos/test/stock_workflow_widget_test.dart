import 'package:agrosys_pos/core/app_controller.dart';
import 'package:agrosys_pos/core/session_vault.dart';
import 'package:agrosys_pos/data/database.dart';
import 'package:agrosys_pos/features/catalog/catalog_screen.dart';
import 'package:drift/native.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/fixtures.dart';
import 'support/workflow_server.dart';

void main() {
  for (final size in [const Size(360, 740), const Size(1200, 800)]) {
    for (final role in [
      'vendedor',
      'inventario',
      'admin',
      'adminEmpresa',
      'superAdmin',
    ]) {
      testWidgets('stock menus follow $role at ${size.width}', (tester) async {
        final api = WorkflowServer(
          purchasesAccess: role != 'inventario',
          transfersAccess: role != 'vendedor',
        );
        if (role == 'vendedor') {
          api.permissions!.removeWhere(
            (p) => p.startsWith('inventory.') || p.startsWith('product.'),
          );
        }
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
            MaterialApp(
              home: ListenableBuilder(
                listenable: c,
                builder: (_, _) => CatalogScreen(controller: c),
              ),
            ),
          );
          await settle();
          await tester.tap(find.byTooltip('Menú'));
          await tester.pumpAndSettle();
          expect(
            find.widgetWithText(OutlinedButton, 'Compras'),
            role == 'inventario' ? findsNothing : findsOneWidget,
          );
          expect(
            find.widgetWithText(OutlinedButton, 'Transferencias'),
            role == 'vendedor' ? findsNothing : findsOneWidget,
          );
          final label = role == 'inventario' ? 'Transferencias' : 'Compras';
          final menu = find.widgetWithText(OutlinedButton, label);
          await tester.ensureVisible(menu);
          await tester.tap(menu);
          await settle();
          expect(
            find.byKey(
              ValueKey(
                role == 'inventario' ? 'transfers-create' : 'purchases-create',
              ),
            ),
            findsOneWidget,
          );
          api.permissions!.removeWhere(
            (p) =>
                p.startsWith(role == 'inventario' ? 'transfer.' : 'purchase.'),
          );
          await tester.runAsync(c.checkAccess);
          await settle();
          expect(
            find.byKey(
              ValueKey(
                role == 'inventario' ? 'transfers-create' : 'purchases-create',
              ),
            ),
            findsNothing,
          );
          expect(tester.takeException(), isNull);
        } finally {
          await tester.pumpWidget(const SizedBox.shrink());
          await tester.binding.setSurfaceSize(null);
          c.dispose();
          await tester.runAsync(() => c.repository!.db.close());
        }
      });
    }
    testWidgets(
      'purchase, payment, transfer and destination receipt from menus at ${size.width}',
      (tester) async {
        final api = WorkflowServer();
        final c = AppController(
          enableAutomaticSync: false,
          vault: SessionVault(MemorySecretStore()),
          apiFactory: (_) => api,
          openDatabase: (id) async => PosDatabase(NativeDatabase.memory(), id),
        );
        Future<void> settle() async {
          await tester.runAsync(
            () => Future<void>.delayed(const Duration(milliseconds: 50)),
          );
          await tester.pumpAndSettle();
        }

        Future<void> visible(String key, String scroll) async {
          await tester.scrollUntilVisible(
            find.byKey(ValueKey(key)),
            100,
            scrollable: find
                .descendant(
                  of: find.byKey(ValueKey(scroll)),
                  matching: find.byType(Scrollable),
                )
                .first,
          );
          await tester.pumpAndSettle();
        }

        Future<void> field(String name, String text) async {
          await visible('stock-field-$name', 'stock-editor-scroll');
          await tester.enterText(
            find.byKey(ValueKey('stock-field-$name')),
            text,
          );
          await tester.pumpAndSettle();
        }

        Future<void> navigate(String label) async {
          await tester.tap(find.byTooltip('Menú'));
          await tester.pumpAndSettle();
          final menu = find.widgetWithText(OutlinedButton, label);
          await tester.ensureVisible(menu);
          await tester.tap(menu);
          await settle();
        }

        Future<void> product({bool purchase = false}) async {
          await visible('stock-product-null', 'stock-editor-scroll');
          await tester.tap(find.byKey(const ValueKey('stock-product-null')));
          await tester.pumpAndSettle();
          await tester.tap(find.text('Semillas de maíz · 1 kg').last);
          await tester.pumpAndSettle();
          await field('quantity', purchase ? '1.25' : '2.50');
          if (purchase) await field('cost', '20.10');
          await visible('stock-add-item', 'stock-editor-scroll');
          await tester.tap(find.byKey(const Key('stock-add-item')));
          await tester.pumpAndSettle();
        }

        Future<void> save() async {
          await visible('stock-save', 'stock-editor-scroll');
          await tester.tap(find.byKey(const Key('stock-save')));
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
              home: ListenableBuilder(
                listenable: c,
                builder: (_, _) => CatalogScreen(controller: c),
              ),
            ),
          );
          await settle();
          await navigate('Compras');
          await tester.tap(find.byKey(const Key('purchases-create')));
          await settle();
          await field('supplier', 'Proveedor de prueba');
          await product(purchase: true);
          await field('payment', '5.00');
          await save();
          expect(api.purchases['1']!['total'], '25.13');
          expect(api.purchases['1']!['debt'], '20.13');
          await tester.tap(find.byKey(const ValueKey('stock-document-1')));
          await settle();
          await visible('stock-payment-amount', 'stock-detail-scroll');
          await tester.enterText(
            find.byKey(const Key('stock-payment-amount')),
            '10.00',
          );
          await visible('stock-document-action', 'stock-detail-scroll');
          await tester.tap(find.byKey(const Key('stock-document-action')));
          await tester.pumpAndSettle();
          await tester.tap(find.text('Cancelar'));
          await settle();
          expect(api.mutations, 1);
          await tester.tap(find.byKey(const Key('stock-document-action')));
          await tester.pumpAndSettle();
          await tester.tap(find.byKey(const Key('stock-confirm-write')));
          await settle();
          expect(api.purchases['1']!['debt'], '10.13');
          await navigate('Transferencias');
          await tester.tap(find.byKey(const Key('transfers-create')));
          await settle();
          await visible('stock-destination-null', 'stock-editor-scroll');
          await tester.tap(
            find.byKey(const ValueKey('stock-destination-null')),
          );
          await tester.pumpAndSettle();
          await tester.tap(find.text('Norte').last);
          await tester.pumpAndSettle();
          await product();
          await save();
          await tester.tap(find.byKey(const ValueKey('stock-document-1')));
          await settle();
          expect(
            find.byKey(const Key('stock-document-action')),
            findsNothing,
          ); // Origin cannot receive.
          expect(api.products['10']!['serverQuantity'], '11.25');
          await tester.tap(find.text('Volver a Transferencias'));
          await settle();
          api.transfers['99'] = api.incoming();
          await tester.tap(find.byTooltip('Actualizar'));
          await settle();
          await tester.tap(find.byKey(const ValueKey('stock-document-99')));
          await settle();
          await visible('stock-document-action', 'stock-detail-scroll');
          c.online = false;
          c.notifyListeners();
          await tester.pumpAndSettle();
          expect(
            tester
                .widget<FilledButton>(
                  find.byKey(const Key('stock-document-action')),
                )
                .onPressed,
            isNull,
          );
          c.online = true;
          c.notifyListeners();
          await tester.pumpAndSettle();
          await tester.tap(find.byKey(const Key('stock-document-action')));
          await tester.pumpAndSettle();
          await tester.tap(find.byKey(const Key('stock-confirm-write')));
          await settle();
          expect(api.transfers['99']!['status'], 'completado');
          expect(api.products['10']!['serverQuantity'], '13.75');
          expect(api.mutations, 4);
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
