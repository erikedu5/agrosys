# Agrosys POS — cliente Flutter

Entrega de fases 2–4: autenticación/2FA, consulta local, ventas de contado/crédito,
ticket, historial y sincronización recuperable con el servidor. Ventas e IDs se
guardan primero en SQLite/Outbox; su envío conserva el contenido original.

## Ejecutar

Flutter **3.47.6**, Dart **3.13.5**; dependencias fijadas en `pubspec.lock`.
Instala el SDK oficial o usa la versión indicada en `.flutter-version`.

```sh
cd clients/agrosys_pos
flutter pub get --enforce-lockfile
flutter run -d macos --dart-define-from-file=config/development.json
# En emulador Android, 10.0.2.2 apunta al host.
flutter run -d emulator-5554 --dart-define-from-file=config/android_emulator.json
```

El cliente apunta por defecto a `https://agrosys.pixka.com.mx/api/v1/pos`;
`config/development.json` apunta al servidor local `http://localhost:8000/api/v1/pos`.
El inicio de sesión no muestra un campo de servidor. Para otros entornos,
configura `POS_API_URL` al compilar, incluyendo `/api/v1/pos`.
Desarrollo admite HTTP; release exige
HTTPS. `config/production.example.json` es sólo una plantilla sin credenciales.

El servidor debe tener las migraciones de fase 1 y `POS_NATIVE_ENABLED=true`
en el entorno seleccionado. No habilitar ni migrar producción simplemente
por compilar el cliente. Login conserva únicamente token personal, concesión
y dispositivo en almacenamiento seguro; nunca guarda la contraseña.

## Uso

1. Conectar, indicar servidor y acceder con correo/contraseña; completar 2FA
   o recovery code si corresponde.
2. Elegir sucursal y activar el dispositivo. La autorización manual requiere
   al administrador; el mismo ID puede reintentarse sin crear otro.
3. Esperar descarga completa. Un corte conserva páginas en staging y el
   catálogo anterior; se puede continuar al recuperar conexión.
4. Consultar productos por nombre/ID/barcode y clientes por nombre/ID. Todo
   se lee de SQLite, con fecha de actualización y estimados visibles.
5. Cerrar y abrir con el servidor desconectado: el contexto autorizado se
   restaura desde almacenamiento seguro y se consulta localmente. Al vencer
   la concesión se bloquea lectura hasta revalidar online. Revocación o bloqueo
   conocido quedan persistidos, incluso si después desaparece la conexión.

Ctrl/Cmd+F enfoca búsqueda. NavigationRail en escritorio y NavigationBar en
móvil. No hay consultas al servidor por cada búsqueda ni dependencias externas
para fuentes/imágenes durante uso offline.

## Venta local de contado y crédito

1. Abrir **Venta**, seleccionar un cliente existente activo y agregar cantidades.
   Se aplica su descuento actual del catálogo; cantidades de hasta dos decimales.
2. Elegir contado o crédito según permisos. Contado aplica el total; crédito
   permite inicial de cero al total y muestra saldo de esa venta.
3. Introducir efectivo recibido. Debe cubrir el pago/inicial; el excedente es cambio.
4. Si la cantidad supera stock estimado, revisar y reconocer la advertencia.
5. Finalizar. Sólo tras commit aparece comprobante, folio local y estado pendiente.
6. Abrir **Historial** para consultar/copiar/imprimir/reimprimir el ticket guardado.

El carrito se conserva al navegar; un borrador no terminado no sobrevive al
cierre del proceso. Si falla el cierre, se puede reintentar el mismo UUID o
revisar si ya quedó guardado antes de modificar el carrito. Una venta terminada
no se borra ni se cancela desde este cliente. Historial pagina de 100 en 100.

Impresión PDF mediante el servicio nativo del sistema. Incluye fuente Lato y
licencia OFL en assets para funcionar offline. Cancelación/error de impresión
no deshace ni repite la venta; resultado enviado requiere comprobar salida
física. Impresoras térmicas/Bluetooth/USB aún requieren elegir y probar adaptador
en fase 5. Fuente: [printing](https://pub.dev/packages/printing).

## Sincronizar y revisar pendientes

El cliente intenta sincronizar al iniciar, completar una venta, volver al primer
plano y verificar conexión cada 30 segundos mientras está en primer plano. No
requiere ejecución continua en segundo plano. La venta local sigue disponible
durante el envío; no se renueva automáticamente la concesión offline.

**Historial** permite filtrar pendientes, ventas en revisión y confirmadas, y
**Reintentar pendientes** vuelve a intentar los errores temporales. Un conflicto
conserva ticket, cobro, IDs y motivo: requiere revisión central; el botón no cambia
precios/clientes ni genera otra venta. HTTP 401 requiere iniciar sesión; 402 exige
suscripción activa; 403 requiere revisar la autorización. Revalidar el mismo
contexto permite recuperar sus pendientes sin eliminarlos.

El worker tiene exclusión durable por contexto y lease recuperable de 120 segundos,
lotes de hasta 50, orden por secuencia y backoff exponencial con jitter. Antes de
reenviar una operación intentada consulta su estado central. Un ACK confirma la
venta pero conserva deltas: sólo el recibo del snapshot completo demuestra que
stock/adeudo ya incluyen el movimiento. **Cuenta → descarga completa** permite
reiniciar staging inválido conservando ventas y pendientes.

## Persistencia y recuperación

Una base por hash de servidor/usuario/empresa/sucursal/dispositivo, bajo
Application Support/contexts. El esquema Drift v4 incluye catálogo, snapshots,
estado de sincronización, páginas, ventas, pagos, efectos locales y Outbox.
Migraciones v1/v2/v3→v4 conservan identidad, ventas y payloads pendientes; migraciones no soportadas
detienen apertura. No se borra una base si falla almacenamiento/migración.

Las páginas se guardan de forma durable. Sólo el conjunto completo aplica
productos/clientes, bajas, stock/cuentas, recibos y cursor en una transacción.
Cursor vencido fuerza bootstrap sin borrar efectos ni pendientes. Un ACK no
retira deltas; sólo un recibo confirmado del snapshot completo los refleja.

Cerrar sesión bloquea primero acceso local y conserva la base. Los IDs de
cada contexto permanecen en almacenamiento seguro; reautenticación recupera
el mismo dispositivo. `auth/context` intercambia el token de descubrimiento
por uno ligado a ese ID y revoca el anterior.

La firma Ed25519 verifica bytes exactos y pertenencia; se fija la clave pública
por servidor y un cambio exige revisión. Retroceso significativo de reloj
bloquea acceso hasta corregirlo y revalidar. Concesión de 7 días por defecto.

Los tokens usan Android Keystore, Keychain Apple y el almacenamiento seguro
nativo de Windows. macOS utiliza Keychain local con servicio propio y sin
Keychain Sharing (no requiere un perfil para compartir credenciales). SQLite
**no está cifrado**: política de cifrado/PIN local y recuperación siguen
pendientes antes de distribuir fuera del piloto. Android tiene backups
automáticos deshabilitados para evitar restaurar claves/identidades inválidas.

## Verificar

```sh
flutter analyze
flutter test
flutter test integration_test/offline_reopen_test.dart -d macos
flutter test integration_test/sync_recovery_test.dart -d macos
flutter test integration_test/offline_reopen_test.dart -d emulator-5554
flutter test integration_test/sync_recovery_test.dart -d emulator-5554
flutter test integration_test/offline_reopen_test.dart -d <ios-simulator-id>
flutter test integration_test/sync_recovery_test.dart -d <ios-simulator-id>
# En un host Windows con Visual Studio Desktop development with C++:
flutter test integration_test/offline_reopen_test.dart -d windows
flutter test integration_test/sync_recovery_test.dart -d windows
```

La prueba nativa usa secretos y datos QA aislados, API HTTP local dentro de la
app, firma real Ed25519 y SQLite real. Detiene el servidor, cierra/reabre el
controlador/base, restaura credenciales y consulta productos/clientes. No
simula el plugin de almacenamiento seguro. El test limpia sólo sus propios
datos QA. El ciclo de cierre forzado del proceso y QA físico también debe
verificarse antes del piloto.

```sh
flutter build macos --release
flutter build apk --debug
flutter build ios --simulator --debug --no-codesign
# Requiere Windows:
flutter build windows --release
```

iOS usa Swift Package Manager y requiere Xcode; dispositivos físicos requieren
firma/provisioning propios. Android release aún usa la configuración de firma
de desarrollo del scaffold: no es un artefacto para tiendas. macOS release
local todavía no está notarizado. Firma y distribución pertenecen a fase 5.

Validación local: 46 pruebas Flutter, análisis sin incidencias y prueba nativa
de consulta aprobada en macOS, Android (emulador API 36.1) e iOS (simulador 26.2).
Las pruebas de fase 3 también comprueban venta offline y reapertura; evidencia en OpenSpec. Windows
aún requiere ejecución en un host compatible. Evidencia detallada en
[OpenSpec fase 2](../../openspec/changes/flutter-pos-mvp/client-phase-2.md) y
[fase 3](../../openspec/changes/flutter-pos-mvp/client-phase-3.md) y
[fase 4](../../openspec/changes/flutter-pos-mvp/client-phase-4.md).

CI en `.github/workflows/flutter-pos.yml`: calidad, pruebas nativas de escritorio,
Android y build de simulador iOS. La existencia del workflow no demuestra que
haya corrido: no se publicó ni ejecutó remotamente durante esta implementación.

Preparación de fase 5: interfaz alineada con la web y ticket térmico de 80 mm
mediante el diálogo de impresión del sistema. Para Nextep USB en escritorio,
instalar el driver y configurar la cola con papel de 80 mm. La impresión física
y la conexión USB directa desde móvil siguen pendientes; ver
[evidencia y checklist de fase 5](../../openspec/changes/flutter-pos-mvp/client-phase-5.md).

Fuentes de configuración:

[Flutter](https://docs.flutter.dev/install/manual),
[Drift](https://drift.simonbinder.eu/setup/),
[almacenamiento seguro Apple](https://pub.dev/packages/flutter_secure_storage_darwin).
