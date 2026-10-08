# Evidencia de fase 4 — sincronización y recuperación

Fecha: 2026-10-07. Implementación de tareas 4.1–4.8. El cambio OpenSpec sigue
abierto para QA físico, Windows, firma/distribución e impresora/piloto de fase 5.

## Comportamiento entregado

- Worker exclusivo por contexto con ownership SQLite durable de 120 segundos,
  renovación antes de requests/escrituras, claims transaccionales, secuencia y
  lotes de hasta 50. Recupera `sending` tras un cierre o lease vencido.
- Consulta por operación antes de reenviar una intentada; conserva UUID, JSON,
  hash, secuencia, cobro y folio. Reintentos exponenciales con jitter y próxima
  fecha persistida; manual puede adelantar errores temporales.
- Resultado original determina confirmación/conflicto. Exige IDs propios y
  folio central; `duplicate` conflictivo no confirma. Conflictos se conservan
  para revisión central, sin edición ni creación de otro ID. Un recibo posterior
  no oculta un conflicto de contenido ni corrupción local, aunque coincidan IDs.
- ACK confirma venta/Outbox, pero conserva efectos. Snapshot completo y recibo
  validado reflejan stock/cuenta con datos/cursor en una sola transacción. Recibo
  anterior al ACK también confirma. Prioriza confirmados sin prueba y descarga
  capturas adicionales cuando hay más de 2000 recibos pendientes de demostrar.
- Reanuda staging antes de enviar; pages/commit verifican contexto y ownership.
  410 reinicia bootstrap sin borrar ventas. Recibo inválido revierte catálogo,
  reflejos y cursor; descarga completa permite recuperar staging inválido.
- HTTP 401/402/403 se distingue de fallo temporal y bloquea acceso durable.
  Revalidación explícita del mismo contexto recupera bloqueados. No hay
  renovación silenciosa de permisos o concesión offline.
- Disparadores: inicio, activación/renovación, venta terminada, primer plano,
  verificación de conectividad cada 30 segundos en primer plano y manual.
  El envío usa estado separado de checkout: se puede guardar otra venta local
  mientras espera la red. No depende de ejecución móvil continua en background.
- Historial filtra todas/pendientes/en revisión/confirmadas, muestra motivo y
  permite reintento temporal. UI muestra envío en curso y última sincronización.
  Logout invalida ejecutor y oculta acceso antes de esperar/cerrar SQLite.
  Cambiar servidor/token/contexto impide usar la cola anterior con la nueva API.

Drift v4 agrega resultados/códigos a Outbox y `SyncWorkers`. Migración desde
v3 conserva una fila sending con payload, IDs, intentos, efectos y secuencia;
v1/v2 también conserva el catálogo/checkpoints. Nunca resetea la base.

## Pruebas y alcance

| Verificación | Evidencia |
|---|---|
| Suite Flutter completa | 68 pruebas aprobadas; incluye las 46 de fases 2/3 |
| `flutter analyze` | Sin incidencias |
| OpenSpec | Validación estricta aprobada |
| Drift generado | Regeneración reproduce el mismo SHA-256 |
| Worker SQLite | 16 pruebas: ACK/recibos antes/después, mixtos, ACK malformado, 401/402/403, leases, cambio de contexto, migración v3, staging/cursor 410, rollback de recibo inválido y dos conexiones al mismo archivo |
| Disparadores/controlador | 4 pruebas: venta automática sin bloquear otro checkout, startup/reconexión/primer plano, logout durante request y cambio de servidor |
| Bandeja | 2 pruebas de UI, 360×740 y 1200×800: motivo, pendientes, filtro de revisión y botón manual sin overflow |
| Carga/reenvíos | 1000 ventas reales en SQLite local: 500 contado y 500 crédito con inicial; 100 respuestas interrumpidas después de commit central simulado; reapertura a mitad; mismos payloads y 1000 resultados únicos |
| Conciliación de carga | Stock 2000→1000; deuda 40→50040; pagos 30000; balance final 20040. Efectos todos reflejados, sin segunda deducción de inventario/adeudo/inicial |
| Dos cajas | Archivos/contextos/dispositivos distintos: contado/crédito, abono central y cambios de precio/producto/cliente. Cobros y conflictos originales permanecen; cajas convergen por snapshot/recibos |
| API Laravel | `NativePosApiTest`: 29 pruebas, 211 assertions aprobadas; contrato de operaciones, idempotencia, originales, snapshots, bloqueos y escritores centrales |
| macOS nativo | Aprobado: HTTP local, Keychain, SQLite en isolate, respuesta perdida, reopen, GET operación y pull. Aviso al llevar ventana a primer plano; el test sí se ejecutó y pasó |
| iOS nativo | Aprobado en iPhone 16e / simulador iOS 26.2: misma recuperación con Keychain/SQLite reales |
| Android nativo | Aprobado en emulador ARM64 API 36.1: misma recuperación con Keystore/SQLite reales |

Los escenarios de 1000 ventas/100 cortes y dos cajas usan un simulador central
determinista del contrato, no Laravel/MySQL ni carga de producción. El cliente
crea ventas/efectos/Outbox reales en archivos SQLite. El servidor simulado aplica
antes de cortar, comprueba contenido inmutable y cuenta inventario/deuda/pagos.
La API Laravel se prueba por separado con su suite Feature. Las pruebas nativas
usan HTTP loopback y plugins reales, con datos QA aislados que se limpian al
terminar; no equivalen a terminación forzada del proceso ni dispositivos físicos.

La advertencia Drift por instanciar dos bases aparece en pruebas que usan
executors distintos deliberadamente para cajas/ownership. No se comparte un
QueryExecutor entre instancias.

## Reproducir

Desde `clients/agrosys_pos`:

```sh
flutter analyze
flutter test
flutter test integration_test/sync_recovery_test.dart -d macos
flutter test integration_test/sync_recovery_test.dart -d emulator-5554
flutter test integration_test/sync_recovery_test.dart -d <ios-simulator-id>
# En un host Windows:
flutter test integration_test/sync_recovery_test.dart -d windows
```

Desde la raíz:

```sh
php artisan test --filter=NativePosApiTest
openspec validate flutter-pos-mvp --strict --no-interactive
```

SDK instalado verificado: Flutter 3.47.6 / Dart 3.13.5. El workflow de Flutter
incluye recuperación nativa macOS/Windows/Android; no se publicó ni ejecutó CI
remota. Windows, impresora física, cifrado/PIN/recuperación de SQLite, builds
firmados y piloto continúan pendientes. No se desplegó, habilitó ni migró
producción.
