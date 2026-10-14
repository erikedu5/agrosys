## Purpose

Ofrecer consulta persistente del catálogo autorizado y de existencias estimadas
sin depender de la disponibilidad inmediata del servidor AgroSys.

## ADDED Requirements

### Requirement: Búsqueda local y vigencia visible
El cliente SHALL consultar el catálogo local por nombre, identificador y código
de barras tanto online como offline. SHALL mostrar precio, última actualización
y existencia etiquetada como estimada, sin presentar el stock local como global exacto.

#### Scenario: AC-03 Búsqueda durante corte
- **WHEN** se busca un producto conocido sin conexión tras bootstrap
- **THEN** aparece con precio, fecha de sincronización y stock estimado.

### Requirement: Descarga consistente y bajas
El sistema SHALL aplicar páginas de catálogo, bajas y checkpoint de forma
atómica. Un producto o cliente dado de baja SHALL dejar de ser seleccionable
después de aplicar la actualización, conservando las referencias históricas.

#### Scenario: AC-13 Cambios durante descarga
- **WHEN** se actualiza un producto durante una descarga paginada
- **THEN** la actualización aparece en ese snapshot o en el siguiente, sin quedar omitida.

#### Scenario: Baja de producto con venta histórica
- **WHEN** se recibe la baja de un producto usado en una venta local
- **THEN** deja de ofrecerse para nuevas ventas y el ticket histórico permanece intacto.

### Requirement: Reconciliación de existencias
La existencia estimada SHALL ser snapshot central más movimientos locales aún
no incluidos en él. Una confirmación de venta SHALL no retirar el movimiento
local hasta que una revisión de snapshot pruebe su incorporación.

#### Scenario: AC-11 ACK sin descarga
- **WHEN** se confirma una venta y falla la descarga de stock
- **THEN** la existencia estimada conserva el descuento local sin aumentar artificialmente.

#### Scenario: AC-17 Venta simultánea offline en dos cajas
- **WHEN** dos cajas desconectadas venden las últimas unidades
- **THEN** al sincronizar se aplica la política explícita de stock y se muestra cualquier conflicto.
