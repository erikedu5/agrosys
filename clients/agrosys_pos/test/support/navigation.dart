import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

Future<void> navigate(WidgetTester tester, String label) async {
  final target = find.widgetWithText(OutlinedButton, label);
  if (target.evaluate().isEmpty) {
    await tester.tap(find.byTooltip('Menú'));
    await tester.pumpAndSettle();
  }
  await tester.tap(target.last);
  await tester.pumpAndSettle();
}

/// Desplaza el listado de inventario (tarjetas bajo demanda) hasta [target].
/// `delta` negativo regresa hacia el encabezado (búsqueda y filtros).
Future<void> scrollInventoryTo(
  WidgetTester tester,
  Finder target, {
  double delta = 120,
}) async {
  await tester.scrollUntilVisible(
    target,
    delta,
    // El primero es el del listado; los demás son las filas de filtros.
    scrollable: find
        .descendant(
          of: find.byKey(const Key('inventory-list-scroll')),
          matching: find.byType(Scrollable),
        )
        .first,
  );
  await tester.pumpAndSettle();
}

/// Valor mostrado en la ficha del producto (RecordCard de detalle).
Finder inventoryDetailValue(String value) => find.descendant(
  of: find.byKey(const Key('inventory-detail')),
  matching: find.text(value),
);
