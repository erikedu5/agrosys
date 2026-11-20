## Why

Las empresas necesitan auditar su inventario físico contra el inventario del
sistema con personal externo. Durante el conteo siguen ocurriendo ventas y
podrían ocurrir transferencias, compras o ajustes que generan diferencias
falsas. Los auditores externos no deben poder modificar inventario y el
resultado debe quedar como reporte por sucursal/bodega, marca, SKU y fecha.

## What Changes

- Agregar rol `auditor` con alcance por empresa y, opcionalmente, por sucursal.
  superAdmin crea auditores multiempresa; adminEmpresa crea auditores sólo de
  su empresa. Vigencia opcional (`acceso_hasta`).
- Agregar auditorías de inventario que el auditor abre eligiendo empresa (si
  tiene varias) y sucursal/bodega, con alcance total, por marca, clasificación
  o productos.
- Conteo ciego con capturas append-only por auditor y ubicación, cierre de
  conteo, conciliación y reconteo de renglones con diferencia.
- Bloqueo híbrido: mientras la auditoría está activa se bloquean transferencias,
  compras, altas manuales, dosificación, reseteo y devoluciones de la sucursal;
  las ventas continúan y se descuentan en la conciliación.
- Reporte por auditoría (pantalla y XLSX) y reporte histórico filtrable.
- El módulo **no** escribe movimientos en `alta_inventarios`: sólo reporta.

## Capabilities

### New Capabilities

- `rol-auditor`: alta, alcance, vigencia y acceso restringido del auditor externo.
- `auditoria-conteo`: ciclo de vida de la auditoría, snapshot, captura ciega, cierre, reconteo y conciliación.
- `auditoria-bloqueo`: bloqueo de movimientos no-venta en la sucursal auditada (web y POS).
- `auditoria-reporte`: reporte por auditoría y consolidado, exportable a XLSX.

### Modified Capabilities

Ninguna spec existente. Se modifican servicios centrales de inventario,
transferencias, compras y devoluciones para consultar el bloqueo, y la
administración de usuarios para validar roles en servidor.

## Impact

Migraciones nuevas (`auditor_alcances`, `auditorias`, `auditoria_auditores`,
`auditoria_items`, `auditoria_conteos`, `users.acceso_hasta`, índice compuesto
en `alta_inventarios`). Servicios bajo `app/Services/Auditoria/`. Páginas Vue
bajo `resources/js/Pages/Auditoria/`. Login, suscripción, POS y menú reconocen
el rol `auditor`.

Fuera de alcance v1: captura desde la app Flutter, captura offline completa y
aplicación automática de ajustes de inventario.
