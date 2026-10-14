# Evidencia de fase 3 — venta local de contado y crédito

Fecha: 2026-10-07. Tareas 3.1–3.6 implementadas; cambio OpenSpec sigue abierto.
Las ventas se guardan localmente con Outbox; el envío es fase 4. No usar esta
entrega como piloto de sincronización/conciliación central.

## Entrega y decisiones

- Nueva venta con cliente activo existente, descuento de catálogo, cantidades
  en centésimas y cálculo entero half-up idéntico a PHP/JS. 200 partidas máximo.
- Contado aplica total; recibido adicional es cambio. Crédito inicial 0..total,
  saldo por venta y estado pagada al cubrir total, sin pagar créditos previos.
- Permisos `sale.create`/`sale.credit` verificados con concesión vigente, contexto,
  bloqueo durable y reloj. Stock estimado insuficiente requiere reconocimiento
  explícito; el backend conserva su política central y puede generar conflicto.
- Cierre único transaccional: venta/items/pago/efectos/Outbox/secuencia/folio.
  UUID de cierre estable; huella impide cambiar un cierre ya guardado.
- Historial paginado y ticket con valores históricos, folios y estado. No hay
  borrar/cancelar ventas terminadas; SQLite impide su eliminación y mutar
  identidad/payload de Outbox. Carrito conservado al navegar dentro de la app.
- PDF offline con fuente Lato incluida (licencia OFL); impresión/reimpresión
  mediante servicio del sistema. Intentos registrados por separado. Un error o
  cancelación conserva venta/cola; enviado no garantiza salida física.

Drift v3 añade ventas, pagos, pendientes, secuencia e intentos de impresión.
Migraciones v1/v2→v3 conservan catálogo, propietario, cursores, efectos y
payloads previos. Bases no se resetean en recuperación.

## Pruebas ejecutadas

| Verificación | Evidencia |
|---|---|
| Flutter | 46 pruebas aprobadas en la suite completa final, incluidas 14 de persistencia de ventas |
| `flutter analyze` | Sin incidencias |
| Drift generado | Regeneración reproducible: SHA-256 sin cambios |
| OpenSpec | Validación estricta aprobada |
| UI | Contado y crédito a 360×740 y 1200×800; carrito se conserva al navegar; ticket y estados visibles sin overflow |
| Atomicidad | Doble cierre concurrente y reenvío tras reopen generan una sola venta/operación y un descuento de stock |
| Almacenamiento | Fallo tardío por trigger SQLite y SQLITE_FULL real por max_page_count; rollback completo y reintento del mismo UUID/secuencia |
| Crédito | Inicial 0, 30 y 100 sobre total 100; saldo/adeudo correctos tras reopen; inicial inválido, permiso, expiración y bloqueo rechazados |
| Tickets | Fallo, cancelación y reimpresión no repiten venta; precios/nombres históricos permanecen; PDF con 200 nombres largos genera páginas sin excepción |
| Migración | v1/v2→v3 conserva checkpoint/efectos y payload pendiente ajeno sin reset |
| Contrato decimal | Mismos seis fixtures en Dart/PHP/JS; PHP: 1 prueba/12 assertions; JS: 8 pruebas aprobadas |
| macOS nativo | Aprobado: Keychain real, SQLite en isolate, contado/crédito con servidor detenido, reapertura y cola/estimados conservados |
| Android nativo | Aprobado en emulador ARM64 API 36.1; misma prueba de ventas offline |
| iOS nativo | Aprobado en iPhone 16e / simulador iOS 26.2; misma prueba de ventas offline |

La prueba nativa usa API HTTP de fixtures con firma Ed25519 real, descarga
catálogo y detiene el servidor. Reabre controlador/base, finaliza contado y
crédito offline y vuelve a reabrir: dos ventas/operaciones, stock 6 desde 10,
adeudo estimado 105 desde 40 (crédito de 95 con inicial 30). No simula plugins
de almacenamiento. No equivale a terminación forzada de todo el proceso ni
prueba contra Laravel real/dispositivos físicos. macOS reportó fallo al llevar
la ventana al primer plano, pero ejecutó y aprobó la prueba nativa.

SQLITE_FULL puede hacer rollback automático y Drift envolver la excepción
por un segundo rollback; el caso comprueba tanto la causa real como ausencia
de filas parciales y recuperación. La cuota limita sólo un archivo QA y no
llena el disco del equipo.

## Builds locales

Artefactos generados con entrada normal `lib/main.dart`, bajo `build/` ignorado:

- macOS release: `build/macos/Build/Products/Release/agrosys_pos.app` (54.2 MB).
- Android debug: `build/app/outputs/flutter-apk/app-debug.apk`.
- iOS simulador debug sin firma: `build/ios/iphonesimulator/Runner.app`.

No son paquetes de tiendas/notarizados. SDK y cachés temporales bajo `/tmp`;
instalar la versión fijada al limpiar ese directorio.

## Pendientes y límites

- Worker/push/ACK/conflictos y conciliación central de ventas: fase 4.
- Windows continúa pendiente de ejecutar/build en un host Windows; workflow
  preparado pero no publicado ni ejecutado remotamente.
- Firma/notarización, impresora física/térmica y piloto: fase 5. Impresión
  fallida/cancelada probada mediante adaptador inyectado, sin imprimir en papel.
- QA físico y cierre forzado del proceso, cifrado/desbloqueo/recuperación de
  SQLite siguen pendientes. El borrador no terminado vive en memoria; la venta
  terminada sí persiste. No crear clientes ni cobrar créditos anteriores offline.

Instrucciones en [README del cliente](../../../clients/agrosys_pos/README.md).
Dependencias de impresión: [printing](https://pub.dev/packages/printing),
[pdf](https://pub.dev/packages/pdf); fuente y OFL desde
[Google Fonts/Lato](https://github.com/google/fonts/tree/main/ofl/lato).
No se desplegó, habilitó ni migró producción.
