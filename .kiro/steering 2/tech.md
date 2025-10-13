# Stack Tecnológico

## Backend

-   **Framework**: Laravel 11 (PHP 8.2+)
-   **Autenticación**: Laravel Jetstream + Fortify + Sanctum
-   **Base de Datos**: MySQL
-   **ORM**: Eloquent

## Frontend

-   **Framework**: Vue 3 con Composition API
-   **SPA**: Inertia.js para comunicación Laravel-Vue
-   **CSS**: Tailwind CSS
-   **Iconos**: Material Design Icons (@mdi)
-   **Estado**: Pinia para gestión de estado
-   **Build Tool**: Vite

## Librerías y Paquetes Principales

### Backend (Composer)

-   `inertiajs/inertia-laravel`: Integración Inertia.js
-   `laravel/jetstream`: Autenticación y equipos
-   `barryvdh/laravel-dompdf`: Generación de PDFs
-   `darkaonline/l5-swagger`: Documentación API
-   `maatwebsite/excel`: Exportación Excel
-   `alexpechkarev/google-maps`: Integración Google Maps
-   `tightenco/ziggy`: Rutas Laravel en JavaScript

### Frontend (NPM)

-   `@inertiajs/vue3`: Cliente Inertia para Vue 3
-   `@tailwindcss/forms`: Estilos para formularios
-   `@vuepic/vue-datepicker`: Selector de fechas
-   `laravel-vue-i18n`: Internacionalización
-   `moment`: Manipulación de fechas
-   `pinia`: Gestión de estado

## Comandos Comunes

### Instalación Inicial

```bash
# Instalar dependencias PHP
composer install

# Instalar dependencias Node.js
npm clean-install

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Migrar base de datos
php artisan migrate
```

### Desarrollo

```bash
# Iniciar servidor Laravel
php artisan serve

# Iniciar Vite (desarrollo frontend)
npm run dev

# Build para producción
npm run build
```

### Mantenimiento

```bash
# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Generar documentación Swagger
php artisan l5-swagger:generate

# Ejecutar tests
php artisan test
```

### Base de Datos

```bash
# Crear migración
php artisan make:migration create_table_name

# Crear modelo con migración
php artisan make:model ModelName -m

# Ejecutar seeders
php artisan db:seed
```

## Configuración de Entorno

### Variables Clave en .env

-   `DB_*`: Configuración de base de datos MySQL
-   `API_KEY`: Clave para API pública
-   `APP_URL`: URL base de la aplicación
-   `VITE_*`: Variables para el frontend
