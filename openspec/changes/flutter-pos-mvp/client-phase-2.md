# Evidencia de fase 2 — cliente de consulta

Fecha: 2026-10-07. Tareas 2.1–2.6 implementadas. 2.7 permanece pendiente
por Windows y verificación de cierre forzado del proceso en la matriz acordada.
El cambio completo no está archivado: venta contado/crédito comienza en fase 3.

## Entrega

Proyecto `clients/agrosys_pos` con Flutter 3.47.6 / Dart 3.13.5 y lockfile.
Navegación responsive; login personal/2FA; selección de sucursal; identidad
estable en almacenamiento seguro; activación y concesión Ed25519; API Dio.
Consulta por nombre sin acentos, ID o barcode; clientes, precios, descuentos,
stock/adeudo estimados y fecha de última descarga.

Drift v2 con SQLite en segundo plano, una base por hash de contexto. Staging
durable paginado y commit atómico de catálogo/snapshots/recibos/cursor.
Reanudación tras corte, bootstrap ante cursor vencido, bajas y efectos locales
conservados. No se retiran deltas por ACK sin recibo confirmado. Migración
v1→v2 sin reset. Fallos de escritura conservan catálogo y staging.

Vencimiento/revocación conocida bloquean lectura; conexión fallida conserva
consulta con concesión vigente. Cierre de sesión bloquea y conserva la base.
Reloj retrocedido exige renovación explícita verificada; renovación restablece
la referencia. SQLite sin cifrar: política de cifrado/PIN pendiente antes del piloto.

Backend añade `POST /auth/context` para intercambiar token tras elegir sucursal
y recuperar el ID estable; revoca el token anterior en transacción. OpenAPI
actual contiene 14 operaciones. Servidor nativo sigue deshabilitado por defecto.

## Validación ejecutada

| Verificación | Resultado |
|---|---|
| `flutter analyze` | Sin incidencias |
| `flutter test` | 20 pruebas aprobadas |
| Búsqueda local con 10 000 productos | p95 10.20 ms en SQLite en memoria en este host; no medición de dispositivos físicos |
| Prueba nativa macOS | Aprobada; plugin Keychain y SQLite real en segundo plano |
| Prueba nativa Android | Aprobada en emulador API 36.1; almacenamiento seguro y SQLite reales |
| Prueba nativa iOS | Aprobada en iPhone 16e, simulador iOS 26.2 |
| Regresiones PHP dirigidas | 46 pruebas, 295 assertions; subconjunto Native POS: 29 pruebas, 211 assertions |
| OpenAPI | Validador OpenAPI 3.1 aprobado, 14 operaciones |
| OpenSpec | `validate flutter-pos-mvp --strict --no-interactive` aprobado |

La prueba nativa usa API de fixtures HTTP local y firma Ed25519 real. Activa,
descarga, detiene el servidor, cierra/reabre controlador y SQLite, restaura
credenciales desde el plugin nativo y consulta productos/clientes sin servidor.
Limpia exclusivamente sus claves y archivos QA. No reemplaza una prueba
end-to-end contra Laravel real ni el cierre forzado de todo el proceso.

macOS informó que no pudo llevar la ventana al primer plano, pero el runner
ejecutó y aprobó la prueba. iOS tenía claves de red anidadas en la configuración
de escenas; se corrigió Info.plist y se repitió la prueba satisfactoriamente.

Pruebas cubren aislamiento, firma/claims/rotación de clave, no persistencia de
contraseñas, 2FA antes de credenciales, token/contexto en transporte, bloqueo
persistido, migración, paginación/reopen, cursor vencido, rollback por recibo
inválido, fallos de escritura y reloj corregido.

## Builds locales

- macOS release: `clients/agrosys_pos/build/macos/Build/Products/Release/agrosys_pos.app` (49.3 MB).
- Android debug: `clients/agrosys_pos/build/app/outputs/flutter-apk/app-debug.apk`.
- iOS simulador debug: `clients/agrosys_pos/build/ios/iphonesimulator/Runner.app`.

Artefactos bajo `build/` ignorado por Git, compilados con entrada normal
`lib/main.dart`. Son builds internos sin publicación ni firma de tiendas.

## Pendientes y límites

- Ejecutar prueba y build Windows en host con Visual Studio C++; este Mac no
  compila ni ejecuta Windows. Workflow preparado, sin publicar/ejecutar remotamente.
- Verificar terminación completa del proceso, reapertura offline y QA físico en
  los cuatro sistemas; cerrar matriz/toolchains/spikes de fase 0.
- Cerrar cifrado/desbloqueo local y recuperación antes de distribuir fuera del piloto.
- Firma/notarización, tiendas, impresora y piloto pertenecen a fase 5.
- Carrito, venta contado/crédito, tickets y Outbox todavía no implementados.

Instrucciones reproducibles, entornos y compilación en
[README del cliente](../../../clients/agrosys_pos/README.md). SDK y cachés
utilizados son temporales bajo `/tmp`; instalar la versión fijada si se eliminan.
No se desplegó, habilitó ni migró producción.
