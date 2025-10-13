# AgroSys - Sistema Agrícola

AgroSys es un sistema integral de inventario, ventas y asistencia para empresas agrícolas desarrollado con Laravel y Vue 3.

## Funcionalidades Principales

-   **Gestión de Inventario**: Control de productos agrícolas, stock y altas de inventario
-   **Sistema de Ventas**: Procesamiento de ventas con tickets térmicos (58mm/80mm)
-   **Gestión de Clientes**: Administración de base de datos de clientes
-   **Reportes**: Generación de reportes de ventas, inventario y productos por marca
-   **Multi-sucursal**: Soporte para múltiples sucursales y empresas
-   **API Pública**: Endpoints para integración externa con autenticación por API Key
-   **Asistente Agrícola**: Sistema de soluciones por producto y enfermedades

## Usuarios y Roles

-   **superAdmin**: Acceso completo al sistema
-   **admin**: Gestión de usuarios, sucursales y operaciones generales
-   **vendedor**: Ventas, clientes, reportes de ventas
-   **inventario**: Gestión de productos e inventario

## Características Técnicas

-   Autenticación con Laravel Jetstream/Fortify
-   Interfaz SPA con Inertia.js
-   Generación de PDFs para tickets y reportes
-   Integración con Google Maps
-   Documentación API con Swagger/OpenAPI
