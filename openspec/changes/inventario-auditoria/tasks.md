# Tareas

Pruebas: `tests/Feature/Auditoria/*` (30 pruebas). Suite completa verde.

## 1. Datos y rol auditor

- [x] 1.1 Migración `auditor_alcances`, `auditorias`, `auditoria_auditores`, `auditoria_items`, `auditoria_conteos`, `users.acceso_hasta` e índice `alta_inventarios (id_sucursal, id_producto, id)`.
- [x] 1.2 Modelos `Auditoria`, `AuditoriaItem`, `AuditoriaConteo`, `AuditorAlcance`; helpers `User::isAuditor()`, `accesoVigente()`.
- [x] 1.3 Validación de roles en servidor por editor en `UsuarioController` y alta/edición de alcance (rol-auditor, AC-01..04).
- [x] 1.4 Login de auditor con vigencia y alcance; exención de suscripción a nivel middleware; redirección de dashboard (AC-05, AC-07).
- [x] 1.5 Menú "Auditorías" y formulario de usuario con alcance y vigencia.
- [x] 1.6 Pruebas de rol y acceso (AC-01..07) — `RolAuditorTest`.

## 2. Flujo de auditoría

- [x] 2.1 Crear por auditor con selección de empresa/sucursal y máximo una activa por sucursal (AC-10, AC-11).
- [x] 2.2 Iniciar con corte del ledger y snapshot del alcance; aviso de transferencias pendientes (AC-12).
- [x] 2.3 Captura ciega append-only, ubicaciones, anulación, productos no esperados y no registrados (AC-13..15).
- [x] 2.4 Cerrar conteo con confirmación de no contados en cero (AC-18).
- [x] 2.5 Reconteo ronda 2, finalizar sin ajustes y cancelar con motivo (AC-19, AC-20).
- [x] 2.6 Páginas `Auditoria/Index`, `Create`, `Show`, `Captura`.
- [x] 2.7 Pruebas de flujo — `AuditoriaFlujoTest`.

## 3. Bloqueo híbrido

- [x] 3.1 `InventoryFreeze` en transferencias (crear/recibir), compras, altas, dosificación, reseteo y devoluciones (AC-30, AC-32, AC-33).
- [x] 3.2 Observer `creating` en `AltaInventario` como respaldo; ventas permitidas (AC-31).
- [x] 3.3 Permisos POS recortados durante auditoría (AC-34).
- [x] 3.4 Aviso y error de bloqueo globales en `AppLayout`.
- [x] 3.5 Pruebas de bloqueo — `AuditoriaBloqueoTest`.

## 4. Conciliación y reporte por auditoría

- [x] 4.1 `AuditoriaConciliacion`: esperado al corte de captura, desglose de movimientos, ventas offline tardías, tránsito (AC-16, AC-17).
- [x] 4.2 `AuditoriaReport`: KPIs, agrupado por marca, filtros marca/SKU/diferencias, costos sólo admin (AC-40, AC-41).
- [x] 4.3 XLSX (`AuditoriaExport`) (AC-42). PDF descartado: dompdf excede la memoria con auditorías de inventario total.

## 5. Histórico

- [x] 5.1 `Auditoria/Historico` con filtros empresa, sucursal/bodega, fechas, marca y SKU; exportación XLSX (AC-43).
- [x] 5.2 Pruebas de reporte e histórico — `AuditoriaReporteTest`.

## 6. Pendiente

- [ ] 6.1 Prueba manual en navegador (celular con lector de código) y MySQL real.
- [ ] 6.2 Captura desde app Flutter / captura offline (fuera de alcance v1).
