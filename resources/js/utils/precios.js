// Tipos de precio de venta. Cada cliente tiene asignado uno (por defecto: público en general).
export const TIPOS_PRECIO = [
    { value: 'publico', campo: 'precio_publico', label: 'Público en general' },
    { value: 'preferencial_1', campo: 'precio_preferencial_1', label: 'Distribuidor 1' },
    { value: 'preferencial_2', campo: 'precio_preferencial_2', label: 'Distribuidor 2' },
    { value: 'preferencial_3', campo: 'precio_preferencial_3', label: 'Distribuidor 3' },
];

const tipoPrecio = (tipo) => TIPOS_PRECIO.find((item) => item.value === tipo) ?? TIPOS_PRECIO[0];

export const etiquetaTipoPrecio = (tipo) => tipoPrecio(tipo).label;

// Precio de venta del producto para el tipo de precio del cliente.
export const precioProducto = (producto, tipo) => Number(producto?.[tipoPrecio(tipo).campo] ?? producto?.precio_publico ?? 0);
