import 'package:agrosys_pos/core/app_controller.dart';
import 'package:agrosys_pos/core/session_vault.dart';
import 'package:agrosys_pos/data/database.dart';
import 'package:agrosys_pos/features/inventory/inventory_dose_editor.dart';
import 'package:agrosys_pos/features/inventory/inventory_screen.dart';
import 'package:drift/native.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/inventory_server.dart';
import 'support/fixtures.dart';

void main() {
  test('size text proposes content and unit; decimals print without zeros', () {
    expect(parseSize('1000ml'), ('1000', 'ml'));
    expect(parseSize(' 1,5 L '), ('1.5', 'L'));
    expect(parseSize('Bolsa'), ('', ''));
    expect(plainDecimal(25000), '250');
    expect(plainDecimal(250), '2.5');
    expect(plainDecimal(1), '0.01');
  });

  for (final size in [const Size(360, 740), const Size(1200, 800)]) {
    testWidgets(
      'dose removes one unit, requires accepting the loss and adds the measures at ${size.width}',
      (tester) async {
        final api = InventoryServer(costs: false);
        api.products['10']!['size'] = '1000 ml';
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

        Future<void> visible(Finder finder) async {
          await tester.scrollUntilVisible(
            finder,
            100,
            scrollable: find
                .descendant(
                  of: find.byKey(const Key('inventory-dose-scroll')),
                  matching: find.byType(Scrollable),
                )
                .first,
          );
          await tester.pumpAndSettle();
        }

        Future<String> text(String name) async {
          await visible(find.byKey(ValueKey('dose-field-$name')));
          return tester
              .widget<TextField>(find.byKey(ValueKey('dose-field-$name')))
              .controller!
              .text;
        }

        Future<bool> canSave() async {
          await visible(find.byKey(const Key('dose-save')));
          return tester
                  .widget<FilledButton>(find.byKey(const Key('dose-save')))
                  .onPressed !=
              null;
        }

        Future<void> enter(String name, String value) async {
          // Los campos de medida están arriba: vuelve al inicio del formulario.
          await tester.drag(
            find.byKey(const Key('inventory-dose-scroll')),
            const Offset(0, 3000),
          );
          await tester.pumpAndSettle();
          await visible(find.byKey(ValueKey('dose-field-$name')));
          await tester.enterText(
            find.byKey(ValueKey('dose-field-$name')),
            value,
          );
          await tester.pumpAndSettle();
        }

        Future<void> acceptLoss() async {
          await visible(find.byKey(const Key('dose-accept-loss')));
          await tester.tap(find.byKey(const Key('dose-accept-loss')));
          await tester.pumpAndSettle();
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
          await tester.tap(find.byKey(const ValueKey('inventory-product-10')));
          await settle();
          await tester.ensureVisible(
            find.byKey(const ValueKey('inventory-action-dose')),
          );
          await tester.pumpAndSettle();
          await tester.tap(find.byKey(const ValueKey('inventory-action-dose')));
          await settle();
          expect(await text('contenido'), '1000');
          expect(await text('unidad'), 'ml');

          await enter('medida', '300');
          expect(
            find.text(
              'Salen 3 medidas de 300 ml en "Medida Semillas de maíz".',
            ),
            findsOneWidget,
          );
          // Precio del envase repartido entre las medidas (50.00, 47.50 y 45.00 / 3).
          expect(await text('precio_publico'), '16.67');
          expect(await text('precio_preferencial_1'), '15.83');
          expect(await text('precio_preferencial_2'), '15.00');
          await visible(find.byKey(const Key('dose-leftover')));
          expect(
            find.textContaining('Sobran 100 ml que no completan una medida'),
            findsOneWidget,
          );
          expect(await canSave(), isFalse);
          await acceptLoss();
          expect(await canSave(), isTrue);

          // Cambiar la medida vuelve a exigir la confirmación.
          await enter('medida', '250');
          expect(find.byKey(const Key('dose-leftover')), findsNothing);
          expect(await canSave(), isTrue);
          await enter('medida', '300');
          expect(await canSave(), isFalse);
          await acceptLoss();

          await visible(find.byKey(const Key('dose-save')));
          await tester.tap(find.byKey(const Key('dose-save')));
          await settle();
          expect(api.mutations, 1);
          final body = api.mutationBodies.single;
          expect(body['contenido'], '1000.00');
          expect(body['medida'], '300.00');
          expect(body['unidad'], 'ml');
          expect(body['acepta_perdida'], isTrue);
          expect(body['version'], 'a' * 64);
          expect(
            find.text('Se agregaron 3 medidas a "Medida Semillas de maíz".'),
            findsOneWidget,
          );
          final target = api.products.values.singleWhere(
            (p) => p['name'] == 'Medida Semillas de maíz',
          );
          expect(target['serverQuantity'], '3.00');
          expect(api.products['10']!['serverQuantity'], '9.00');
          // La sincronización posterior trae la medida al catálogo local.
          expect(
            (await tester.runAsync(() => c.repository!.products('Medida')))!
                .single
                .estimatedQuantity,
            300,
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

  testWidgets('dose is disabled without a full unit in stock', (tester) async {
    final api = InventoryServer(costs: false);
    api.products['10']!['serverQuantity'] = '0.50';
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
    try {
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
      await tester.runAsync(
        () => Future<void>.delayed(const Duration(milliseconds: 50)),
      );
      await tester.pumpAndSettle();
      await tester.tap(find.byKey(const ValueKey('inventory-product-10')));
      await tester.pumpAndSettle();
      expect(
        tester
            .widget<OutlinedButton>(
              find.byKey(const ValueKey('inventory-action-dose')),
            )
            .onPressed,
        isNull,
      );
    } finally {
      await tester.pumpWidget(const SizedBox());
      c.dispose();
      await tester.runAsync(() async {
        await c.repository?.db.close();
      });
    }
  });
}
