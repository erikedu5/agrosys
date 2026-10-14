## Purpose

Registrar ventas de contado y crédito con comprobantes recuperables, conservando
integridad de partidas, efectivo, movimientos y operaciones pendientes.

## ADDED Requirements

### Requirement: Cierre local atómico e identidad estable
El cliente SHALL guardar venta, partidas, pago o abono inicial, movimientos,
secuencia y operación pendiente antes de confirmar el cierre. SHALL conservar
los mismos IDs y folio en reaperturas y reintentos, incluso al operar online.

#### Scenario: AC-04 Cierre y reapertura
- **WHEN** se termina una venta offline y se cierra la app antes de enviar
- **THEN** al reabrir persisten venta, folio, pago y cola, con un solo descuento de stock.

#### Scenario: AC-05 Fallo de escritura
- **WHEN** falla una escritura durante el cierre local
- **THEN** se revierte todo el cierre y no se muestra una venta exitosa.

#### Scenario: AC-15 Doble finalización
- **WHEN** el usuario pulsa finalizar dos veces para el mismo cierre
- **THEN** sólo se registra una venta y una operación pendiente.

### Requirement: Validación y cálculo de importes
Cliente y servidor SHALL validar cantidades positivas, cliente activo,
descuento permitido, precios e importes con redondeo decimal acordado.
En contado, el pago aplicado SHALL igualar el total; efectivo recibido y cambio
SHALL no inflar el pago registrado ni el abono del cliente.

#### Scenario: Efectivo recibido mayor al total
- **WHEN** una venta de contado totaliza 100 y recibe 150
- **THEN** registra pago 100 y cambio 50, con total y partidas coherentes.

#### Scenario: Total manipulado
- **WHEN** una operación declara un total incompatible con sus partidas y descuento
- **THEN** el servidor rechaza la operación sin guardar venta, abono ni stock parcial.

### Requirement: Comprobante e historial locales
El cliente SHALL conservar comprobante e historial de ventas del dispositivo,
mostrando folio local, folio central si existe y estado de sincronización.
Reimprimir SHALL no registrar otra venta. Un error de impresión SHALL no
deshacer ni repetir una venta guardada.

#### Scenario: AC-15 Reimpresión sin conexión
- **WHEN** se reabre y reimprime una venta local pendiente
- **THEN** se conserva su comprobante y no aumenta la cantidad de ventas.

### Requirement: Conservación de ventas terminadas
El cliente SHALL permitir descartar borradores sin afectar ventas terminadas.
Una venta terminada SHALL no borrarse ni cancelarse localmente en este MVP;
su corrección deberá pasar por revisión central auditable.

#### Scenario: Intento de borrar una venta pendiente
- **WHEN** el usuario abre una venta terminada pendiente
- **THEN** puede consultar su estado y ticket, pero no eliminarla de la cola.
