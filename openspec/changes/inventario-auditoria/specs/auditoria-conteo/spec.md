## Purpose

Registrar el conteo físico de una sucursal/bodega y conciliarlo contra el
sistema sin perder exactitud por movimientos ocurridos durante el conteo.

## ADDED Requirements

### Requirement: Apertura por el auditor
El auditor SHALL crear la auditoría eligiendo empresa (sólo si su alcance tiene
más de una) y sucursal/bodega dentro de su alcance. SHALL existir como máximo
una auditoría activa por sucursal.

#### Scenario: AC-10 Sucursal fuera de alcance
- **WHEN** un auditor intenta crear una auditoría en una sucursal fuera de su alcance
- **THEN** el sistema responde 403.

#### Scenario: AC-11 Segunda auditoría activa
- **WHEN** ya existe una auditoría `borrador`, `en_conteo` o `en_revision` en la sucursal
- **THEN** el sistema rechaza la nueva auditoría.

### Requirement: Snapshot al iniciar
Al iniciar, el sistema SHALL guardar `corte_inicio_id` y crear un renglón por
producto del alcance (incluyendo existencia cero) con su existencia al corte y
datos descriptivos congelados.

#### Scenario: AC-12 Snapshot por corte
- **WHEN** se inicia una auditoría y después se registra una venta
- **THEN** `cantidad_sistema_inicio` conserva la existencia previa a la venta.

### Requirement: Captura ciega y append-only
Las capturas SHALL sumarse por renglón y ronda, guardar usuario, ubicación y
corte del ledger. Corregir SHALL ser anular y capturar de nuevo. Antes de
`en_revision` ningún endpoint de captura SHALL exponer existencias del sistema.

#### Scenario: AC-13 Varias ubicaciones
- **WHEN** se capturan 3 y 2 unidades del mismo producto en dos ubicaciones
- **THEN** la cantidad contada del renglón es 5.

#### Scenario: AC-14 Captura anulada
- **WHEN** se anula una captura
- **THEN** deja de sumar pero permanece en el historial.

#### Scenario: AC-15 Conteo ciego
- **WHEN** el auditor busca un producto durante `en_conteo`
- **THEN** la respuesta no incluye existencias del sistema.

### Requirement: Conciliación con movimientos durante el conteo
Al cerrar el conteo y tras cada reconteo, el sistema SHALL calcular esperado y
diferencia según el corte de la última captura, descontando ventas offline con
`occurred_at` previo a la captura sincronizadas después.

#### Scenario: AC-16 Venta durante el conteo
- **WHEN** hay 10 al iniciar, se venden 2 y luego se cuentan 8
- **THEN** la diferencia es 0.

#### Scenario: AC-17 Venta offline tardía
- **WHEN** una venta offline ocurrida antes de la captura se sincroniza después
- **THEN** se descuenta del esperado y no genera faltante.

#### Scenario: AC-18 Renglón no contado
- **WHEN** se cierra el conteo confirmando que los no contados valen cero
- **THEN** esos renglones quedan con contado 0 y su diferencia negativa.

### Requirement: Reconteo y finalización
En `en_revision` el auditor SHALL poder marcar renglones para reconteo; la ronda
2 reemplaza a la 1 sin borrarla. Finalizar SHALL volver la auditoría inmutable.
Ningún paso SHALL crear movimientos de inventario.

#### Scenario: AC-19 Reconteo
- **WHEN** se recuenta un renglón con 7 tras haber contado 5 en ronda 1
- **THEN** el contado vigente es 7 y el reporte conserva ambas rondas.

#### Scenario: AC-20 Sin ajustes
- **WHEN** se finaliza una auditoría con diferencias
- **THEN** no se crean renglones en `alta_inventarios`.
