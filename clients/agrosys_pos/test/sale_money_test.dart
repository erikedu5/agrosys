import 'dart:convert';
import 'dart:io';

import 'package:agrosys_pos/core/models.dart';
import 'package:agrosys_pos/features/sales/sale_domain.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  final fixture = jsonDecode(
    File('../../tests/Fixtures/pos-sale-amounts.json').readAsStringSync(),
  ) as Map;
  for (final raw in fixture['cases'] as List) {
    final f = Map<String, dynamic>.from(raw as Map);
    test('AC-19/20/21/22 shared Dart/PHP/JS decimal fixture: ${f['name']}', () {
      final price = discountedUnitPrice(
        decimalUnits(f['price']),
        decimalUnits(f['discount']),
      );
      expect(decimalText(price), f['unitPrice']);
      final total = saleLineTotal(price, decimalUnits(f['quantity']));
      expect(decimalText(total), f['total']);
      final payment = SalePayment.calculate(
        type: f['saleType'],
        total: total,
        initial: decimalUnits(f['payment']),
        received: decimalUnits(f['payment']),
      );
      expect(decimalText(payment.appliedCents), f['payment']);
      expect(decimalText(payment.balanceCents), f['balance']);
      expect(payment.balanceCents == 0, f['paid']);
    });
  }
  test(
    'AC-21 invalid quantities, discounts, initial and cash cannot close',
    () {
      for (final q in [0, -1, maxQuantityUnits + 1]) {
        expect(() => saleLineTotal(100, q), throwsA(isA<SaleInputException>()));
      }
      expect(
        () => saleLineTotal(maxSaleCents, 200),
        throwsA(isA<SaleInputException>()),
      );
      for (final d in [-1, 10001]) {
        expect(
          () => discountedUnitPrice(100, d),
          throwsA(isA<SaleInputException>()),
        );
      }
      for (final initial in [-1, 10001]) {
        expect(
          () => SalePayment.calculate(
            type: 'Credito',
            total: 10000,
            initial: initial,
            received: 15000,
          ),
          throwsA(isA<SaleInputException>()),
        );
      }
      expect(
        () => SalePayment.calculate(
          type: 'Contado',
          total: 10000,
          initial: 0,
          received: 9999,
        ),
        throwsA(isA<SaleInputException>()),
      );
      expect(
        () => SalePayment.calculate(
          type: 'Contado',
          total: 10000,
          initial: 0,
          received: maxSaleCents + 1,
        ),
        throwsA(isA<SaleInputException>()),
      );
      for (final value in ['1e2', '-1', '1.001', 'NaN', double.nan, 1.2]) {
        expect(() => decimalUnits(value), throwsFormatException);
      }
    },
  );
  test('cash received never increases applied payment or customer account', () {
    final p = SalePayment.calculate(
      type: 'Contado',
      total: 10000,
      initial: 0,
      received: 15000,
    );
    expect(p.appliedCents, 10000);
    expect(p.changeCents, 5000);
    expect(p.balanceCents, 0);
    final c = SalePayment.calculate(
      type: 'Credito',
      total: 10000,
      initial: 3000,
      received: 5000,
    );
    expect(c.appliedCents, 3000);
    expect(c.changeCents, 2000);
    expect(c.balanceCents, 7000);
  });
}
