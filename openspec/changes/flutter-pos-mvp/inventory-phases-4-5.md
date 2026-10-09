# Inventario nativo: Compras y Transferencias

## Permisos y navegación

Los menús se muestran a partir de los permisos de la autorización firmada y se retiran cuando cambia el rol. Se mantienen los roles actuales de las rutas web:

| Rol | Compras | Transferencias |
| --- | --- | --- |
| vendedor | Sí | No |
| inventario | No | Sí |
| admin | Sí | Sí |
| adminEmpresa | Sí | Sí |
| superAdmin | Sí | Sí |

Compras concede `purchase.read`, `purchase.create` y `purchase.payment`. Transferencias concede `transfer.read`, `transfer.create` y `transfer.receive`. El servidor comprueba el usuario actual, dispositivo autorizado, empresa y sucursal en cada solicitud. El bloqueo empresarial se aplica con la excepción existente para superAdmin.

Ambos módulos requieren conexión para consultar y registrar. Las solicitudes por confirmar se conservan en SQLite por contexto; los listados y detalles se consultan al servidor y no constituyen un catálogo financiero offline. Los precios de las líneas de compra pertenecen al módulo de Compras, como en la web; no conceden acceso adicional a los costos del catálogo de inventario.

## Fase 4: Compras

- Listado paginado con búsqueda por proveedor y detalle de productos, cantidades, precios de compra, pagos, saldo y vencimiento.
- Registro con proveedor, fecha, productos existentes, cantidades, precios de compra y pago inicial. El servidor calcula el total por línea con centavos y redondeo, rechaza pagos superiores al total, registra stock y lotes, y determina si queda pagada o a crédito. El vencimiento conserva los 30 días usados por la web.
- Abonos con confirmación y saldo esperado. El servidor bloquea la compra y rechaza saldo desactualizado, abonos no positivos y sobrepagos. Web y app coordinan también los abonos: el formulario web envía el saldo original y no puede sobrescribir pagos nuevos con un saldo antiguo.
- `PurchaseManagement` comparte el registro de compras con la web y mantiene su identidad de solicitud. Los productos eliminados continúan siendo visibles en los detalles históricos.
- La migración `2026_10_08_030000_preserve_fractional_purchase_quantities` cambia `compras_productos.cantidad` a decimal para que no se trunquen cantidades fraccionarias en MySQL. Se aplicó en el entorno local. Su reversión conserva el tipo decimal para evitar pérdida de cantidades ya recibidas.

## Fase 5: Transferencias

- Listado paginado con búsqueda por folio y detalle de origen, destino, notas, productos y estado.
- Destinos activos de la misma empresa, distintos del origen. El selector utiliza productos con existencia local estimada positiva; la disponibilidad definitiva se comprueba al recibir.
- Crear registra una transferencia pendiente con folio único y no descuenta stock, manteniendo el comportamiento de la web.
- Recibir requiere confirmación y solo está disponible para la sucursal destino. `TransferManagement` bloquea la transferencia y productos, verifica existencias del origen, consume lotes FIFO, crea lotes y compra interna en destino, registra salida/entrada y usuario receptor, y cambia el estado en una sola transacción.
- Stock insuficiente, producto eliminado o contexto incorrecto no dejan movimientos ni lotes parciales. Una recepción ya completada devuelve su estado sin volver a mover stock, incluso desde otro dispositivo o desde la web.
- Los recibos PDF existentes continúan en la web; esta fase nativa comprende listado, detalle, creación y recepción.

## Reintentos y contrato

Las cuatro escrituras (`purchases/create`, `purchases/{id}/payment`, `transfers/create`, `transfers/{id}/receive`) guardan identificador y contenido antes de enviar. Los formularios se bloquean si el resultado es incierto y permiten reintentar exactamente la misma solicitud. `pos_inventory_requests` registra el resultado en la misma transacción que los efectos. Cambiar el contenido de un identificador previamente usado devuelve conflicto.

La confirmación se guarda antes de actualizar el catálogo. Un fallo posterior de descarga no reenvía la escritura y muestra un aviso para sincronizar. Los menús ofrecen las solicitudes pendientes del módulo y contexto correspondiente.

Se documentaron nueve operaciones nuevas en `openapi.json`, incluidos listados, detalles, destinos, creación, abonos y recepción.

## Verificación

- 115 pruebas Flutter aprobadas y análisis estático sin incidencias.
- 46 pruebas del API nativo y 16 de regresión de controladores aprobadas.
- Casos de móvil (360 px) y escritorio (1200 px), los cinco roles, revocación de permisos, conexión, confirmaciones, cantidades fraccionarias, saldo desactualizado y flujo completo desde los menús.
- Respuesta perdida y reinicio de app para compra, abono, creación y recepción de transferencia, sin duplicar stock ni pagos.
- Aislamiento de empresa/sucursal, FIFO, recepción insuficiente con rollback, productos eliminados, repetición de recepción entre web y app y abonos concurrentes detectados por saldo esperado.
- La suite general de Laravel conserva 23 fallos previos, sin nuevos fallos respecto al informe de la fase anterior. Las pruebas del API utilizan SQLite; la contención real en MySQL sigue pendiente del entorno aislado descrito en `backend-phase-1.md`.
