# Especificación funcional

Estado: propuesta. Los requisitos se identifican con `FR`; los escenarios con
`AC`. Las tareas y pruebas deben citar estos identificadores.

## Alcance

Incluye activación online, usuario y sucursal autorizados, catálogo y clientes
existentes, búsqueda por nombre/identificador/código de barras, precio con
descuento del cliente, carrito, ventas de contado y crédito con pago o abono inicial en efectivo, comprobante
local, historial de ventas de este dispositivo y bandeja de sincronización.

El historial central completo, cobros posteriores de crédito, creación de clientes, compras,
devoluciones, transferencias, ajustes de inventario, CFDI, reportes,
procesamiento de tarjetas y cambio de precios quedan para otras specs.
Un borrador se puede descartar; una venta terminada no se borra ni cancela
localmente en este MVP. Su corrección necesita un flujo central auditable.

## Requisitos

- **FR-01 Activación:** primer acceso y descarga inicial requieren conexión.
  Autenticación personal, dispositivo registrado y sucursal autorizada. No
  incluir una API key compartida dentro del instalador.
- **FR-02 Autorización:** permitir operación offline sólo con autorización
  vigente del mismo usuario/dispositivo/sucursal. Al vencer, impedir nuevas
  ventas y conservar la cola. No guardar la contraseña. La política de lectura
  tras vencer debe evitar exposición de datos a usuarios sin autorización.
- **FR-03 Consulta:** leer SQLite aunque exista red. Mostrar última
  actualización y etiqueta de stock estimado. Desactivar productos/clientes
  dados de baja mediante cambios recibidos del servidor.
- **FR-04 Venta:** validar cliente, partidas, cantidades positivas y descuentos.
  Calcular dinero con aritmética decimal y redondeo explícito, no `double`.
  Efectivo recibido y cambio son datos de ticket; en contado el pago aplicado
  es el total, y en crédito es el abono inicial validado.
- **FR-05 Persistencia:** confirmar al usuario sólo después de guardar venta,
  partidas, pago, movimientos, secuencia y Outbox en una transacción SQLite.
  El mismo flujo local se usa online y offline.
- **FR-06 Identidad:** generar `saleId` y `operationId` UUID estables; folio local
  distintivo y secuencia por dispositivo/contexto asignada dentro de la
  transacción. Un reintento no cambia IDs ni contenido.
- **FR-07 Sincronización:** un ejecutor por base/contexto, envío ordenado,
  reintentos con backoff y jitter, consulta de resultado tras respuestas
  ambiguas y recuperación de operaciones `sending` al iniciar.
- **FR-08 Idempotencia:** mismo ID y contenido devuelve resultado original;
  mismo ID con contenido distinto produce conflicto. Una respuesta `duplicate`
  sólo confirma una venta si el resultado original fue exitoso.
- **FR-09 Conflictos:** precio cambiado, cliente/producto inactivo, stock,
  autorización o secuencia deben mostrarse con un motivo accionable. Nunca
  eliminar la venta ni editar silenciosamente una operación ya enviada.
- **FR-10 Existencias:** mostrar snapshot central más movimientos locales que
  todavía no estén incluidos en ese snapshot. Un ACK aislado no permite
  retirar el movimiento local. Nunca subir cantidades absolutas.
- **FR-11 Contexto:** aislar usuario, empresa, sucursal y dispositivo. Logout
  bloquea acceso pero no borra pendientes. Cambio de contexto requiere conexión
  y no reasigna ventas anteriores a otro usuario o sucursal.
- **FR-12 Ticket:** mostrar folio local y estado pendiente/confirmado; conservar
  precios, cliente, partidas y fecha de la venta. Reimprimir no genera venta.
  Impresión física depende del adaptador y equipo acordados.
- **FR-13 Instalación:** arranque sin red después de activación en las cuatro
  plataformas. Actualizar esquema y aplicación sin perder pendientes.
- **FR-14 Operación:** indicar conexión real a AgroSys, pendientes, conflictos,
  última sincronización, sesión vencida y almacenamiento lleno. No prometer
  sincronización continua cuando iOS/Android suspendan la aplicación.

## Escenarios de aceptación

| ID | Dado / Cuando | Resultado verificable |
|---|---|---|
| AC-01 | Instalación nueva sin red | Solicita activación; no crea una sesión ficticia |
| AC-02 | Usuario autorizado, login y bootstrap completados | Catálogo local y contexto quedan consistentes; no almacena contraseña |
| AC-03 | Sin red, catálogo sincronizado | Busca nombre/barcode y muestra precio, fecha y existencia estimada |
| AC-04 | Venta de efectivo, cierre y reapertura antes del envío | Misma venta, folio, pago y Outbox sobreviven; stock descontado una vez |
| AC-05 | Fallo en SQLite durante cierre de venta | Todo se revierte; no aparece venta exitosa ni movimiento huérfano |
| AC-06 | Servidor guarda la venta pero se pierde la respuesta | Reintenta mismo ID; una venta, un pago y un movimiento centrales |
| AC-07 | Dos solicitudes simultáneas con mismo ID | Una sola venta; resultados compatibles; prueba en MySQL |
| AC-08 | Mismo ID con partidas diferentes | Conflicto; conserva original; no duplica ni sobrescribe |
| AC-09 | Precio cambiado o producto eliminado antes de enviar | Conflicto visible con venta y ticket originales intactos |
| AC-10 | Usuario/dispositivo revocado o autorización vencida | Bloquea nuevas ventas según política; conserva pendientes para revisión |
| AC-11 | Se recibe ACK y falla pull; luego se repite pull | Stock estimado no aumenta temporalmente ni se descuenta dos veces |
| AC-12 | Lote con confirmación, conflicto y error temporal | Cada operación obtiene su estado; reintenta sólo las recuperables |
| AC-13 | Datos cambian durante descarga paginada | No pierde cambios; cursor avanza sólo con página aplicada atómicamente |
| AC-14 | Logout/cambio de usuario con cola pendiente | Ninguna venta cambia de propietario ni se envía con otra identidad |
| AC-15 | Reimpresión o doble clic en finalizar | No genera otra venta por la misma finalización |
| AC-16 | Instalación actualizada con pendientes | Migración conserva IDs, payloads, stock y cola |
| AC-17 | Dos cajas sin red venden últimas unidades | Presenta conflicto/stock negativo según política; no promete stock global exacto |
| AC-18 | Inicialización rechazada, disco lleno o permiso denegado | Explica error, no habilita ventas y permite recuperación sin reset destructivo |

## Objetivos medibles de calidad

En equipos de prueba acordados, con 10 000 productos: búsqueda local p95 menor
a 300 ms y cierre local de venta p95 menor a 1 s. Son objetivos del MVP, no
resultados medidos. Probar recuperación con 1 000 operaciones y al menos
100 interrupciones/reenvíos sin pérdidas ni duplicados. Release exige cero
fallos en AC-04 a AC-11 y AC-14 a AC-16; las metas de rendimiento deben medirse.

## Crédito: requisitos y aceptación adicionales

- **FR-15:** venta `Credito` con cliente existente activo y permiso explícito; abono inicial entre cero y total, saldo = total − abono. Efectivo recibido/cambio no aumenta el abono aplicado. Cobros de otras ventas fuera de alcance.
- **FR-16:** mostrar balance sincronizado y adeudo estimado local por cliente, usando el mismo mecanismo de recibos/revisión que el stock. No prometer límite de crédito global offline; no existe configuración de límite en el modelo inspeccionado.

| ID | Dado / Cuando | Resultado verificable |
|---|---|---|
| AC-19 | Crédito total 100, inicial 0, cierre y reapertura sin red | Venta y deuda local 100; inicial cero; una operación pendiente |
| AC-20 | Crédito total 100, inicial 30, respuesta perdida y reenvío | Una venta, un abono 30, incremento neto de adeudo 70 |
| AC-21 | Inicial negativo o mayor al total | Rechazo sin cambios locales ni centrales |
| AC-22 | Crédito total 100, inicial 100 | Saldo cero y venta pagada, sin alterar créditos previos |
| AC-23 | Balance central 40 e incremento local no reflejado 70 | Estimado 110; ACK sin pull no lo reduce; snapshot con recibo conserva 110 |
| AC-24 | Otra caja registra crédito o abono durante el corte | Después del pull se ajusta estimado, sin sobrescribir balance central |

Los requisitos observables de OpenSpec viven en `specs/*/spec.md`; esta matriz es el mapa de pruebas.
