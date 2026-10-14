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
