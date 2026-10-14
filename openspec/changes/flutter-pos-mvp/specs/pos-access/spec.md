## Purpose

Permitir acceso personal y operación offline acotada del cliente instalable,
con aislamiento de usuario, empresa, sucursal y dispositivo.

## ADDED Requirements

### Requirement: Activación online y autenticación personal
El cliente SHALL exigir activación online antes de operar offline, usando
credenciales personales y una sucursal autorizada. SHALL almacenar tokens
mediante almacenamiento seguro, sin persistir contraseñas ni API keys comunes.

#### Scenario: AC-01 Instalación nueva sin conexión
- **WHEN** se abre una instalación no activada sin conexión
- **THEN** solicita conectarse para activar y no permite registrar ventas.

#### Scenario: AC-02 Activación válida
- **WHEN** un usuario activo autentica un dispositivo autorizado y termina bootstrap
- **THEN** conserva el contexto y catálogo consistentes sin guardar la contraseña.

### Requirement: Vigencia y revocación
El cliente SHALL permitir nuevas ventas offline sólo con autorización vigente
para su contexto. Al conocer revocación o vencer la autorización SHALL bloquear
nuevas ventas y conservar operaciones pendientes. El servidor SHALL revalidar
permisos y política de aceptación tardía al sincronizar.

#### Scenario: AC-10 Autorización vencida
- **WHEN** vence la autorización durante un corte de red
- **THEN** impide nueva venta, preserva la cola y pide revalidación online.

#### Scenario: Dispositivo revocado
- **WHEN** el servidor informa que el dispositivo fue revocado
- **THEN** bloquea nuevas ventas y muestra pendientes para recuperación autorizada.

### Requirement: Separación de contexto y cierre de sesión
El sistema SHALL aislar los datos de cada usuario/empresa/sucursal/dispositivo.
Cerrar sesión SHALL bloquear su acceso sin borrar ventas pendientes. Un nuevo
usuario SHALL no enviar ni modificar operaciones del usuario anterior.

#### Scenario: AC-14 Cambio de identidad con pendientes
- **WHEN** se cierra sesión con ventas pendientes e inicia sesión otro usuario
- **THEN** las ventas originales conservan propietario y no se envían con el nuevo token.

#### Scenario: Sucursal ajena
- **WHEN** un usuario solicita activar o consultar una sucursal sin permiso
- **THEN** la API devuelve 403 sin exponer catálogo, clientes ni operaciones ajenos.

### Requirement: Consulta offline autorizada
El cliente SHALL permitir consulta local sólo con concesión firmada vigente
para el contexto y permiso `catalog.read`. SHALL bloquear lectura al expirar,
conocer revocación o detectar retroceso significativo de reloj, conservando
catálogo y pendientes hasta revalidación online explícita.

#### Scenario: Concesión vencida durante consulta
- **WHEN** vence la concesión o el servidor informa bloqueo del contexto
- **THEN** el cliente solicita revalidación y no expone el catálogo local.

#### Scenario: Reloj corregido y renovación válida
- **WHEN** el usuario corrige el reloj y obtiene una renovación verificada online
- **THEN** restablece la referencia de reloj y permite consultar datos conservados.
