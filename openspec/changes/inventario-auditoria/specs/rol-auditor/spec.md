## Purpose

Permitir que personal externo audite inventario con un rol propio, alcance
explícito por empresa/sucursal y sin capacidad de modificar inventario.

## ADDED Requirements

### Requirement: Alta de auditores según el rol del creador
El sistema SHALL validar en servidor el `tipo` asignado según quien crea o
edita: superAdmin puede asignar cualquier rol y alcance multiempresa;
adminEmpresa puede crear auditores sólo con alcance en su empresa y no puede
asignar `superAdmin` ni `adminEmpresa`; admin no puede crear auditores ni roles
superiores.

#### Scenario: AC-01 adminEmpresa crea auditor de su empresa
- **WHEN** un adminEmpresa crea un auditor con alcance en su empresa
- **THEN** el usuario queda con `tipo=auditor` y un alcance en esa empresa.

#### Scenario: AC-02 adminEmpresa intenta alcance ajeno
- **WHEN** un adminEmpresa envía un alcance con otra empresa
- **THEN** el sistema rechaza la solicitud con error de validación.

#### Scenario: AC-03 admin intenta escalar privilegios
- **WHEN** un admin envía `tipo=superAdmin` o `tipo=auditor`
- **THEN** el sistema rechaza la solicitud con error de validación.

#### Scenario: AC-04 superAdmin crea auditor multiempresa
- **WHEN** superAdmin asigna dos empresas al auditor
- **THEN** el auditor puede elegir cualquiera de ellas al abrir una auditoría.

### Requirement: Acceso restringido del auditor
El auditor SHALL iniciar sesión sólo si tiene al menos un alcance con empresa
activa y su `acceso_hasta` no ha vencido. SHALL acceder únicamente al módulo de
auditorías; las rutas de inventario, ventas, transferencias, compras y la API
POS SHALL responder 403. El auditor no SHALL contar en el límite de
dispositivos de la sucursal.

#### Scenario: AC-05 Vigencia vencida
- **WHEN** un auditor con `acceso_hasta` en el pasado intenta iniciar sesión
- **THEN** el login falla con mensaje de acceso vencido.

#### Scenario: AC-06 Rutas operativas
- **WHEN** un auditor solicita `/inventario`, `/transferencias` o `/compra`
- **THEN** recibe 403.

#### Scenario: AC-07 Inicio
- **WHEN** un auditor entra a `/dashboard`
- **THEN** es redirigido a la lista de auditorías.
