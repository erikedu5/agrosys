<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

## Pasos para iniciar proyecto

Crear el archivo .env con algo de ejemplo como el siguiente:

```
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=agrosys
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=database
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"

```

Instalar las dependencias de npm ejecutando el comando en terminal

```
npm clean-install
```

instalar las dependencias de composer ejecutando el comando en terminal

```
composer install
```

Crear estructura de base de datos

```
php artisan migrate
```

Como dev se inicia el proyecto vue en modo dev

```
npm run dev
```


iniciar el servicio de laravel 

```
php artisan serve
```

# agrosys
Sistema de inventario/ventas/asistente de agricola con laravel y vue3

## Rutas clave (Web)
- `GET /` (Login): pantalla de autenticación (Jetstream/Inertia).
- `GET /dashboard` (auth): dashboard de soluciones por producto.
- `resource /inventario` (roles: inventario, admin, superAdmin): CRUD de productos/inventario.
- `POST /inventario/addInventario` (roles: inventario, admin, superAdmin): alta de inventario.
- `resource /venta` (roles: vendedor, admin, superAdmin): gestión de ventas.
- `GET /ticket/{venta}` (roles: vendedor, admin, superAdmin): ticket de venta.
  - Ahora formateado para impresión térmica 80mm.
  - Parámetro opcional: `?size=58` para imprimir en 58mm (si no se envía, usa el ancho configurado en la sucursal; por defecto 80mm).
- `resource /cliente` (roles: vendedor, admin, superAdmin): clientes.
- `resource /empresa` (rol: superAdmin): empresas.
- `resource /usuario` (roles: admin, superAdmin): usuarios.
- `resource /sucursal` (roles: admin, superAdmin): sucursales.
- `GET /reporte` (roles: inventario, vendedor, admin, superAdmin): panel de reportes.
- `GET /reporte/venta` (roles: vendedor, admin, superAdmin): reporte de ventas.
- `GET /reporte/inventario` (roles: inventario, admin, superAdmin): reporte de inventario.
- `GET /reporte/ventaPorProductoMarca` (roles: vendedor, admin, superAdmin): ventas por producto/marca.
- Versiones térmicas (80mm):
  - `GET /reporte/venta-ticket` (mismos parámetros que `/reporte/venta`).
  - `GET /reporte/inventario-ticket` (mismos parámetros que `/reporte/inventario`).
  - `GET /reporte/ventaPorProductoMarca-ticket` (mismos parámetros que `/reporte/ventaPorProductoMarca`).
  - En todas las rutas térmicas admite `?size=58` para reducir el ancho a 58mm (si no se envía, usa el ancho de la sucursal; por defecto 80mm).

### Preferencia por sucursal
- Campo nuevo en `sucursales`: `ticket_width_mm` (80 por defecto). Valores recomendados: 80 o 58.
- Para cambiarlo rápidamente:
  - SQL: `UPDATE sucursales SET ticket_width_mm = 58 WHERE id = <id_sucursal>;`
  - UI: (opcional) se puede agregar un selector en la pantalla de edición de sucursal.
- `resource /compra` (roles: vendedor, admin, superAdmin): compras.
- `GET /facturas/index` y `PUT /facturas/update` (roles: vendedor, admin, superAdmin): facturas.
- `GET /pedidos/index` y `PUT /pedidos/{pedido}/completar` (auth): gestión de pedidos internos.

## API Pública
- `GET /api/sucursales` (X-API-KEY requerido): lista sucursales con empresa.
- `GET /api/sucursales/{sucursal}/productos?busqueda=...` (X-API-KEY): productos con stock en la sucursal. Búsqueda case-insensitive.
- `POST /api/pedidos` (sin API key): crea pedidos internos por lote.

### Autenticación por API Key
- Header: `X-API-KEY: <tu_api_key>` o query `?api_key=<tu_api_key>`.
- Configurar en `.env`: `API_KEY=tu_valor_seguro`.

## Roles y seguridad
- Autenticación: Jetstream/Fortify + Sanctum (`auth:sanctum`).
- Middleware de roles: `hasRoles` usa `User.tipo` y acepta múltiples roles separados por guiones, por ejemplo: `hasRoles:vendedor-admin-superAdmin`.

## Desarrollo rápido
1) `composer install` y `npm clean-install`
2) Copiar `.env.example` a `.env`, configurar DB y `API_KEY`
3) `php artisan key:generate` y `php artisan migrate`
4) `npm run dev` y `php artisan serve`

## Notas
- El stock se calcula desde la última alta en `alta_inventarios` por producto/sucursal.
- Paquetes incluidos: DOMPDF (PDF), Excel, L5-Swagger, S3, Google Maps.

## Documentación Swagger (OpenAPI)
- Generar docs (primera vez):
  - `php artisan vendor:publish --provider="L5Swagger\\L5SwaggerServiceProvider"`
  - `php artisan l5-swagger:generate`
- Regenerar docs tras cambios en anotaciones: `php artisan l5-swagger:generate`
- Ver documentación: visita `http://localhost:8000/api/documentation` (o `APP_URL` + `/api/documentation`).
- Seguridad para endpoints con API Key:
  - Enviar header `X-API-KEY: <tu_api_key>`.
  - Define `API_KEY` en `.env` para que `CheckApiKey` lo valide.
 - Incluye ejemplos y esquemas de respuesta para: Sucursales, Productos por Sucursal y creación de Pedidos.
 - Las rutas Web también están documentadas (como referencia) bajo el tag "Web" y responden HTML/Inertia.

### Ejemplos rápidos (cURL)
- Listar sucursales
  - `curl -H "X-API-KEY: $API_KEY" $APP_URL/api/sucursales`
- Productos por sucursal
  - `curl -H "X-API-KEY: $API_KEY" "$APP_URL/api/sucursales/1/productos?busqueda=herbicida"`
- Crear pedidos
  - `curl -X POST -H "Content-Type: application/json" -d '{"sucursal_id":1, "productos":[{"producto_id":10,"cantidad":3}], "nombre_solicitante":"Juan Perez","numero_solicitante":"555-123-4567"}' $APP_URL/api/pedidos`

### Autenticación por sesión en Swagger (rutas Web)
- Inicia sesión en la app desde tu navegador y copia el valor de la cookie `laravel_session` (DevTools → Application → Cookies).
- En Swagger UI, presiona "Authorize" y pega la cookie en el campo de `SessionAuth`.
- Alternativamente, agrega manualmente el parámetro de cookie al probar endpoints (Swagger muestra el parámetro `laravel_session`).
