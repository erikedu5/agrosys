// Formas de pago (claves SAT) para ventas de clientes que requieren factura.
// Mantener igual que App\Models\Venta::FORMAS_PAGO.
export const FORMAS_PAGO = [
    { value: '01', label: 'Efectivo' },
    { value: '03', label: 'Transferencia electrónica' },
    { value: '04', label: 'Tarjeta de crédito' },
    { value: '28', label: 'Tarjeta de débito' },
    { value: '02', label: 'Cheque nominativo' },
    { value: '99', label: 'Por definir' },
];

export const etiquetaFormaPago = (clave) => FORMAS_PAGO.find((forma) => forma.value === clave)?.label ?? '';
