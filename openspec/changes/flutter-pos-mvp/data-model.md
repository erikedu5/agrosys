# Modelo local del MVP

SQLite mediante Drift. El esquema v4 de consulta/ventas/sincronización y sus migraciones están
implementados. La tabla siguiente mantiene el diseño completo del MVP;
ventas/pagos/Outbox, worker y confirmaciones están implementados.
Una base por contexto autorizado o aislamiento equivalente verificado.
Todos los IDs centrales son strings; IDs locales son UUID. Dinero en
centavos int64 y cantidad decimal con escala acordada, nunca float de negocio.

| Tabla | Campos relevantes / restricciones |
|---|---|
| app_metadata | schema_version, context_id, instalación y versionado de migración |
| authorized_context | user_id, company_id, branch_id, device_id, permisos, lease_id, issued/expires; token y lease firmada en secure storage |
| products | server_id PK, nombre, barcode index, tamaño, precio en centavos, activo, revision |
| customers | server_id PK, nombre index, descuento decimal, activo, revision |
| stock_snapshots | product_id+branch_id únicos, cantidad central, revision, synced_at |
| account_snapshots | customer_id+branch_id únicos, adeudo/abono/balance centrales en centavos, revision, synced_at |
| sales | UUID PK, operation_id unique, context_id, cliente, tipo, total, inicial, saldo, folio_local unique, folio_server nullable, estado, occurred_at |
| sale_items | UUID PK, sale_id FK, producto, nombres/precios históricos, cantidad decimal, total_cents |
| payments | UUID PK, sale_id FK, método cash, applied_cents, received_cents, change_cents; cero inicial válido en crédito |
| inventory_movements | UUID PK, operation_id, producto, delta decimal, estado de reflexión, commit_revision nullable |
| account_movements | UUID PK, operation_id, cliente, debt_delta_cents, payment_delta_cents, estado de reflexión, commit_revision nullable |
| outbox | operation_id PK, sale_id unique FK, context_id, secuencia unique por dispositivo, payload inmutable/hash, estado, attempts, next_attempt_at, worker_lease, server_result, error_code, error |
| device_sequence | dispositivo/contexto PK, next_sequence; incrementar dentro de cierre de venta |
| sync_state | contexto PK, cursor, snapshot_revision, página/continuación, última_sync |
| sync_workers | context_id PK, owner UUID nullable, expires_at epoch milliseconds |

## Transacción de cierre

Comprobar autorización, precios y pago; crear venta/items/payment; crear
movimientos de inventario y cuenta; asignar secuencia y folio; crear Outbox
inmutable. Commit único. Después se muestra comprobante y se despierta worker.
Fallo antes de commit no consume cierre exitoso, secuencia ni movimiento.
Un UUID de finalización conserva el mismo resultado ante doble clic.

Para crédito 100 / inicial 30: debt_delta 100, payment_delta 30, balance_delta
70. Contado 100: debt_delta 100, payment_delta 100, balance_delta cero,
según la semántica central actual que se debe verificar con pruebas.

## Estados separados

Venta: `draft` → `pending_sync` → `confirmed` o `conflict`.
Outbox: pending → sending → confirmed; sending → retry/blocked;
retry → sending. `processing` remoto pasa a retry conservando identidad y
próxima consulta recuperable.
Auth requerida es bloqueo de contexto, no una venta anulada.

Movimiento: `unreflected` → `acknowledged_unreflected` → `reflected`.
Un recibo de snapshot puede llevar unreflected directamente a reflected
si demuestra commit. Un conflicto no borra los deltas: conserva la realidad
local cobrada y la revisión pendiente hasta resolución auditable.

## Invariantes

1. Una venta terminada tiene una operación; sin venta, no hay Outbox huérfana.
2. Reintentos no regeneran IDs, secuencias, pago, payload ni folio.
3. Cantidad estimada = cantidad snapshot + deltas no reflejados.
4. Adeudo estimado = balance snapshot + cargos menos abonos no reflejados.
5. ACK no implica retirada de delta; snapshot y recibo se aplican juntos.
6. Retirar deltas repetidamente es idempotente.
7. No existe actualización local que suba stock o balance absoluto al servidor.
8. No se elimina ni reasigna cola al cerrar sesión/cambiar sucursal.
9. Nombres y precios históricos de ticket no dependen del catálogo actual.
10. Actualización/migración no resetea device_id/secuencia ni modifica payloads.

El archivo SQLite en sí no está cifrado por usar secure storage. Antes de
distribuir se define cifrado/desbloqueo y recuperación; verificar biblioteca
e integración en los cuatro targets antes de prometer ese soporte.

## Correspondencia implementada en fase 2

`LocalProducts`, `LocalCustomers`, `StockSnapshots`, `AccountSnapshots` y
`SyncMetadata` contienen la consulta. El contexto y concesión/token residen
en secure storage; `SyncMetadata` verifica propietario y conserva bloqueo,
cursor, revisión y fecha. `Downloads` y `DownloadPages` guardan staging
recuperable. `LocalEffects` prepara estimados stock/cuenta y reflexión por
recibo; la fase 2 no produce ventas ni operaciones de Outbox.

Versión 2 añade staging/efectos a versión 1 sin reset. Cantidades y dinero
se convierten desde strings decimales a enteros con escala 100. La prueba de
migración conserva catálogo/contexto y un payload pendiente ajeno al esquema.

## Correspondencia implementada en fase 3

`LocalSales` y `LocalSaleItems` conservan propietario, UUID, secuencia, folio y
valores históricos. `LocalPayments` conserva aplicado/recibido/cambio, incluido
inicial cero explícito. `LocalEffects` contiene un delta stock por producto y
un delta deuda/pago por cliente, vinculados a la misma operación. `Outbox`
contiene JSON exacto de la operación y SHA-256, propietario y campos reservados
para reintentos/lease. `DeviceSequence` se incrementa en la transacción del cierre.
`ReceiptAttempts` registra resultados del servicio de impresión separados de
la venta. Folio local incluye UUID completo de dispositivo y secuencia.

El payload Outbox es el objeto de `operations[]` del contrato; el worker de
fase 4 agrega envelope `schemaVersion/device_id/branch_id`. Un inicial cero
se serializa como un único método cash con amount `0.00`, conforme al backend,
y no suma abono ficticio al estimado. Migrar con pendientes conserva bytes, IDs
y efectos existentes; no se regenera la identidad del dispositivo.

## Correspondencia implementada en fase 4

Drift v4 añade `Outbox.serverResult/errorCode` y `SyncWorkers`, manteniendo
payload, hash, estado, attempts, próxima fecha y lease de filas existentes.
Migrar v3→v4 hace ALTER TABLE sin reset; v1/v2→v4 crea tablas de las fases 3/4.
El ownership global del worker es distinto de la marca `workerLease` por fila:
la primera controla exclusión/recuperación, la segunda impide reescribir filas
que ya recibieron resultado o fueron recuperadas. ACK guarda resultado/folio sin
reflejar efectos. Recibo válido puede confirmar desde pending/sending incluso
sin ACK y refleja efectos junto con snapshot/cursor. Conflictos conservan cobro,
folios e identidad; no se eliminan ni reintentan automáticamente.
