# Contrato de API nativa implementado — fases 1–4

Estado: backend implementado. Base `/api/v1/pos`; OpenAPI en `openapi.json`.
Deshabilitado por defecto con `POS_NATIVE_ENABLED=false`.
No sustituye las rutas PWA `/api/v1/offline`. Adaptador normaliza el contrato
nativo para los servicios comunes tras reforzar validación.

HTTPS, `Accept: application/json`, token Bearer personal tras login.
IDs de negocio como strings, UUID para dispositivo/venta/operación,
fechas RFC3339 con zona horaria, importes decimales en strings con dos posiciones,
cantidades decimal string con dos posiciones (máximo 999999.99). No mostrar credenciales en logs.
`schemaVersion: 2` distingue el contrato nativo del bootstrap PWA v1 actual.

## Endpoints

| Método y ruta | Contrato |
|---|---|
| GET `/health` | Disponibilidad; no permite inferir autorización ni salud de DB sólo por responder HTTP |
| POST `/auth/login` | Email/password/device_id/device_name; reglas de usuario activo, throttle y segundo factor cuando corresponda; token y sucursales permitidas |
| POST `/auth/verify-2fa` | Completar challenge si la cuenta lo requiere; no emitir token operativo antes |
| POST `/auth/context` | Seleccionar branch_id/device_id autorizados tras login/2FA; emitir token ligado a identidad estable y revocar token anterior atómicamente |
| POST `/auth/logout` | Revocar token actual; conservar pendientes locales bloqueados |
| GET `/branches` | Sólo sucursales accesibles al usuario |
| POST `/devices/activate` | Registrar device_id y branch_id autorizados; lease y permisos; no reasignar un dispositivo existente silenciosamente |
| POST `/devices/revoke` | Revocar dispositivo propio o por administrador autorizado de la sucursal; irreversible para ese ID |
| POST `/devices/renew` | Renovación explícita con token, permisos y suscripción válidos; dispositivo revocado no se reactiva |
| POST `/bootstrap` | Contexto y known_operation_ids; snapshot inicial paginado, productos/clientes/balances, checkpoint y recibos |
| POST `/sync/push` | Hasta 50 operaciones; resultado individual, originales y revisión central de commit |
| POST `/sync/pull` | Contexto, cursor/page_token y known_operation_ids; páginas consistentes con cambios, bajas y recibos |
| GET `/operations/{operationId}` | Resultado únicamente del usuario/dispositivo/contexto autorizado |
| POST `/device/heartbeat` | Estado autorizado y vigencia; no renueva permisos automáticamente |

El token se emite con abilities y políticas por recurso/contexto; no se acepta
`branch_id` por pertenecer sólo al payload. Superadmin debe seleccionar contexto
de forma explícita. Login sin dispositivo activo no equivale a permiso de venta.
Token con ability `pos:access`, ligado al device_id, vence a los 30 días.
Challenge de 64 caracteres, almacenado como SHA-256, vence a los 5 minutos y
admite hasta 5 intentos. Login/2FA limitados por cuenta/challenge e IP.
Se aceptan TOTP y códigos de recuperación de Fortify; se consume cada código
de recuperación de manera transaccional. Sesiones web y tokens genéricos no
autorizan estas rutas.

## Operación de venta

Ejemplo de crédito con inicial; una venta contado usa `saleType: Contado` y
pago aplicado igual al total. El UUID de este ejemplo sólo es ilustrativo.

```json
{
  "schemaVersion": 2,
  "device_id": "6de3a7c4-850d-4a25-9e47-d3e818756d65",
  "branch_id": "12",
  "operations": [{
    "operation_id": "eb576cef-720d-4be5-84a2-7b4e17c89959",
    "aggregate_type": "sale",
    "aggregate_id": "48d248f9-211f-4093-952b-d3a15de718ab",
    "event_type": "SALE_COMPLETED",
    "sequence": 1,
    "occurred_at": "2026-10-06T16:00:00Z",
    "offline_lease_id": "19dcd58e-0ffb-4dd8-aa67-4e39b26f4e97",
    "payload": {
      "customerId": "34",
      "saleType": "Credito",
      "total": "100.00",
      "localFolio": "LOCAL-6DE3A7C4-000001",
      "items": [{"productId": "56", "quantity": "2.00", "unitPrice": "50.00", "total": "100.00"}],
      "payments": [{"method": "cash", "amount": "30.00"}]
    }
  }]
}
```

Para una sola operación se admite `Idempotency-Key` igual a `operation_id`.
Efectivo recibido y cambio se conservan en ticket local; el monto aplicado
del payload es 30, aunque se reciban billetes por valor mayor.

Servidor valida cardinalidad de pago (un método cash en MVP), importes finitos,
descuento, precios, cantidades, suma de partidas y tipo de venta. Crédito
requiere permiso y cliente existente autorizado; inicial 0..total. Cliente
financia saldo total − inicial. Inicial igual a total marca venta pagada y
no liquida otros créditos. No subir balance calculado por el cliente.

## Respuesta por operación

```json
{
  "results": [{
    "operationId": "eb576cef-720d-4be5-84a2-7b4e17c89959",
    "status": "confirmed",
    "originalStatus": "confirmed",
    "saleId": "48d248f9-211f-4093-952b-d3a15de718ab",
    "serverSaleId": 987,
    "serverFolio": "987",
    "commitRevision": "eb576cef-720d-4be5-84a2-7b4e17c89959"
  }],
  "serverTime": "2026-10-06T16:01:00Z"
}
```

`status`: confirmed, duplicate, conflict, rejected, retry o processing.
Duplicados incluyen `originalStatus` y resultado original; nunca interpretar
duplicate de conflicto como venta confirmada. El resultado exitoso demuestra
commit, no que SQLite ya tenga un snapshot que lo incluye.

Mismo ID + hash canónico diferente: `IDEMPOTENCY_PAYLOAD_MISMATCH`, sin cambiar
el original. Un ID de otro dispositivo/contexto: 403. Un race en INSERT debe
recuperar resultado o processing, no producir duplicados ni 500 por unicidad.
La semántica terminal/reintentable de cada código forma parte del contrato.

## Checkpoints y reconciliación

Bootstrap y pull deben obtener un punto consistente de catálogo/stock/cuentas.
Si usan changelog/revisiones, todos los escritores centrales (venta web,
compras, devoluciones, transferencias, ajustes y abonos) deben participar.
Se usan snapshots inmutables persistidos con UUID, obtenidos dentro de una
transacción REPEATABLE READ en MySQL. Pull compara valores completos contra
el snapshot anterior: captura cambios de cualquier escritor central sin
instrumentar cada controlador. El coste es lectura completa y almacenamiento
de catálogo por primera página (adecuado al piloto; medir antes de escalar).
Cursor/página cifrados están ligados al usuario/dispositivo/sucursal y vencen
a las 24 horas; `pos:prune` elimina snapshots/challenges vencidos cada hora.
`commitRevision` identifica la operación confirmada, no es un contador global.

Respuesta paginada incluye `snapshotRevision`, `pageToken`, `hasMore`,
`nextCursor`, cambios/bajas y `operationReceipts`. Cada recibo asocia
`operationId`, resultado original y `includedInSnapshot` al mismo snapshot.
El request proporciona los IDs locales pendientes/no reflejados para poder
reconciliar tras snapshot completo y expiración de cursor.

- Guardar páginas en staging durable; sólo la última contiene recibos y
  nextCursor. Aplicar todas las páginas, recibos y cursor en una sola
  transacción SQLite; una descarga incompleta nunca reemplaza el catálogo.
- Mantener revisión común mientras se recorren páginas; no mezclar revisiones.
- Marcar delta reflejado sólo cuando el snapshot completo aplicado y su
  recibo demuestren inclusión. No usar `last_sequence = max` como prueba.
- ACK sin pull: venta confirmada, delta todavía no reflejado.
- Pull antes del ACK: el recibo permite confirmar resultado y retirar delta
  junto al snapshot, sin descontar doble ni perder cargo de crédito.
- Cursor vencido: respuesta 410 y bootstrap completo, conservando Outbox y
  reconciliando mediante los IDs conocidos.

No habrá endpoints nativos de cobranza de créditos anteriores en este MVP.

## Errores HTTP y política temporal

401 requiere reautenticación; 402 indica bloqueo por suscripción; 403
autorización; 409 conflicto de identidad/payload; 410 cursor vencido; 422
entrada inválida; 429 reintento limitado; 5xx/timeouts error temporal o ambiguo.
Nunca interpretar 401/402/403 como falta de internet ni descartar la cola.

La concesión offline firmada contiene usuario, empresa, sucursal, dispositivo,
permisos, emisión, vencimiento y versión de política. Vigencia inicial 7 días configurable. Se aceptan operaciones ocurridas dentro
de la concesión hasta 24 horas después de su vencimiento; hasta 5 minutos de
tolerancia antes de emisión o respecto al reloj central. Después se devuelve
OFFLINE_LEASE_EXPIRED y el cliente conserva la operación para revisión.
Un replay ya confirmado conserva su resultado aun vencida la concesión,
siempre que token/dispositivo/contexto continúen autorizados. No confiar en
el reloj del cliente como prueba independiente.

La firma usa Ed25519 (sodium). Verificar los bytes exactos de `payload` y
comprobar que `claims` corresponden a esos bytes; no reserializar claims.
Obtener/pinear `publicKey` durante activación HTTPS. El backend persiste la
concesión y valida su pertenencia al procesar operaciones. Bootstrap/pull y
heartbeat no renuevan la concesión. Activación y renovación son explícitas;
un ID revocado nunca puede reactivarse. No mezclar IDs de dispositivos PWA
y nativos.

Los códigos dentro de results usan HTTP 200: conflicto/rechazo son terminales
para el contenido original y requieren intervención; retry/processing pueden
consultarse o reenviarse con el mismo payload. 409 es una carrera de activación.

## Consumidor Flutter de sincronización (fase 4)

`SyncWorker` reclama Outbox por contexto/secuencia en transacción, con exclusión
SQLite durable y lease de 120 segundos renovada antes de solicitudes y escrituras.
La muerte del proceso deja `sending`; al recuperar el lease se consulta
`GET /operations/{operationId}`. Un 404 permite enviar los mismos bytes lógicos,
IDs y secuencia; otros fallos conservan la operación. Hasta 50 por push. Backoff
2×2^attempts segundos, limitado a 900 antes de jitter 0.75..1.25, persistido en
`nextAttemptAt`. El reintento manual ignora el plazo, nunca un bloqueo autorizado
ni un conflicto de negocio. La huella SHA-256 local detecta corrupción sin enviar.

Confirmación exige `originalStatus=confirmed`, operationId y saleId propios y
folio central no vacío. `duplicate` de un conflicto no confirma una venta;
`IDEMPOTENCY_PAYLOAD_MISMATCH` requiere revisión aunque el estado previo fuera
confirmado. ACK actualiza venta/Outbox y conserva efectos no reflejados. Sólo
`includedInSnapshot=true` con resultado confirmado compatible refleja efectos,
con datos/cursor en el commit completo. No se realiza edición/reenvío con otro ID.

Se reanuda staging previo antes de push para no aplicar después una captura vieja
sobre operaciones recién confirmadas. Cada página y commit verifican ownership
activo; 410 reinicia bootstrap conservando Outbox. Los IDs conocidos priorizan
ACK confirmados sin prueba (máximo 2000 por snapshot), y se descargan capturas
adicionales sólo si disminuyen los confirmados sin prueba.

HTTP 401/402/403 bloquea acceso local durable; no se confunde con falta de red.
Reactivación/renovación verifican firma y contexto antes de desbloquear. Logout,
cambio de token/servidor/contexto invalidan el ejecutor antes de cerrar SQLite;
respuestas tardías no escriben confirmaciones en un contexto abandonado.

Si Outbox ya reportó `IDEMPOTENCY_PAYLOAD_MISMATCH` o `LOCAL_PAYLOAD_CORRUPT`,
un recibo central con los mismos IDs no prueba incorporación de ese contenido
local: se aplica el catálogo/cursor, se conserva conflicto y no se reflejan esos
efectos. Una revisión central explícita sigue siendo necesaria.
