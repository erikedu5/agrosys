const moneda = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

export const formatMoneda = (value) => {
    const number = Number(value ?? 0);
    return Number.isFinite(number) ? moneda.format(number) : String(value ?? '');
};

// Solo da formato a fechas ISO (yyyy-MM-dd...); cualquier otro texto se muestra tal cual.
export const formatFecha = (value, conHora = false) => {
    if (!value) return '';
    const texto = String(value);
    if (!/^\d{4}-\d{2}-\d{2}/.test(texto)) return texto;
    const fecha = new Date(texto.length === 10 ? `${texto}T00:00:00` : texto.replace(' ', 'T'));
    if (Number.isNaN(fecha.getTime())) return texto;
    const opciones = { day: '2-digit', month: 'short', year: 'numeric' };
    if (conHora) Object.assign(opciones, { hour: '2-digit', minute: '2-digit' });
    return fecha.toLocaleString('es-MX', opciones);
};
