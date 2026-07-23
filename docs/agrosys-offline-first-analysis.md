# Análisis de la primera fase offline-first

## Flujo actual de venta

La única pantalla de POS es `resources/js/Pages/Venta/Venta.vue`, entregada por
`VentaController@index`. Recibe productos con existencia positiva, clientes de
la sucursal, búsquedas entre sucursales y configuración de bloqueo. La venta se
envía con Inertia a `venta.store`; Laravel crea `ventas`, `producto_ventas`, un
`alta_inventarios` por partida y un `abono_cuentas`, actualiza el cliente y
devuelve la venta usada para imprimir el ticket HTML.

Requests directos identificados:

- búsquedas del POS mediante visitas Inertia a `venta.index`;
- búsqueda F3 mediante `GET /buscar-precio`;
- ventas del día mediante `GET /reporte/ventas-dia`;
- reimpresión mediante `GET /ticket/print-last`;
- alta de venta mediante `POST /venta`.

## Diseño aplicado

Se mantiene una sola entrada Vite y una sola página de venta. La primera fase
añade una máquina de conectividad Pinia, health check real, banner permanente,
guardas centrales para impedir visitas Inertia incompatibles, y una base
IndexedDB versionada con todas las tablas reservadas por el plan.

`GET /api/v1/offline/bootstrap` entrega el catálogo autorizado de la sucursal,
precios, última existencia central y clientes. La descarga se escribe de forma
atómica en IndexedDB. La pantalla existente cambia a un repositorio local para
búsquedas por nombre, SKU o código de barras cuando Laravel no está disponible.

Las capacidades de consulta siempre dependen del catálogo sincronizado.
`sale.create` y la impresión local sólo se habilitan cuando coinciden feature
flags, rol, sesión vigente y dispositivo autorizado.

Sólo `/venta`, `/buscar-precio` y los endpoints de recuperación tienen política
offline. Dashboard, devoluciones, inventario, compras, transferencias, clientes,
facturación, reportes, pedidos, catálogos, administración, perfil, suscripción,
cambio de sucursal y cierre de sesión requieren conexión.

## Riesgos y siguiente corte

- El primer uso requiere una sincronización online exitosa.
- El HTML de `/venta` sólo puede reabrirse offline después de haber visitado esa
  URL; los datos de negocio sí quedan independientes en IndexedDB.
- La sesión temporal se renueva online y su expiración bloquea ventas nuevas,
  pero conserva la consulta y la sincronización de pendientes.
- El bootstrap inicial consulta la última existencia por producto; antes de un
  despliegue grande conviene reemplazarlo por una consulta agregada y paginada.
