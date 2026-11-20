// Utilidades compartidas del módulo de auditorías de inventario.
export const STATUS_BADGE = {
    borrador: 'ui-badge',
    en_conteo: 'ui-badge ui-badge-blue',
    en_revision: 'ui-badge ui-badge-amber',
    finalizada: 'ui-badge ui-badge-green',
    cancelada: 'ui-badge ui-badge-red',
};

export const statusBadge = (status) => STATUS_BADGE[status] || 'ui-badge';

// Cantidades con hasta dos decimales, sin ceros sobrantes.
export const cantidad = (value) => {
    if (value === null || value === undefined || value === '') return '—';
    const n = Number(value);
    if (!Number.isFinite(n)) return String(value);
    return n.toLocaleString('es-MX', { maximumFractionDigits: 2 });
};

export const diferenciaClase = (value) => {
    const n = Number(value);
    if (value === null || value === undefined || !Number.isFinite(n) || n === 0) return '';
    return n < 0 ? 'text-red-700 dark:text-red-300 font-bold' : 'text-blue-700 dark:text-blue-300 font-bold';
};

export const primerError = (errors) => {
    const values = Object.values(errors || {});
    return values.length ? values[0] : '';
};
