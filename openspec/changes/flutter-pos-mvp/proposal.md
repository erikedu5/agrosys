## Why

AgroSys necesita que los vendedores consulten productos y registren ventas de
contado y crédito aunque se interrumpa internet o se cierre la aplicación.
Un cliente Flutter con persistencia local permitirá ese flujo en Windows,
macOS, iOS y Android, sincronizando con el servidor central existente.

## What Changes

- Agregar un cliente Flutter instalable con consulta local y punto de venta.
- Guardar cada venta, pago/abono inicial, adeudo, inventario y cola de envío
  de forma atómica antes de contactar al servidor.
- Agregar autenticación nativa, contexto de sucursal y autorización offline.
- Reutilizar y reforzar el motor central de ventas y sincronización.
- Definir resultados idempotentes, revisión de conflictos y conciliación de
  snapshots con movimientos locales, tanto para inventario como adeudos.
- Mantener la aplicación web/PWA y sus contratos existentes.
- Producir primero propuesta, specs, diseño y tareas. Posteriormente el usuario
  autorizó fase 1 backend, implementada y verificada; cliente Flutter pendiente.

## Capabilities

### New Capabilities

- `pos-access`: autenticación nativa, dispositivo y autorización por contexto.
- `pos-catalog`: consulta local de productos, precios, clientes y existencias.
- `pos-sales`: venta persistente, efectivo, ticket e historial del dispositivo.
- `pos-credit`: crédito con abono inicial y consulta de adeudo estimado.
- `pos-sync`: envío idempotente, descarga consistente y conflictos visibles.
- `pos-distribution`: instalación y actualización en las cuatro plataformas.

### Modified Capabilities

Ninguna spec existente: el repositorio no tenía capacidades OpenSpec.
Los servicios Laravel actuales se adaptarán con pruebas de compatibilidad.

## Impact

Nuevo proyecto propuesto `clients/agrosys_pos/`; adaptador de API Laravel bajo
`/api/v1/pos`; modelos, validaciones, autorización y checkpoints de sincronización
centrales; SQLite y almacenamiento seguro en cliente; infraestructura de builds
y QA por plataforma. No distribuir Laravel/MySQL dentro de los instaladores.

Fuera de alcance: compras, devoluciones, CFDI, administración, reportes,
terminal bancaria, cobros posteriores de crédito y nuevos clientes offline.
Impresión física depende de equipo acordado. La consulta de adeudo offline
no garantiza un límite global entre dispositivos desconectados.
