# Plan del MVP Flutter de AgroSys

Plan original: 2026-10-06. Actualización: 2026-10-07; fase 1 backend
implementada y verificada; fase 2 consulta implementada con validación nativa
en macOS/Android/iOS. Fase 3 de ventas locales implementada; envío de operaciones, Windows y decisiones físicas pendientes.
Evidencia en [backend-phase-1.md](backend-phase-1.md) y
[client-phase-2.md](client-phase-2.md) y [client-phase-3.md](client-phase-3.md).

Decisiones confirmadas por el usuario: Flutter, ventas y consulta, OpenSpec, ventas de contado y crédito.

- [Propuesta](proposal.md)
- [Diseño y fases](design.md)
- [Tareas](tasks.md)
- [Specs por capacidad](specs/)
- [Contratos API propuestos](contracts.md)
- [Modelo local](data-model.md)
- [Matriz de aceptación](acceptance.md)
- [Investigación y brechas](research.md)

La nueva petición permite un cliente Flutter separado del cliente web/PWA descrito en los documentos anteriores. Se conserva Laravel como servidor y la PWA existente. Este cambio no se archiva ni se considera implementado por haber completado los artefactos.

## Decisiones pendientes

- Modelo/conexión de impresora y plataformas donde se exige impresión física.
- Política de stock negativo offline; propuesta: advertencia y política central explícita.
- Vigencia offline configurable: siete días por defecto; consulta y nuevas ventas bloqueadas al vencer.
- Necesidad de límites de crédito; propuesta: conservar comportamiento actual sin introducir límite global nuevo.
- Equipos de QA Windows, Android e iOS; firmas y canal de distribución.
- Cifrado de la base local y desbloqueo local según política de dispositivos.

Cobros posteriores a la venta y creación de clientes quedan fuera del MVP. El plazo orientativo con crédito es 10–14 semanas para un desarrollador experimentado, condicionado a los puntos pendientes y al spike.
