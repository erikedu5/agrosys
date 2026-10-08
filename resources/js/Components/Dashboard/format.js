const currency = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });
const number = new Intl.NumberFormat('es-MX', { maximumFractionDigits: 2 });
const oneDecimal = new Intl.NumberFormat('es-MX', { maximumFractionDigits: 1 });

export const formatCurrency = (value) => currency.format(Number(value ?? 0));

// $1,284 / $12.9 mil / $4.2 M en cifras grandes
export const formatCurrencyCompact = (value) => {
    const n = Number(value ?? 0);
    const sign = n < 0 ? '-' : '';
    const abs = Math.abs(n);
    if (abs >= 1e6) return `${sign}$${oneDecimal.format(abs / 1e6)} M`;
    if (abs >= 1e4) return `${sign}$${oneDecimal.format(abs / 1e3)} mil`;
    return currency.format(n).replace(/\.00$/, '');
};

export const formatNumber = (value) => number.format(Number(value ?? 0));

// Variación porcentual contra el periodo anterior; null si no hay base.
export const percentChange = (actual, anterior) => {
    const a = Number(actual ?? 0);
    const b = Number(anterior ?? 0);
    if (!b) return null;
    return ((a - b) / b) * 100;
};

// Tope "bonito" del eje: 1, 2, 2.5 o 5 × 10^n
export const niceMax = (value) => {
    if (!value || value <= 0) return 1;
    const exp = Math.floor(Math.log10(value));
    const base = 10 ** exp;
    const step = [1, 2, 2.5, 5, 10].find((m) => m * base >= value);
    return step * base;
};
