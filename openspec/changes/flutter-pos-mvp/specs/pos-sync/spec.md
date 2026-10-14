## Purpose

Sincronizar operaciones persistidas con resultados idempotentes, recuperación
ante cortes y conflictos visibles, sin perder ventas, pagos ni movimientos.

## ADDED Requirements

### Requirement: Reintentos y recuperación de envío
El cliente SHALL enviar operaciones en orden por contexto con un único
ejecutor y reintentar errores temporales conservando IDs y payload. SHALL
recuperar envíos interrumpidos tras reinicio y separar fallos de red de
autenticación, suscripción o reglas de negocio.

#### Scenario: AC-06 Respuesta perdida
- **WHEN** el servidor confirma y la respuesta no llega
- **THEN** el cliente consulta o reenvía la misma operación y conserva una única venta central.

#### Scenario: AC-12 Resultados mixtos
- **WHEN** un lote contiene confirmación, conflicto y error temporal
- **THEN** aplica cada resultado por separado y sólo reintenta automáticamente el error temporal.

### Requirement: Idempotencia con verificación de contenido
El servidor SHALL devolver el resultado original para mismo ID y contenido,
incluyendo su estado original. Mismo ID con contenido distinto SHALL producir
conflicto. Solicitudes concurrentes SHALL no duplicar venta, abono ni stock.

#### Scenario: AC-07 Solicitudes concurrentes
- **WHEN** dos requests simultáneos contienen la misma operación
- **THEN** registran una venta y retornan resultados compatibles con esa venta.

#### Scenario: AC-08 Operación alterada
- **WHEN** se reusa operationId con partidas diferentes
- **THEN** retorna conflicto y conserva el contenido original sin nuevos movimientos.

#### Scenario: Duplicado de operación conflictiva
- **WHEN** se reenvía una operación cuyo resultado original fue conflicto
- **THEN** no se marca la venta como confirmada ni se inventa folio central.

### Requirement: Conflictos persistentes
El cliente SHALL mostrar la venta y motivo de conflicto conservando payload,
ticket e IDs originales. Cambiar precio o cliente después del envío SHALL
requerir resolución explícita, nunca edición silenciosa ni reintento con nuevo ID.

#### Scenario: AC-09 Cambio de precio
- **WHEN** cambia el precio central antes de procesar una venta pendiente
- **THEN** se muestra conflicto con el importe cobrado original intacto.

### Requirement: Checkpoint y recibos consistentes
El servidor SHALL entregar páginas y bajas con checkpoint consistente y
evidencia de qué operaciones están incluidas en snapshots de stock y cuenta.
El cliente SHALL guardar páginas en staging durable y aplicar el snapshot
completo, recibos y cursor en una sola transacción. Los recibos y nextCursor
SHALL entregarse en la última página; una descarga incompleta SHALL no
avanzar cursor ni reemplazar datos centrales.

#### Scenario: AC-11 Descarga repetida
- **WHEN** se descarga dos veces la misma página tras un cierre inesperado
- **THEN** stock y adeudos mantienen el mismo estimado y no duplican movimientos.

#### Scenario: Cursor vencido con ventas pendientes
- **WHEN** el servidor exige un snapshot completo por cursor vencido
- **THEN** renueva catálogo y snapshots sin borrar Outbox ni perder movimientos no reflejados.

### Requirement: Sincronización observable y primer plano
El cliente SHALL mostrar última sincronización, pendientes y conflictos,
permitir reintento manual y reanudar al volver a primer plano. SHALL no
depender de ejecución continua mientras el sistema operativo suspenda la app.

#### Scenario: Regreso a primer plano
- **WHEN** la app vuelve al primer plano tras un corte con pendientes
- **THEN** verifica autorización y servidor, reanuda sincronización y muestra su resultado.
