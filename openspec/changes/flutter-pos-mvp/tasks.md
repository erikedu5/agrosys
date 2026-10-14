## 0. Decisiones y spike

- [ ] 0.1 Revisar propuesta/specs y cerrar vigencia, stock negativo, política de crédito y límites; registrar decisiones en design.md.
- [ ] 0.2 Acordar impresora/conexión, dispositivos de QA, canal de distribución y cifrado/desbloqueo local.
- [ ] 0.3 Preparar Flutter/Dart y matriz de toolchains, fijar dependencias y lockfiles.
- [ ] 0.4 Spike SQLite y secure storage con reopen en Windows/macOS/iOS/Android; registrar evidencia (pos-access, pos-distribution).
- [x] 0.5 Cerrar fixtures de dinero, descuento y crédito compartidos cliente/servidor; decidir escala de cantidades y conciliación de saldos.
- [ ] 0.6 Validar contrato de checkpoints/recibos y todos los escritores centrales; actualizar contracts.md antes de código de sync.

## 1. Backend para cliente nativo

- [x] 1.1 Implementar login/challenge/logout por Sanctum y tests de token, rate limit, cuentas/suscripción y segundo factor (AC-01/02/10).
- [x] 1.2 Implementar contexto explícito de empresa/sucursal y políticas por recurso sin sesión web (AC-14; pos-access).
- [x] 1.3 Separar activar, renovar y revocar dispositivo; emitir concesión offline y definir recepción tardía (AC-10).
- [x] 1.4 Recalcular/verificar dinero y pagos de contado/crédito en servicio compartido; coherencia con venta web (AC-20/21/22).
- [x] 1.5 Agregar huella del payload y resultado original de idempotencia; manejar carreras de operación/secuencia en MySQL real (AC-06/07/08).
- [x] 1.6 Implementar bootstrap paginado con cliente por defecto, catálogo autorizado, stock, cuentas y recibos (AC-02/13/23).
- [x] 1.7 Implementar pull/checkpoints/bajas incluyendo cambios desde web, compras, abonos, devoluciones y transferencias (AC-11/13/24).
- [x] 1.8 Publicar contrato verificable/OpenAPI y fixtures; ejecutar regresiones PWA separando fallos de baseline.

## 2. Cliente de consulta

- [x] 2.1 Crear `clients/agrosys_pos/`, navegación responsive y entornos API de prueba/producción (pos-distribution).
- [x] 2.2 Implementar credenciales seguras, activación y permisos por contexto (AC-01/02/10/14).
- [x] 2.3 Implementar esquema Drift, repositorios, transacciones y migración con reopen (AC-16/18).
- [x] 2.4 Aplicar bootstrap atómico/paginado y estados de descarga recuperables (AC-02/13).
- [x] 2.5 Implementar consulta por nombre/identificador/barcode, clientes y última sincronización (AC-03).
- [x] 2.6 Implementar stock y adeudo estimados con revisión/recibos (AC-11/23).
- [ ] 2.7 Demostrar primera entrega: activar, descargar, cerrar, abrir sin red y consultar en cuatro targets.

## 3. Venta contado y crédito

- [x] 3.1 Implementar carrito/cliente/descuentos y motor decimal con fixtures compartidos (pos-sales, AC-21).
- [x] 3.2 Implementar efectivo recibido/cambio e inicial 0..total para crédito (AC-19/20/21/22).
- [x] 3.3 Implementar finalización local con UUID, secuencia, folio, pago, stock, cuenta y Outbox atómicos (AC-04/05/15).
- [x] 3.4 Implementar historial/ticket/reimpresión, estados visibles y prohibición de borrar venta terminada (AC-15).
- [x] 3.5 Implementar advertencias por stock y saldo estimado; autorización local de crédito (AC-10/17/23).
- [x] 3.6 Probar reopen, fallo de escritura, doble clic y ticket fallido sin duplicados (AC-04/05/15/18).

## 4. Sincronización y recuperación

- [x] 4.1 Implementar worker único con claim/lease recuperable, orden, backoff y jitter (pos-sync).
- [x] 4.2 Implementar push/consulta por operación y confirmación basada en estado original (AC-06/07/08/12).
- [x] 4.3 Aplicar ACK sin retirar deltas hasta checkpoint probado; manejar pull antes/después de ACK (AC-11/23).
- [x] 4.4 Implementar pull paginado, bajas, cursor vencido y staging de páginas y commit completo de datos/recibos/cursor (AC-11/13/24).
- [x] 4.5 Implementar bandeja de pendientes/conflictos y separación de auth requerida, suscripción y errores temporales (AC-09/10/12/14).
- [x] 4.6 Implementar triggers de inicio/primer plano/red/manual; no depender de background móvil continuo (pos-sync).
- [x] 4.7 Probar 1 000 operaciones y 100 cortes/reenvíos: contado y crédito sin duplicar venta, inventario, adeudo o inicial.
- [x] 4.8 Probar dos cajas offline y cambios centrales de precio, producto, cliente y abonos (AC-09/17/24).

## 5. Piloto y distribución

- [x] 5.0 Adaptar interfaz Flutter a la web: identidad visual, menú, catálogo, carrito responsive y cobro contado/crédito.
- [ ] 5.1 Integrar y probar físicamente adaptador de impresora acordado; ticket pendiente/confirmado y reimpresión.
- [ ] 5.2 Ejecutar matriz AC-01 a AC-24 en dispositivos/OS acordados y medir p95 de búsqueda/cierre.
- [ ] 5.3 Ejecutar migración/actualización con cola pendiente y recuperación sin reset (AC-16/18).
- [ ] 5.4 Preparar builds internos y firma de las cuatro plataformas; documentar requisitos y canales (pos-distribution).
- [ ] 5.5 Piloto en una sucursal con ventas contado/crédito y conciliación diaria de efectivo, adeudos e inventario.
- [ ] 5.6 Registrar evidencia, resolver defectos y validar specs; archivar el cambio OpenSpec sólo después de implementación aceptada.

Las fases 1–4 se implementaron por instrucción del usuario. La consulta
(2.1–2.6) está implementada; 2.7 sigue abierta hasta verificar Windows y el
cierre forzado del proceso en la matriz acordada. Evidencia y limitaciones
en `backend-phase-1.md`, `client-phase-2.md`, `client-phase-3.md` y
`client-phase-4.md`. La fase 4 incluye envío/recuperación y conciliación por
recibos; carga de 1000 operaciones/100 cortes y dos cajas validada con simulador
central del contrato. Spikes físicos de fase 0, Windows, impresora física,
firma/distribución y piloto siguen pendientes.

Preparación de interfaz e impresora Nextep USB de 80 mm y límites de la entrega
de fase 5 en `client-phase-5.md`. Las tareas físicas no se marcan completadas
por disponer de implementación o pruebas automatizadas.
