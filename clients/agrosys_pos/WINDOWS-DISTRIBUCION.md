# AgroSys POS: crear y publicar el instalador Windows

Esta guía genera **`AgroSys-POS-Setup.exe`**, el instalador que descarga la
landing. Incluye el ejecutable, DLL, plugins y datos de Flutter. El ejecutable
interno `agrosys_pos.exe` por sí solo no es un instalador ni una distribución
completa.

El paquete de producción apunta a:

```text
https://agrosys.pixka.com.mx/api/v1/pos
```

## 1. Preparar una computadora Windows

Compila en Windows x64. Esta guía y su script no se han ejecutado en Windows
desde el entorno macOS del proyecto; valida el instalador en una PC antes de
publicarlo. No incluye un build nativo ARM64.

Instala:

1. Git y Flutter **3.47.6** (la versión fijada por el proyecto), con Dart incluido.
2. Visual Studio con la carga **Desktop development with C++**, MSVC y Windows SDK.
   Visual Studio Code por sí solo no proporciona el compilador de Windows.
3. Inno Setup **6.3 o posterior**. El script también usa directivas compatibles
   con Inno Setup 7. Ajusta la ruta de `ISCC.exe` si instalaste otra versión.

Fuentes: [herramientas Windows de Flutter](https://docs.flutter.dev/platform-integration/windows/setup),
[Inno Setup](https://jrsoftware.org/isinfo.php).

Obtén una copia actualizada del repositorio. Abre PowerShell y entra en
`clients/agrosys_pos`:

```powershell
cd C:\ruta\agrosys\clients\agrosys_pos
flutter --version
flutter doctor -v
flutter config --enable-windows-desktop
flutter pub get --enforce-lockfile
```

Resuelve los errores de la sección Windows de `flutter doctor` antes de continuar.

## 2. Verificar host y versión

```powershell
Get-Content config\production.json
Select-String -Path pubspec.yaml -Pattern '^version:'
```

`production.json` debe contener:

```json
{"POS_API_URL":"https://agrosys.pixka.com.mx/api/v1/pos"}
```

Usa este archivo para distribución. `config/development.json` apunta a un
servidor local y no corresponde al instalador público.

La versión actual es `1.0.0+5`. Para una nueva entrega aumenta la versión/build
en `pubspec.yaml` y usa la misma versión comercial al compilar el instalador.
Mantén el `AppId`, el nombre del ejecutable y los identificadores de la app.

## 3. Compilar y probar la aplicación

Desde `clients/agrosys_pos`:

```powershell
flutter analyze
flutter test
flutter build windows --release --dart-define-from-file=config/production.json
```

Con el build x64 del proyecto, la salida esperada es:

```text
build\windows\x64\runner\Release\
  agrosys_pos.exe
  flutter_windows.dll
  otras DLL de plugins
  data\
```

Confirma que existe y ejecuta la app:

```powershell
Test-Path build\windows\x64\runner\Release\agrosys_pos.exe
& .\build\windows\x64\runner\Release\agrosys_pos.exe
```

Si Flutter informa una carpeta de salida diferente, ajusta `[Files]` en
`packaging/windows.iss` antes de crear el instalador.

Comprueba login, selección de sucursal, activación, descarga, consulta offline,
venta de contado, crédito y reimpresión. Cierra la app antes de empaquetar.

### Dependencia de Visual C++ en las PCs destino

Las PCs que usarán la app necesitan **Microsoft Visual C++ Redistributable
x64** compatible con las herramientas de compilación. Visual Studio puede
instalarlo en tu PC de desarrollo, pero eso no demuestra que esté presente en
las PCs destino.

Instálalo desde [Microsoft](https://learn.microsoft.com/en-us/cpp/windows/latest-supported-vc-redist)
antes de probar AgroSys en una PC sin herramientas de desarrollo. Este
instalador Inno Setup **no incorpora ni instala automáticamente** el runtime;
su instalación puede requerir permisos de administrador. Si faltan
`VCRUNTIME140.dll`, `VCRUNTIME140_1.dll` o `MSVCP140.dll`, verifica este requisito.

Referencia: [distribución Windows de Flutter](https://docs.flutter.dev/platform-integration/windows/building).

## 4. Crear AgroSys-POS-Setup.exe

El script está incluido en **`packaging/windows.iss`**. Empaqueta toda la carpeta
Release y usa el icono de AgroSys. Instala para el usuario actual en:

```text
%LOCALAPPDATA%\Programs\AgroSys POS
```

Puedes abrir el script en Inno Setup y elegir **Build → Compile**, o usar
PowerShell desde `clients/agrosys_pos`:

```powershell
& "C:\Program Files (x86)\Inno Setup 6\ISCC.exe" "/DAppVersion=1.0.0" "packaging\windows.iss"
```

Si instalaste Inno Setup 7 o en otro directorio, usa la ruta real de `ISCC.exe`.

La salida será:

```text
clients/agrosys_pos/dist/AgroSys-POS-Setup.exe
```

Verifica el resultado:

```powershell
Get-Item dist\AgroSys-POS-Setup.exe
Get-FileHash dist\AgroSys-POS-Setup.exe -Algorithm SHA256
```

El nombre debe permanecer **`AgroSys-POS-Setup.exe`** porque la landing lo
busca exactamente así. La versión se guarda dentro del instalador.

## 5. Validar instalación y actualización

Prueba el instalador en otra PC o máquina virtual Windows con Visual C++
Redistributable y sin Flutter/Visual Studio:

- Instala y abre desde el menú Inicio; confirma el icono de AgroSys.
- Inicia sesión y activa una sucursal con Internet.
- Desconecta Internet, reinicia la app y comprueba consulta y venta.
- Reconecta y verifica sincronización sin duplicados.
- Si usarás Nextep USB de 80 mm, instala su driver, configura papel de 80 mm
  e imprime/reimprime seleccionando la cola del sistema.
- Para actualizar, deja una venta pendiente, cierra la app e instala encima
  con el mismo usuario Windows. Comprueba que conserva el pendiente y su UUID.

SQLite y credenciales se guardan fuera del directorio de instalación. El
script no incluye reglas de borrado de esos datos. No agregues `UninstallDelete`
sobre AppData ni cambies los identificadores para resolver un problema de
actualización. No empaquetes bases de datos reales, credenciales, `.env`,
tokens o archivos de otros usuarios.

Para distribución pública puedes firmar la app y el instalador con un
certificado de firma de código válido. El script actual no firma el EXE;
Windows puede mostrar avisos de editor desconocido/SmartScreen. La firma
requiere tu certificado y configuración de distribución.

## 6. Subir el instalador a la landing

Sube el archivo generado a la **carpeta del proyecto Laravel en el servidor**:

```text
<proyecto Laravel>/public/downloads/AgroSys-POS-Setup.exe
```

Usa SFTP o tu mecanismo de despliegue. Por ejemplo, desde PowerShell si tienes
OpenSSH y acceso SSH al servidor:

```powershell
scp .\dist\AgroSys-POS-Setup.exe usuario@servidor:/ruta/al/proyecto/public/downloads/AgroSys-POS-Setup.exe
```

Sustituye usuario, servidor y ruta por los reales. La carpeta `downloads` debe
existir y el servidor web debe poder leer el archivo. Para reemplazar una
versión publicada, sube primero con un nombre temporal y renómbralo al final,
para que nadie descargue un EXE incompleto durante la transferencia.

La URL pública será:

```text
https://agrosys.pixka.com.mx/downloads/AgroSys-POS-Setup.exe
```

En la landing, `routes/web.php` comprueba la existencia del archivo en cada
carga. Cuando está presente, se habilita **Descargar para Windows**. No hace
falta cambiar Vue para cada nueva versión del EXE.

Para el primer despliegue de esta sección también deben estar actualizados:

```text
routes/web.php
resources/js/Pages/Welcome.vue
public/build/ (completo, incluyendo manifest.json y assets)
```

Los assets deben generarse con `npm run build` desde la raíz del repositorio
y subirse completos. Si prefieres compilar en el servidor, con las dependencias
de Node y el lockfile del proyecto:

```sh
npm ci
npm run build
php artisan route:clear
```

Después recarga la landing sin caché. Si un CDN cachea la página o la descarga,
invalida las URLs correspondientes tras publicar la nueva versión.

## 7. Verificar descarga y API

Desde PowerShell:

```powershell
curl.exe -I https://agrosys.pixka.com.mx/downloads/AgroSys-POS-Setup.exe
curl.exe -H "Accept: application/json" https://agrosys.pixka.com.mx/api/v1/pos/health
```

La descarga debe responder **HTTP 200** y la API debe devolver
`{"status":"ok", ...}`. Descarga el EXE desde la landing y compara su SHA256
con el archivo que compilaste.

El backend necesita las migraciones POS aplicadas y `POS_NATIVE_ENABLED=true`
en el `.env` del servidor, seguido de `php artisan config:cache`. El instalador
no modifica el backend ni habilita la API por sí mismo.

## Problemas frecuentes

| Síntoma | Qué revisar |
| --- | --- |
| No hay compilador Windows | Visual Studio, carga C++ y `flutter doctor -v`. |
| Sólo abre en la PC de desarrollo | Runtime Visual C++ x64 y todas las DLL/plugins de Release. |
| ISCC no encuentra archivos | Ruta de salida del build y carpeta raíz del script. |
| Windows sigue en “Próximamente” | Nombre exacto del EXE, carpeta pública, permisos y recargar landing. |
| No aparece la sección Descargas | Despliegue de rutas y assets nuevos; limpiar caché de rutas y navegador. |
| La descarga devuelve HTML o 404 | Document root de Nginx debe ser `public`; verificar ruta y archivo. |
| Login dice que la API no está disponible | API nativa desplegada y habilitada; probar `/api/v1/pos/health`. |
| Actualización parece perder datos | Mismo usuario Windows, identificadores y contexto de servidor/sucursal; no borrar AppData. |

## Referencias del instalador

[Arquitecturas de Inno Setup](https://jrsoftware.org/ishelp/topic_setup_architecturesallowed.htm),
[instalación por usuario](https://jrsoftware.org/ishelp/topic_setup_privilegesrequired.htm),
[inclusión recursiva de archivos](https://jrsoftware.org/ishelp/topic_filessection.htm).
