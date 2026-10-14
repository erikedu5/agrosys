import 'package:agrosys_pos/core/app_controller.dart';
import 'package:agrosys_pos/core/session_vault.dart';
import 'package:agrosys_pos/data/database.dart';
import 'package:agrosys_pos/features/cash/cash_screen.dart';
import 'package:agrosys_pos/features/catalog/catalog_screen.dart';
import 'package:drift/native.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/cash_server.dart';
import 'support/fixtures.dart';
import 'support/navigation.dart';

void main() {
  for (final size in [const Size(360, 740), const Size(1200, 800)]) {
    testWidgets(
      'cash opens as a sale modal and closing preserves the cart at ${size.width}',
      (tester) async {
        final api = CashServer();
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
            () => Future<void>.delayed(const Duration(milliseconds: 60)),
          );
          await tester.pumpAndSettle();
        }

        Finder field(String label) => find.byWidgetPredicate(
          (w) => w is TextField && w.decoration?.labelText == label,
        );
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
          expect(find.widgetWithText(OutlinedButton, 'Caja'), findsNothing);
          await navigate(tester, 'Venta');
          await settle();
          expect(c.canCompleteSale, isFalse);
          final add = find.byTooltip('Agregar Semillas de maíz');
          final addButton = find.descendant(
            of: add,
            matching: find.byType(InkWell),
          );
          expect(tester.widget<InkWell>(addButton).onTap, isNull);
          await tester.tap(find.byKey(const Key('sale-cash-button')));
          await settle();
          expect(find.byType(Dialog), findsOneWidget);
          await tester.enterText(field('Fondo inicial (\$)'), '200.00');
          final open = find.widgetWithText(FilledButton, 'Abrir caja').last;
          await tester.ensureVisible(open);
          await tester.pumpAndSettle();
          await tester.tap(open);
          await settle();
          expect(c.canCompleteSale, isTrue);
          await tester.tap(find.byTooltip('Volver a la venta'));
          await settle();
          await tester.tap(add);
          await settle();
          await tester.tap(find.byKey(const Key('sale-cash-button')));
          await settle();
          await tester.scrollUntilVisible(
            field('Efectivo contado (\$)'),
            300,
            scrollable: find
                .descendant(
                  of: find.byType(CashScreen),
                  matching: find.byType(Scrollable),
                )
                .first,
          );
          await tester.enterText(field('Efectivo contado (\$)'), '200.00');
          final confirm = find.byType(CheckboxListTile);
          await tester.ensureVisible(confirm);
          await tester.tap(confirm);
          await tester.pumpAndSettle();
          final close = find.widgetWithText(
            FilledButton,
            'Guardar corte y cerrar caja',
          );
          await tester.ensureVisible(close);
          await tester.tap(close);
          await settle();
          expect(c.canCompleteSale, isFalse);
          await tester.tap(find.byTooltip('Volver a la venta'));
          await settle();
          expect(
            find.widgetWithText(FilledButton, 'Abrir caja'),
            findsOneWidget,
          );
          if (size.width < 900) {
            expect(find.text('Ver venta · 1'), findsOneWidget);
          } else {
            expect(find.text('Carrito · 1 productos'), findsOneWidget);
          }
          expect(tester.takeException(), isNull);
        } finally {
          await tester.pumpWidget(const SizedBox.shrink());
          c.dispose();
          await c.repository!.db.close();
          await tester.binding.setSurfaceSize(null);
        }
      },
    );
    testWidgets('opening, withdrawal, cut and history fit ${size.width}', (
      tester,
    ) async {
      final api = CashServer();
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

      Future<void> tapButton(String text) async {
        final target = find.widgetWithText(FilledButton, text);
        await tester.ensureVisible(target);
        await tester.tap(target);
        await settle();
      }

      Finder field(String label) => find.byWidgetPredicate(
        (w) => w is TextField && w.decoration?.labelText == label,
      );
      try {
        await tester.binding.setSurfaceSize(size);
        await tester.pumpWidget(
          MaterialApp(
            home: Scaffold(body: CashScreen(controller: c)),
          ),
        );
        await settle();
        await tester.enterText(field('Fondo inicial (\$)'), '200.00');
        await tapButton('Abrir caja');
        expect(find.text('Efectivo esperado: \$200.00'), findsOneWidget);
        await tester.ensureVisible(field('Importe (\$)'));
        await tester.enterText(field('Importe (\$)'), '50.00');
        await tester.enterText(field('Motivo'), 'Retiro de caja');
        await tapButton('Guardar movimiento');
        expect(api.current!['expected'], '150.00');
        await tester.ensureVisible(field('Efectivo contado (\$)'));
        await tester.enterText(field('Efectivo contado (\$)'), '149.00');
        await tester.enterText(
          field('Observaciones (obligatorias si hay diferencia)'),
          'Faltante de un peso',
        );
        final confirmation = find.byType(CheckboxListTile);
        await tester.ensureVisible(confirmation);
        await tester.tap(confirmation);
        await tester.pumpAndSettle();
        await tapButton('Guardar corte y cerrar caja');
        expect(api.current, isNull);
        expect(api.history.single['difference'], '-1.00');
        expect(find.byTooltip('Imprimir corte'), findsOneWidget);
        expect(tester.takeException(), isNull);
      } finally {
        await tester.pumpWidget(const SizedBox.shrink());
        c.dispose();
        await c.repository!.db.close();
        await tester.binding.setSurfaceSize(null);
      }
    });
  }
}
