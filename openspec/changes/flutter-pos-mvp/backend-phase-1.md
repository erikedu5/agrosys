# Fase 1 — backend nativo POS

Implementada y verificada el 2026-10-07. El cliente Flutter, los spikes físicos
multiplataforma y el piloto continúan pendientes. No se desplegó ni se ejecutaron
migraciones en la base de producción.

## Comportamiento disponible

- 13 endpoints bajo `/api/v1/pos`: login, challenge Fortify 2FA, logout,
  sucursales, activación/renovación/revocación, heartbeat, bootstrap, push,
  pull y consulta de operación. Token Sanctum ligado al dispositivo y contexto
  explícito; sin dependencia de la sesión web.
- Empresa/sucursal/usuario activos, suscripción vigente y permisos por rol.
  Inventario consulta pero no vende. IDs de dispositivos nativos no pueden
  usarse en las rutas PWA para saltarse las concesiones nativas.
- Concesión Ed25519: 7 días por defecto, renovación explícita, recepción tardía
  máxima de 24 horas. Un ID revocado no se reactiva. Los pendientes fuera de
  ventana requieren revisión central; el backend no inventa ventas nuevas.
- Venta contado/crédito con inicial de 0..total. Dinero entero en centavos,
  cantidades en centésimas, precio descontado redondeado antes de la partida.
  Validación compartida web/PWA/nativa; el frontend web utiliza el mismo
  redondeo half-up con enteros. Un crédito con inicial completo queda pagado
  sin cancelar deudas anteriores.
- Huella canónica SHA-256 por operación, resultado original en duplicados,
  bloqueo de dispositivo y restricciones de unicidad. Venta, inventario,
  abono y saldo se guardan en transacción; error temporal no deja escrituras
  parciales. Mismo ID alterado no cambia el original.
- Snapshots inmutables REPEATABLE READ en MySQL, páginas y cursores cifrados
  por usuario/dispositivo/sucursal. Pull compara valores completos: captura
  cambios centrales de precios, clientes, stock, compras, devoluciones,
  transferencias y abonos sin depender de callbacks de cada controlador.
  Bajas/desactivaciones viajan como registros inactivos. Recibos y nextCursor
  sólo en última página; Flutter deberá aplicar el conjunto completo de forma
  atómica y conservar Outbox cuando venza el cursor.

## Evidencia

- `NativePosApiTest`: autenticación, TOTP real y recovery codes, expiración,
  throttle, aislamiento, roles, firma/revocación, contado/crédito, replays,
  rechazo de importes inválidos, rollback, snapshots, cambios de escritores
  centrales, cursores y coherencia de venta web.
- SQLite: 45 pruebas dirigidas pasan (API, PWA y regresiones previas).
- MySQL 8.4 aislado: 29 pruebas pasan (API nativa, concurrencia y PWA); 225
  aserciones. Dos procesos con HTTP kernels y conexiones independientes
  verifican replay simultáneo, secuencia reutilizada y payload alterado: sin
  duplicar venta, pago, stock ni adeudo. La prueba adicional de escritores
  centrales se verificó en SQLite después de esa ejecución.
- JavaScript: 12 pruebas pasan, incluidos 6 fixtures compartidos de contado,
  crédito y descuento/fracciones; `npm run build` pasa.
- OpenAPI 3.1 validado mediante `openapi-spec-validator` 0.9.0 temporal, fuera
  de dependencias del proyecto. Respuestas reales de Activation, PushResults
  y SnapshotPage validadas contra sus schemas con jsonschema. También se
  verifica correspondencia de las 13 rutas mediante test Laravel.
- Suite completa: conserva los 23 fallos del baseline previo (fixtures de
  usuario sin sucursal/clave foránea y onboarding); no atribuirlos a esta
  fase. La prueba MySQL se omite deliberadamente en la suite SQLite.
- OpenSpec: `openspec validate flutter-pos-mvp --strict --no-interactive`.

## Habilitación en un entorno de piloto

Primero aplicar las migraciones pendientes al entorno elegido. La nueva
migración es `2026_10_06_010000_create_native_pos_tables.php`; requiere las
migraciones previas de offline. Se corrigió también el orden del rollback de
la migración de idempotencia de compras para MySQL.

Configurar `POS_NATIVE_ENABLED=true`, `POS_OFFLINE_VALID_DAYS=7` y la política
existente `offline.require_activation`/`offline.allow_negative_stock` del
entorno. Con activación manual, el primer intento conserva un dispositivo
pendiente y devuelve 403; se autoriza por administración y se reintenta.
Aplican los límites de dispositivos del plan por sucursal.

Se requiere sodium para Ed25519. `POS_LEASE_SIGNING_SEED` admite una semilla
independiente de 32 bytes en 64 caracteres hexadecimales. Si está vacío, se
usa HKDF con APP_KEY y dominio específico. Una rotación cambia la clave
pública: planificar la reactivación/renovación y el pin del cliente; no cambiar
APP_KEY como procedimiento rutinario de POS. No registrar tokens ni semillas.

Mantener el scheduler Laravel activo: `pos:prune` elimina snapshots y
challenges vencidos cada hora, sólo cuando POS está habilitado. Las concesiones
y operaciones se conservan para auditoría. HTTPS es requisito de instalación.

## Repetir pruebas

```sh
php artisan test --filter='NativePosApiTest|OfflineSaleIdempotencyTest|ControllerErrorRegressionTest'
node --test tests/js/*.test.mjs
npm run build
openspec validate flutter-pos-mvp --strict --no-interactive
```

MySQL usa exclusivamente `agrosys_pos_test` en puerto 33079; la prueba de
concurrencia verifica ambos valores antes de recrear el esquema. Puede usarse
el contenedor temporal `agrosys-pos-mysql-test`, publicado sólo en localhost.

```sh
APP_ENV=testing DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=33079 \
DB_DATABASE=agrosys_pos_test DB_USERNAME=root DB_PASSWORD='' \
php artisan test --filter='NativePosApiTest|NativePosConcurrencyTest|OfflineSaleIdempotencyTest'
```

Las migraciones históricas de productos tienen un down() incompatible con
MySQL porque eliminan un índice requerido por una FK. La prueba aislada usa
migrate:fresh/db:wipe, sin ejecutar esos rollback históricos. No se modificó
esa migración ajena a la fase; no usar esta receta sobre otra base.

## Límites pendientes para el piloto

Snapshot implica lectura completa y almacenamiento de catálogo por revisión,
con TTL de 24 horas. Medir memoria, latencia y tamaño durante el piloto; escalar
con changelog sólo si todos los escritores participan. Los tests prueban los
movimientos centrales almacenados; las pruebas completas de UI por cada flujo
(compra/devolución/transferencia/cobranza) pertenecen al QA del piloto.

No hay app Flutter, impresión física, builds firmados ni distribución todavía.
No hay endpoint nativo de cobro posterior, corrección auditada de conflictos
ni recuperación de dispositivo perdido. Los relojes de cliente y la firma
no demuestran por sí solos cuándo ocurrió una venta en un equipo manipulado.
El spike de SQLite/secure storage, cifrado local y toolchains de fase 0 se debe
resolver al comenzar fase 2 antes de declarar soporte de los cuatro targets.
