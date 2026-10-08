// Match the native API: integer cents, discounted unit price first, half-up.
const units = (value) => {
    const text = typeof value === 'number' && Number.isFinite(value)
        ? value.toFixed(8).replace(/0+$/, '').replace(/\.$/, '') : String(value);
    const match = /^(\d{1,8})(?:\.(\d{1,2}))?$/.exec(text);
    if (!match) throw new RangeError('Usa un decimal válido con hasta dos decimales.');
    return BigInt(match[1]) * 100n + BigInt((match[2] ?? '').padEnd(2, '0'));
};
const format = (value) => `${value / 100n}.${String(value % 100n).padStart(2, '0')}`;
export const validPosDecimal = (value) => {
    try { units(value); return true; } catch { return false; }
};
export const discountedPrice = (price, discount) => {
    const percentage = units(discount);
    if (percentage > 10000n) throw new RangeError('Descuento fuera de rango.');
    return format((units(price) * (10000n - percentage) + 5000n) / 10000n);
};
export const lineAmount = (price, quantity) => format((units(price) * units(quantity) + 50n) / 100n);
export const sumDecimals = (values) => format(values.reduce((sum, value) => sum + units(value), 0n));
