import 'package:flutter_test/flutter_test.dart';
import 'package:agrosys_pos/core/models.dart';

void main() {
  test('business decimals use integer units and never double', () {
    expect(decimalUnits('100.30'), 10030);
    expect(decimalText(-7030), '-70.30');
    expect(() => decimalUnits(100.30), throwsFormatException);
    expect(() => decimalUnits('1e309'), throwsFormatException);
  });
}
