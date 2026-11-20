## Purpose

Evitar diferencias falsas bloqueando movimientos no-venta en la sucursal
auditada mientras la auditoría está activa, sin detener las ventas.

## ADDED Requirements

### Requirement: Bloqueo de movimientos no-venta
Mientras una auditoría está `en_conteo` o `en_revision`, el sistema SHALL
rechazar en esa sucursal: crear o recibir transferencias (como origen o
destino), compras, altas manuales, dosificación, reseteo y devoluciones. Las
ventas web, offline y POS SHALL continuar.

#### Scenario: AC-30 Transferencia bloqueada
- **WHEN** se intenta crear o recibir una transferencia que involucra la sucursal auditada
- **THEN** el sistema rechaza con mensaje que incluye el folio de la auditoría.

#### Scenario: AC-31 Venta permitida
- **WHEN** se registra una venta en la sucursal auditada
- **THEN** la venta se guarda normalmente.

#### Scenario: AC-32 Otra sucursal
- **WHEN** se mueve inventario en una sucursal sin auditoría activa
- **THEN** la operación no se ve afectada.

#### Scenario: AC-33 Liberación
- **WHEN** la auditoría se finaliza o cancela
- **THEN** los movimientos vuelven a permitirse.

### Requirement: Permisos POS y aviso en web
`PosAccess::permissions` SHALL retirar permisos de compra, inventario y
transferencia en sucursales bloqueadas. La web SHALL mostrar un aviso en la
sucursal activa bloqueada.

#### Scenario: AC-34 Permisos POS
- **WHEN** un dispositivo POS consulta permisos de una sucursal auditada
- **THEN** no recibe `transfer.create`, `transfer.receive`, `purchase.create`, `inventory.add`, `inventory.dose` ni `inventory.reset`.
