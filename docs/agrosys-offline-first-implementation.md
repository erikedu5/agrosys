# Implementación offline-first y piloto

## Alcance implementado

La aplicación conserva una sola entrada Vite, un solo layout Inertia y la
pantalla `Venta/Venta.vue`. Las fases 1–6 están conectadas mediante feature
flags:

- PWA con shell cacheado, navegación network-first y APIs network-only.
- Máquina `online`, `offline`, `recovering`, `sync_error` con health check.
- Catálogo, precios, existencias, clientes y sesión temporal en IndexedDB.
- Venta única online/offline mediante `SaleApplicationService`.
- Transacción local con venta, partidas, pagos, movimientos, stock estimado y Outbox.
- Ticket provisional, reimpresión y cancelación local compensatoria.
- Push idempotente, consulta por `operationId`, backoff, recuperación de
  operaciones `processing` y pull incremental con cursor.
- Conflictos por precio, producto/cliente inactivo, stock, secuencia reutilizada
  o dispositivo revocado.
- Bandeja persistente de pendientes y conflictos.

## Despliegue

1. Respaldar la base de datos.
2. Ejecutar `php artisan migrate`.
3. Compilar con `npm run build`.
4. Activar primero una sucursal/caja con las variables:

```dotenv
POS_OFFLINE_ENABLED=true
POS_OFFLINE_CATALOG_ENABLED=true
POS_OFFLINE_SALES_ENABLED=true
POS_SYNC_PUSH_ENABLED=true
POS_OFFLINE_VALID_DAYS=7
POS_REQUIRE_ACTIVATION=false
POS_OFFLINE_ALLOW_NEGATIVE_STOCK=true
```

Para autorización manual, usar `POS_REQUIRE_ACTIVATION=true` y autorizar el
registro correspondiente en `offline_devices`.

## Secuencia de validación del piloto

1. Abrir `/venta` online y confirmar las tablas de IndexedDB.
2. Cortar la red, buscar por nombre/barcode y registrar cinco ventas en efectivo.
3. Cerrar y reabrir la PWA; confirmar que ventas y pendientes siguen visibles.
4. Cancelar una venta no sincronizada; debe crear el movimiento compensatorio.
5. Recuperar red; debe pasar por `recovering`, confirmar cuatro ventas y aplicar pull.
6. Reenviar un `operation_id`; debe responder `duplicate` con el mismo folio.
7. Cambiar un precio antes del push; la venta debe quedar como `conflict` visible.
8. Revocar el dispositivo; debe responder 403 sin eliminar la cola local.

## Operación y recuperación

Las filas Outbox confirmadas no se borran: se conservan como auditoría. `failed`
usa backoff y jitter; `blocked` requiere revisión. Un 400/401/403 no se interpreta
como pérdida de conexión. El cliente nunca sube stock absoluto: usa movimientos
y calcula `serverQuantity + localPendingDelta`.

La facturación automática no se ejecuta durante el procesamiento offline. Una
venta confirmada que requiera CFDI debe continuar en el flujo central.
