## Purpose

Entregar el resultado de cada auditoría y su histórico, filtrable por
sucursal/bodega, marca, SKU y fecha, exportable a XLSX.

## ADDED Requirements

### Requirement: Reporte por auditoría
El reporte SHALL mostrar encabezado (folio, empresa, sucursal/bodega, alcance,
auditores, fechas), KPIs (renglones, contados, exactitud, faltantes, sobrantes,
neto) y detalle agrupado por marca con subtotales. SHALL permitir filtrar por
marca, SKU y sólo diferencias. La valorización en pesos SHALL mostrarse sólo a
superAdmin y adminEmpresa.

#### Scenario: AC-40 Subtotales por marca
- **WHEN** una auditoría tiene renglones de dos marcas
- **THEN** el reporte muestra subtotales independientes por marca.

#### Scenario: AC-41 Auditor sin valores
- **WHEN** un auditor descarga el reporte
- **THEN** no contiene costos ni importes.

#### Scenario: AC-42 Exportación
- **WHEN** se solicita el XLSX de una auditoría accesible
- **THEN** el sistema responde con el archivo.

### Requirement: Reporte histórico
El sistema SHALL listar auditorías filtrando por empresa, sucursal/bodega,
rango de fechas, marca y SKU, mostrando la diferencia de cada SKU por auditoría.

#### Scenario: AC-43 Filtro por fecha y SKU
- **WHEN** se filtra por rango de fechas y un SKU
- **THEN** sólo aparecen auditorías del rango con ese SKU.
