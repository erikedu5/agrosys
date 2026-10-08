## Purpose

Distribuir un cliente instalable y actualizable en Windows, macOS, iOS y Android,
preservando las operaciones locales durante reinicios y cambios de versión.

## ADDED Requirements

### Requirement: Interfaz consistente con la web
El cliente SHALL usar la identidad visual de AgroSys y el flujo de venta de
la web, con catálogo de tarjetas, carrito lateral en escritorio y panel de
carrito en móvil. Los recursos de marca SHALL estar disponibles offline.

#### Scenario: Venta responsive con navegación
- **WHEN** se agregan productos y se cambia entre consulta y venta en móvil o escritorio
- **THEN** conserva el carrito y permite completar contado o crédito con el mismo cálculo monetario.

### Requirement: Operación en las cuatro plataformas
El MVP SHALL contar con builds y validación en Windows, macOS, iOS y Android.
Tras activación SHALL arrancar y consultar datos sin conexión. La interfaz
SHALL permitir operación táctil en móvil y teclado en escritorio.

#### Scenario: Apertura offline por plataforma
- **WHEN** se instala, activa, cierra y abre la app sin red en cada target
- **THEN** abre el contexto autorizado y catálogo persistido sin depender de Laravel local.

### Requirement: Actualización sin pérdida de pendientes
Una actualización SHALL migrar almacenamiento preservando identidades,
payloads y operaciones pendientes. Si la migración falla SHALL impedir ventas
y ofrecer recuperación sin reset destructivo automático.

#### Scenario: AC-16 Actualización con cola pendiente
- **WHEN** se actualiza la aplicación con ventas todavía sin sincronizar
- **THEN** se preservan folios, IDs, pagos, adeudos, stock estimado y cola.

#### Scenario: AC-18 Falta de espacio
- **WHEN** la inicialización o migración falla por almacenamiento lleno
- **THEN** explica el fallo, no permite nuevas ventas y conserva datos recuperables.
