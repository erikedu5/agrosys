# Apertura y corte de caja — web y Flutter

Una caja activa por cajero y sucursal, compartida entre la web y los dispositivos
Flutter del mismo cajero. No altera ni incorpora ventas anteriores a la apertura.
La apertura es obligatoria antes de registrar cualquier venta, de contado o crédito.
El backend y los clientes bloquean nuevas ventas sin una caja abierta.

## Operación

- Web y Flutter: desde **Venta**, pulsar el botón grande **Abrir caja**. El formulario
  se abre en un modal y conserva el carrito. Cuando la caja está abierta, el botón
  cambia a **Cerrar caja / corte** y abre el mismo modal con movimientos, corte e
  historial. La opción independiente de caja se retiró del menú. La ayuda aparece
  en el icono de información. `/caja` sigue funcionando para enlaces anteriores.
- Abrir con un fondo inicial no negativo; registrar entradas y salidas con motivo.
- Efectivo esperado = fondo + ventas de contado + iniciales de crédito + abonos
  posteriores de clientes + entradas manuales − salidas manuales − reembolsos de contado.
- Se registra el importe aplicado, no el efectivo entregado antes de devolver cambio.
- Los pagos a proveedores se incluyen como salida manual cuando salen de la caja;
  no se asume que todos los pagos de compras sean efectivo.
- Antes del corte, sincronizar TODOS los dispositivos y navegadores del cajero.
- Registrar efectivo contado, confirmar conteo y sincronización. Si hay diferencia,
  explicar el motivo. El servidor rechaza un corte cuyo esperado cambió desde la consulta.
- Historial de los últimos 20 cortes y comprobante imprimible con fondo, totales,
  esperado, contado, diferencia, fechas, cajero, sucursal y observaciones.

## Consistencia y recuperación

Montos en centavos enteros. Caja, cobros y cierre comparten un bloqueo del cajero
para serializar los movimientos con el cierre. La venta web y los abonos se guardan
junto con sus movimientos de caja en una transacción. Una sesión cerrada conserva
su esperado y diferencia; no se recalcula con movimientos posteriores.

Cada escritura lleva `operation_id` UUID. El servidor conserva contexto, hash y
respuesta; un reintento devuelve la misma confirmación y rechaza un payload distinto.
Flutter guarda las solicitudes antes de enviarlas en SQLite y confirma respuesta y
estado local de caja de forma atómica. La web guarda su solicitud exacta en
localStorage por usuario/sucursal, recuperable al volver a abrir el modal de caja. Una solicitud de caja
sin confirmar bloquea nuevas ventas desde el cliente actualizado.

Las ventas offline de ambos clientes conservan `cashSessionId` original en su
payload inmutable. Un ID nulo (o ausente en clientes antiguos) se rechaza y requiere
revisión, aunque se abra una caja después. Las ventas locales anteriores se conservan;
no se reescriben para asociarlas a otra caja. Una venta enviada para una caja cerrada o de otro contexto
queda en revisión sin modificar el corte. Las operaciones conocidas por el servidor
que siguen pendientes/en conflicto impiden cerrar su caja. La web comprueba además
sus ventas locales, y Flutter comprueba toda su Outbox antes del cierre. El servidor
no puede detectar ventas que otro dispositivo todavía no ha enviado: por ello la
confirmación del cajero exige sincronizar todos sus dispositivos.

Apertura, movimientos manuales y cierre requieren conexión; Flutter permite consultar
la última caja/cortes descargados offline. La base local migra de v1–v6 a v7 conservando
ventas, secuencias, payloads, solicitudes y catálogo. No se abren ni cierran cajas offline.

## Contrato y despliegue

API POS: `POST /cash/status`, `/cash/open`, `/cash/movement`, `/cash/close`, con token
ligado a dispositivo, `device_id` y `branch_id`. Permiso `cash.manage` para vendedor,
admin, adminEmpresa y superAdmin. El inventarista no maneja caja. Todas las lecturas y
escrituras están limitadas al cajero autenticado y la sucursal autorizada.
Las escrituras responden `operationId`, `status: confirmed`, `sessionId` y `session`.
El contrato actualizado está en `openapi.json`.

La migración `2026_10_13_010000_create_cash_register_tables.php` crea `cash_sessions`,
`cash_movements` y `cash_requests`. Aplicada únicamente a la base local durante esta
implementación. Para desplegar: ejecutar la migración, construir los assets web y
compilar/distribuir el nuevo cliente Flutter. Los instaladores publicados 2.1.0 no
incluyen estos cambios. El heartbeat actualiza las concesiones cuyos permisos cambiaron.

## Validación

- Backend: pruebas de caja, POS nativo, idempotencia offline y regresiones de ventas;
  incluye rechazo antes de abrir y después de cerrar sin generar cobros o inventario.
- Flutter: suite completa de 129 pruebas aprobada; apertura/cierre con pérdida de respuesta y reinicio, pago offline ligado a
  caja, bloqueo de corte con pendientes, confirmación inválida y migración desde v6.
- Widgets: apertura, retiro, corte con faltante, historial y modal desde ventas a 360
  y 1200 px; comprueban la ausencia del menú de caja y la conservación del carrito.
- Análisis Flutter sin incidencias y compilación Vite aprobada.
- Web: 7 pruebas del servicio real de ventas, con transporte y persistencia sustituidos:
  conflictos de caja, respuestas de reintento, recuperación de ACK, referencia offline
  y bloqueo por solicitudes pendientes o caja cerrada. Ejecutar
  `node --experimental-vm-modules --test tests/js/saleApplicationService.test.mjs`.

Tras corregir las factories de usuarios, sucursales y productos, adaptar la prueba
de eliminación al borrado lógico y corregir los campos opcionales y la persistencia
del trial en onboarding, la suite PHP completa pasó: 128 aprobadas, 8 omitidas,
0 fallidas (934 assertions). Las omitidas dependen de funcionalidades deshabilitadas
o de la ejecución de concurrencia con MySQL aislado.
El navegador integrado no estuvo disponible para verificación visual de la web;
la salida física de impresión tampoco se probó.
