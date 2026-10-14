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

class InventoryApi extends FixtureApi {
  bool refreshPermissions = false;
  InventoryApi()
    : super(
        pages: [
          snapshot(
            products: [
              {...product(), 'classification': 'Semillas'},
              {
                ...product(id: '11', name: 'Abono'),
                'classification': 'Fertilizantes',
              },
            ],
          ),
        ],
      );

  @override
  Future<Map<String, dynamic>> request(
    String path, {
    Map<String, dynamic>? body,
    String? token,
    String method = 'POST',
  }) async {
    if (path == 'device/heartbeat' && refreshPermissions) {
      return {
        'authorized': true,
        'offlineLease': await signedLease(
          fixtureSession(device: device),
          pair: key,
          permissions: permissions,
        ),
      };
    }
    if (path.startsWith('inventory/') &&
        !disconnected &&
        deniedStatus == null) {
      calls.add(path);
      return {
        'page': 1,
        'hasMore': false,
        'movements': [
          {
            'id': '1',
            'type': 'Alta de inventario',
            'before': '0.00',
            'change': '10.00',
            'after': '10.00',
            'user': 'Operador',
            'date': '2026-10-08 10:00:00',
          },
        ],
      };
    }
    return super.request(path, body: body, token: token, method: method);
  }
}

void main() {
  for (final size in [const Size(360, 740), const Size(1200, 800)]) {
    for (final role in [
      'vendedor',
      'inventario',
      'admin',
      'adminEmpresa',
      'superAdmin',
    ]) {
      testWidgets('inventory and menus follow $role at ${size.width}', (
        tester,
      ) async {
        final inventory = role != 'vendedor';
        final sales = role != 'inventario';
        final api = InventoryApi()
          ..permissions = [
            'catalog.read',
            'product.search',
            'stock.read_estimated',
            if (inventory) ...['inventory.read', 'inventory.movements.read'],
            if (sales) ...[
              'sale.create',
              'sale.credit',
              'sale.history',
              'customer.read',
            ],
          ];
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
            ProviderScope(
              overrides: [appControllerProvider.overrideWithValue(c)],
              child: const AgrosysPosApp(),
            ),
          );
          await settle();
          await tester.tap(find.byTooltip('Menú'));
          await tester.pumpAndSettle();
          expect(
            find.widgetWithText(OutlinedButton, 'Inventario'),
            inventory ? findsOneWidget : findsNothing,
          );
          for (final label in ['Venta', 'Clientes', 'Historial']) {
            expect(
              find.widgetWithText(OutlinedButton, label),
              sales ? findsOneWidget : findsNothing,
            );
          }
          if (!inventory) {
            await expectLater(c.inventoryMovements('10'), throwsException);
            expect(api.calls.where((p) => p.startsWith('inventory/')), isEmpty);
            return;
          }
          await navigate(tester, 'Inventario');
          await settle();
          await tester.ensureVisible(
            find.widgetWithText(ChoiceChip, 'Semillas'),
          );
          await tester.pumpAndSettle();
          await tester.tap(find.widgetWithText(ChoiceChip, 'Semillas'));
          await settle();
          expect(
            find.byKey(const ValueKey('inventory-product-10')),
            findsOneWidget,
          );
          expect(
            find.byKey(const ValueKey('inventory-product-11')),
            findsNothing,
          );
          await tester.enterText(
            find.byKey(const Key('inventory-search')),
            'abono',
          );
          await tester.pump(const Duration(milliseconds: 160));
          await settle();
          expect(
            find.byKey(const ValueKey('inventory-product-10')),
            findsNothing,
          );
          await tester.enterText(
            find.byKey(const Key('inventory-search')),
            'maiz',
          );
          await tester.pump(const Duration(milliseconds: 160));
          await settle();
          await tester.tap(find.byKey(const ValueKey('inventory-product-10')));
          await settle();
          expect(find.text('Categoría: Semillas'), findsOneWidget);
          expect(find.text('Existencia estimada: 10.00'), findsOneWidget);
          await tester.ensureVisible(find.text('Consultar movimientos'));
          await tester.tap(find.text('Consultar movimientos'));
          await settle();
          expect(find.text('Alta de inventario · 10.00'), findsOneWidget);
          api.disconnected = true;
          await tester.runAsync(c.checkAccess);
          await settle();
          expect(
            find.textContaining('Sin conexión. Consulta el catálogo guardado'),
            findsOneWidget,
          );
          await tester.drag(
            find.byKey(const Key('inventory-detail-scroll')),
            const Offset(0, 300),
          );
          await tester.pumpAndSettle();
          await tester.scrollUntilVisible(
            find.byKey(const Key('inventory-movements-button')),
            80,
            scrollable: find.descendant(
              of: find.byKey(const Key('inventory-detail-scroll')),
              matching: find.byType(Scrollable),
            ),
          );
          await tester.pumpAndSettle();
          expect(
            tester
                .widget<OutlinedButton>(
                  find.byKey(const Key('inventory-movements-button')),
                )
                .onPressed,
            isNull,
          );
          // A verified role change removes the current inventory view and its menu.
          api.disconnected = false;
          api.refreshPermissions = true;
          api.permissions = [
            'catalog.read',
            'sale.create',
            'customer.read',
            'sale.history',
          ];
          await tester.runAsync(c.checkAccess);
          await settle();
          expect(c.canReadInventory, isFalse);
          expect(find.text('Categoría: Semillas'), findsNothing);
          await tester.tap(find.byTooltip('Menú'));
          await tester.pumpAndSettle();
          expect(
            find.widgetWithText(OutlinedButton, 'Inventario'),
            findsNothing,
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
      });
    }
  }
}
