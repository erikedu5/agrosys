## Purpose

Permitir ventas a crédito a clientes existentes y consultar su adeudo estimado,
sin duplicar cargos ni abonos iniciales al sincronizar varios dispositivos.

## ADDED Requirements

### Requirement: Crédito y abono inicial
El cliente SHALL permitir venta a crédito sólo para cliente activo y usuario
autorizado. El abono inicial en efectivo SHALL estar entre cero y total.
SHALL presentar saldo de venta como total menos abono y conservar estos
valores junto con la operación de venta.

#### Scenario: AC-19 Crédito sin inicial
- **WHEN** se termina una venta a crédito de 100 con inicial cero y se reabre offline
- **THEN** conserva saldo 100, sin inventar pago ni perder su operación pendiente.

#### Scenario: AC-21 Inicial inválido
- **WHEN** el abono inicial es negativo o mayor al total
- **THEN** se rechaza el cierre sin modificar venta, cuenta ni inventario.

#### Scenario: AC-22 Inicial completo
- **WHEN** una venta a crédito de 100 recibe inicial 100
- **THEN** queda pagada con saldo cero y no altera créditos anteriores.

### Requirement: Cargo y abono centrales idempotentes
El servidor SHALL aplicar venta a crédito, incremento de adeudo, abono inicial
e inventario en una transacción idempotente. Reenvíos SHALL no aumentar dos
veces el adeudo ni registrar dos abonos por la misma operación.

#### Scenario: AC-20 Respuesta perdida en crédito
- **WHEN** se envía crédito de 100 con inicial 30, se pierde respuesta y se reenvía
- **THEN** existe una venta, un abono inicial 30 y un incremento neto de adeudo 70.

### Requirement: Balance estimado y reconciliación
El cliente SHALL mostrar balance central sincronizado más variaciones locales
aún no incluidas, identificándolo como estimado. Un ACK SHALL no retirar un
cargo local hasta que el snapshot de cuenta pruebe su incorporación.

#### Scenario: AC-23 Cargo confirmado sin snapshot
- **WHEN** el balance central es 40, existe cargo local 70 y llega ACK sin pull
- **THEN** mantiene estimado 110; al incorporar cargo y recibo en snapshot conserva 110.

#### Scenario: AC-24 Otra caja modifica la cuenta
- **WHEN** otra caja registra crédito o abono mientras este cliente está offline
- **THEN** el siguiente pull incorpora el cambio sin sobrescribir la cuenta central.

### Requirement: Alcance acotado del crédito offline
El MVP SHALL registrar únicamente el abono inicial de la venta nueva, sin
cobrar créditos anteriores ni crear clientes offline. SHALL no afirmar que
garantiza un límite global de crédito entre dispositivos desconectados.

#### Scenario: Consulta de cuenta durante corte
- **WHEN** se consulta el adeudo de un cliente sin conexión
- **THEN** se muestra saldo estimado y fecha de referencia, sin ofrecer cobro de crédito anterior.
