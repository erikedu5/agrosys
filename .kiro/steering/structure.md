# Estructura del Proyecto

## Organización de Directorios

### Backend (Laravel)

```
app/
├── Actions/           # Acciones de Jetstream/Fortify
├── Console/           # Comandos Artisan
├── Dto/              # Data Transfer Objects
├── Events/           # Eventos del sistema
├── Exceptions/       # Manejo de excepciones
├── Helpers/          # Funciones auxiliares
├── Http/
│   ├── Controllers/  # Controladores
│   └── Middleware/   # Middleware personalizado
├── Models/           # Modelos Eloquent
├── Providers/        # Service Providers
├── Services/         # Lógica de negocio
└── Swagger/          # Documentación API
```

### Frontend (Vue 3 + Inertia)

```
resources/
├── css/              # Estilos CSS/Tailwind
├── js/
│   ├── Components/   # Componentes Vue reutilizables
│   ├── Layouts/      # Layouts de página
│   ├── Pages/        # Páginas/Vistas principales
│   ├── stores/       # Stores de Pinia
│   └── utils/        # Utilidades JavaScript
└── views/            # Plantillas Blade (mínimas)
```

### Configuración

```
config/               # Archivos de configuración Laravel
database/
├── factories/        # Model Factories
├── migrations/       # Migraciones de BD
└── seeders/         # Seeders
routes/              # Definición de rutas
storage/             # Archivos generados/logs
public/              # Assets públicos
```

## Convenciones de Nomenclatura

### Modelos y Base de Datos

-   **Modelos**: PascalCase singular (`Producto`, `Venta`)
-   **Tablas**: snake_case plural (`productos`, `ventas`)
-   **Campos**: snake_case (`nombre_producto`, `fecha_venta`)
-   **Relaciones**: camelCase (`ventaProductos()`, `sucursalUsuarios()`)

### Controladores

-   **Nombres**: PascalCase + "Controller" (`ProductoController`)
-   **Métodos**: camelCase siguiendo convenciones REST
    -   `index()`: Listar recursos
    -   `create()`: Mostrar formulario de creación
    -   `store()`: Guardar nuevo recurso
    -   `show()`: Mostrar recurso específico
    -   `edit()`: Mostrar formulario de edición
    -   `update()`: Actualizar recurso
    -   `destroy()`: Eliminar recurso

### Frontend (Vue)

-   **Componentes**: PascalCase (`ProductoForm.vue`, `VentaTable.vue`)
-   **Páginas**: PascalCase organizadas por módulo (`Inventario/Index.vue`)
-   **Props/Variables**: camelCase (`nombreProducto`, `fechaVenta`)
-   **Stores**: camelCase con sufijo Store (`useProductoStore`)

## Patrones de Arquitectura

### Backend

-   **Repository Pattern**: Implementado en Services para lógica compleja
-   **DTO Pattern**: Para transferencia de datos estructurados
-   **Event-Driven**: Uso de Events para acciones desacopladas
-   **Middleware**: Para autenticación, roles y validaciones

### Frontend

-   **Composition API**: Preferir sobre Options API en Vue 3
-   **Pinia Stores**: Para estado global compartido
-   **Inertia Pages**: Una página Vue por ruta Laravel
-   **Component Composition**: Componentes pequeños y reutilizables

## Rutas y Organización

### Rutas Web (routes/web.php)

-   Agrupadas por funcionalidad y middleware
-   Uso de `Route::resource()` para CRUD estándar
-   Middleware de roles: `hasRoles:vendedor-admin-superAdmin`

### Rutas API (routes/api.php)

-   Prefijo `/api/` automático
-   Autenticación por API Key (`CheckApiKey` middleware)
-   Documentadas con anotaciones Swagger

### Páginas Vue (resources/js/Pages/)

```
Pages/
├── Auth/             # Autenticación (Jetstream)
├── Dashboard/        # Dashboard principal
├── Inventario/       # Gestión de inventario
├── Venta/           # Sistema de ventas
├── Cliente/         # Gestión de clientes
├── Reporte/         # Reportes y analytics
├── Usuario/         # Administración de usuarios
└── Empresa/         # Gestión de empresas
```

## Convenciones de Código

### PHP (Laravel)

-   PSR-12 para estilo de código
-   Usar Laravel Pint para formateo automático
-   Type hints obligatorios en métodos públicos
-   Documentación PHPDoc para métodos complejos

### JavaScript/Vue

-   ESLint + Prettier para formateo
-   Composition API con `<script setup>`
-   Props tipadas con TypeScript-style comments
-   Imports organizados: librerías → componentes → utils
