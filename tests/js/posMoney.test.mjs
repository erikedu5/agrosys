import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { discountedPrice, lineAmount, sumDecimals, validPosDecimal } from '../../resources/js/utils/posMoney.js';
const fixtures = JSON.parse(readFileSync(new URL('../Fixtures/pos-sale-amounts.json', import.meta.url)));
for (const fixture of fixtures.cases) {
    test(`Web/native shared rounding: ${fixture.name}`, () => {
        const price = discountedPrice(fixture.price, fixture.discount);
        assert.equal(price, fixture.unitPrice);
        assert.equal(lineAmount(price, fixture.quantity), fixture.total);
    });
}
test('decimal sums and quantity additions avoid floating point drift', () => {
    assert.equal(sumDecimals(['0.10', '0.20']), '0.30');
    assert.equal(lineAmount('0.05', 0.1 + 0.2), '0.02');
});
test('invalid monetary input is rejected before calculations', () => {
    for (const value of ['1e309', Infinity, NaN, '-1.00', '1.001', null]) {
        assert.equal(validPosDecimal(value), false);
        assert.throws(() => lineAmount(value, '1.00'), RangeError);
    }
    assert.throws(() => discountedPrice('10.00', '100.01'), RangeError);
});
