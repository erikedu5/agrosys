# Investigación y brechas

Inspección del repositorio: 2026-10-06. Estas observaciones describen código,
no certifican comportamiento en producción.

## Base reutilizable

| Evidencia | Capacidad / límite |
|---|---|
| `routes/web.php`, `OfflineController` | Health, bootstrap, push, pull, operación y heartbeat existen bajo `/api/v1/offline`, pero pertenecen al grupo web |
| `OfflineSaleProcessor` | Transacción central, bloqueo de operación, venta y movimientos; usa modelos de negocio existentes |
| Migración de persistencia offline | UUID de venta/operación y unicidad por dispositivo/secuencia |
| `User` | Ya incluye `HasApiTokens` de Sanctum |
| `SucursalService`, `CheckSucursalSelection` | Administrador de empresa depende parcialmente de sesión web |
| `Producto::booted()` | Scope de empresa basado en contexto; se omite en consola. Las pruebas deben verificar explícitamente aislamiento |
| `resources/js/Offline/` | Casos de dominio y ejemplos de Outbox reutilizables conceptualmente; código JavaScript debe reimplementarse en Dart |
| `config/offline.php` | Ventas y push deshabilitados por defecto; vigencia siete días y stock negativo permitido por defecto |

## Brechas que se deben resolver antes del piloto

1. No se encontró endpoint dedicado de login nativo en `routes/api.php`.
   Crear autenticación por token, selección explícita de sucursal, autorización
   de dispositivo y respuestas JSON; reutilizar reglas de cuentas activas y
   suscripción, evitando redirects web y dependencia de cookies/CSRF.
2. `push` identifica dispositivo/usuario/sucursal, pero no compara una huella
   del payload de una operación existente. Sus races en `firstOrCreate` y
   secuencias deben probarse con MySQL; no basta la prueba secuencial actual.
3. Una operación con `result` vuelve como `duplicate`; el contrato nativo debe
   conservar además su resultado original. `duplicate` no equivale siempre
   a confirmación satisfactoria.
4. `OfflineSaleProcessor` acepta totales de partidas y total de venta del
   payload y sólo usa el primer pago. Debe recalcular/verificar importes,
   finitud, cantidades, descuento y método de pago antes del cliente nativo.
5. El bootstrap puede volver a asignar el dispositivo y renovar autorización
   mediante `fill`. Registro, revocación y renovación necesitan reglas
   independientes; la descarga de catálogo no debe reactivar dispositivos.
6. `credential` usa `Crypt::encryptString`: el cliente no puede tratar ese blob
   como una autorización verificable con clave pública. Definir concesión
   offline firmada o política local explícita; distinguir token de API y lease.
7. El pull filtra por timestamp y genera cursor al final. Una actualización
   durante descarga puede quedar fuera de la siguiente ventana. Definir
   checkpoint consistente, paginación y bajas; revisar expiración del cursor.
8. No existe contrato explícito que relacione stock descargado con las ventas
   locales ya aplicadas. Hace falta revisión/recibos para evitar doble conteo.
9. La credencial offline no se verifica en el push actual. Definir aceptación
   de ventas ocurridas durante vigencia y enviadas después; no confiar sólo
   en `occurred_at` proporcionado por cliente.
10. La API `/api/sucursales/{sucursal}/productos` usa API key. No incrustarla
    en Flutter; el cliente requiere permisos personales por empresa/sucursal.

Las correcciones pueden vivir en un adaptador nativo y servicios compartidos.
No cambiar el contrato PWA sin pruebas de compatibilidad.

## Entorno y riesgos

OpenSpec CLI 1.6.0 está instalado; se inicializó el esquema `spec-driven`. No se encontró `flutter` ni `dart` en PATH durante esta revisión. MySQL local
rechazó conexión en el trabajo anterior. La suite PHP tenía 23 fallos
preexistentes; separar ese baseline de las pruebas nuevas de API.

La validación de Windows necesita equipo/runner Windows; Apple requiere
toolchain y firma apropiadas. Faltan dispositivos iOS/Android, equipo Windows
y modelo de impresora. No comprometer soporte USB/Bluetooth antes de una
prueba física. Guardar tokens en secure storage no cifra por sí solo SQLite.

## Fuentes primarias consultadas

- [Arquitectura Flutter](https://docs.flutter.dev/app-architecture/guide):
  separar vistas, estado, repositorios y servicios.
- [Offline-first Flutter](https://docs.flutter.dev/app-architecture/design-patterns/offline-first):
  persistencia local y sincronización son responsabilidades explícitas.
- [Plataformas Drift](https://drift.simonbinder.eu/platforms/): su backend
  nativo SQLite cubre Android, iOS, Windows y macOS; candidato para este MVP.
- [flutter_secure_storage](https://pub.dev/packages/flutter_secure_storage):
  mecanismos de almacenamiento seguro por plataforma para credenciales.
- [Sanctum Laravel 11](https://laravel.com/docs/11.x/sanctum): autenticación
  de aplicaciones móviles mediante tokens personales y abilities.
- [Despliegue Flutter](https://docs.flutter.dev/deployment): releases por
  plataforma requieren sus propias herramientas y pasos.

Las versiones concretas se fijarán durante el spike y quedarán en lockfiles.

## Crédito actual

`VentaController::store` y `OfflineSaleProcessor` contemplan `Credito` y abono inicial; `Clientes` contiene `adeudo_total`, `abono_total` y `balance`, sin límite de crédito inspeccionado. El bootstrap actual no incluye balances y la ruta offline no comparte necesariamente la normalización de venta pagada que usa el flujo web. Antes del MVP se prueban ambos contra el mismo contrato de dinero y crédito.

[OpenSpec spec-driven](https://openspec.dev/docs/schemas/spec-driven) define proposal, specs por capacidad, design y tasks. El cambio permanece activo y no se archiva durante planificación.
