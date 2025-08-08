<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * Rutas Web (referencia). Estas devuelven HTML/Inertia.
 */
class WebRoutes
{
    /**
     * @OA\Get(
     *   path="/",
     *   summary="Login",
     *   tags={"Web"},
     *   @OA\Response(
     *     response=200,
     *     description="OK (HTML)",
     *     @OA\MediaType(
     *       mediaType="text/html",
     *       @OA\Schema(type="string"),
     *       example="<!doctype html><html><body>Login</body></html>"
     *     )
     *   )
     * )
     */
    public function login() {}

    /**
     * @OA\Get(
     *   path="/dashboard",
     *   summary="Dashboard",
     *   tags={"Web"},
     *   security={{"SessionAuth":{}}},
     *   @OA\Parameter(name="q", in="query", description="Búsqueda de soluciones/productos", @OA\Schema(type="string")),
     *   @OA\Parameter(name="page", in="query", description="Número de página (paginación)", @OA\Schema(type="integer", minimum=1, default=1)),
     *   @OA\Parameter(name="laravel_session", in="cookie", required=false, description="Cookie de sesión de Laravel", @OA\Schema(type="string", example="eyJpdiI6I...")),
     *   @OA\Response(
     *     response=200,
     *     description="OK (HTML)",
     *     @OA\MediaType(
     *       mediaType="text/html",
     *       @OA\Schema(type="string"),
     *       example="<!doctype html><html><body>Dashboard</body></html>"
     *     )
     *   ),
     *   @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function dashboard() {}

    /**
     * @OA\Get(path="/inventario", summary="Inventario - listado", tags={"Web"}, security={{"SessionAuth":{}}},
     *   @OA\Parameter(name="q", in="query", description="Búsqueda por nombre o ingrediente activo", @OA\Schema(type="string")),
     *   @OA\Parameter(name="page", in="query", description="Número de página (paginación)", @OA\Schema(type="integer", minimum=1, default=1)),
     *   @OA\Response(
     *     response=200,
     *     description="OK (HTML)",
     *     @OA\MediaType(
     *       mediaType="text/html",
     *       @OA\Schema(type="string"),
     *       example="<!doctype html><html><body>Inventario</body></html>"
     *     )
     *   )
     * )
     * @OA\Post(path="/inventario", summary="Inventario - crear", tags={"Web"}, security={{"SessionAuth":{}}}, @OA\Response(response=302, description="Redirección"))
     * @OA\Post(path="/inventario/addInventario", summary="Inventario - alta de existencias", tags={"Web"}, security={{"SessionAuth":{}}}, @OA\Response(response=302, description="Redirección"))
     */
    public function inventario() {}

    /**
     * @OA\Get(path="/venta", summary="Ventas - listado", tags={"Web"}, security={{"SessionAuth":{}}},
     *   @OA\Parameter(name="q", in="query", description="Búsqueda de productos en sucursal actual", @OA\Schema(type="string")),
     *   @OA\Parameter(name="b", in="query", description="Búsqueda de productos en otras sucursales de la misma empresa", @OA\Schema(type="string")),
     *   @OA\Parameter(name="page", in="query", description="Número de página (paginación)", @OA\Schema(type="integer", minimum=1, default=1)),
     *   @OA\Response(
     *     response=200,
     *     description="OK (HTML)",
     *     @OA\MediaType(
     *       mediaType="text/html",
     *       @OA\Schema(type="string"),
     *       example="<!doctype html><html><body>Ventas</body></html>"
     *     )
     *   )
     * )
     * @OA\Post(path="/venta", summary="Ventas - crear", tags={"Web"}, security={{"SessionAuth":{}}}, @OA\Response(response=302, description="Redirección"))
     * @OA\Get(
     *   path="/ticket/{venta}", summary="Ventas - ticket", tags={"Web"}, security={{"SessionAuth":{}}},
     *   @OA\Parameter(name="venta", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(
     *     response=200,
     *     description="OK (PDF)",
     *     @OA\MediaType(
     *       mediaType="application/pdf",
     *       @OA\Schema(type="string", format="binary")
     *     )
     *   )
     * )
     */
    public function ventas() {}

    /**
     * @OA\Get(path="/cliente", summary="Clientes - listado", tags={"Web"}, security={{"SessionAuth":{}}},
     *   @OA\Parameter(name="page", in="query", description="Número de página (paginación)", @OA\Schema(type="integer", minimum=1, default=1)),
     *   @OA\Response(
     *     response=200, description="OK (HTML)",
     *     @OA\MediaType(mediaType="text/html", @OA\Schema(type="string"), example="<!doctype html><html><body>Clientes</body></html>")
     *   )
     * )
     */
    public function clientes() {}

    /**
     * @OA\Get(path="/empresa", summary="Empresas - listado", tags={"Web"}, security={{"SessionAuth":{}}},
     *   @OA\Parameter(name="page", in="query", description="Número de página (paginación)", @OA\Schema(type="integer", minimum=1, default=1)),
     *   @OA\Response(response=200, description="OK (HTML)", @OA\MediaType(mediaType="text/html", @OA\Schema(type="string"), example="<!doctype html><html><body>Empresas</body></html>"))
     * )
     */
    public function empresas() {}

    /**
      * @OA\Get(path="/usuario", summary="Usuarios - listado", tags={"Web"}, security={{"SessionAuth":{}}},
      *   @OA\Parameter(name="page", in="query", description="Número de página (paginación)", @OA\Schema(type="integer", minimum=1, default=1)),
      *   @OA\Response(response=200, description="OK (HTML)", @OA\MediaType(mediaType="text/html", @OA\Schema(type="string"), example="<!doctype html><html><body>Usuarios</body></html>"))
      * )
     */
    public function usuarios() {}

    /**
     * @OA\Get(path="/sucursal", summary="Sucursales - listado", tags={"Web"}, security={{"SessionAuth":{}}},
     *   @OA\Parameter(name="page", in="query", description="Número de página (paginación)", @OA\Schema(type="integer", minimum=1, default=1)),
     *   @OA\Response(response=200, description="OK (HTML)", @OA\MediaType(mediaType="text/html", @OA\Schema(type="string"), example="<!doctype html><html><body>Sucursales</body></html>"))
     * )
     */
    public function sucursales() {}

    /**
     * @OA\Get(path="/solucion", summary="Soluciones - listado", tags={"Web"}, security={{"SessionAuth":{}}}, @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", minimum=1, default=1)), @OA\Response(response=200, description="OK (HTML)", @OA\MediaType(mediaType="text/html", @OA\Schema(type="string"), example="<!doctype html><html><body>Soluciones</body></html>")))
     * @OA\Get(path="/clasificacion", summary="Clasificaciones - listado", tags={"Web"}, security={{"SessionAuth":{}}}, @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", minimum=1, default=1)), @OA\Response(response=200, description="OK (HTML)", @OA\MediaType(mediaType="text/html", @OA\Schema(type="string"), example="<!doctype html><html><body>Clasificaciones</body></html>")))
     * @OA\Get(path="/enfermedad", summary="Enfermedades - listado", tags={"Web"}, security={{"SessionAuth":{}}}, @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", minimum=1, default=1)), @OA\Response(response=200, description="OK (HTML)", @OA\MediaType(mediaType="text/html", @OA\Schema(type="string"), example="<!doctype html><html><body>Enfermedades</body></html>")))
     * @OA\Get(path="/tipoFlor", summary="Tipos de flor - listado", tags={"Web"}, security={{"SessionAuth":{}}}, @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", minimum=1, default=1)), @OA\Response(response=200, description="OK (HTML)", @OA\MediaType(mediaType="text/html", @OA\Schema(type="string"), example="<!doctype html><html><body>Tipos de flor</body></html>")))
     * @OA\Get(path="/marca", summary="Marcas - listado", tags={"Web"}, security={{"SessionAuth":{}}}, @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", minimum=1, default=1)), @OA\Response(response=200, description="OK (HTML)", @OA\MediaType(mediaType="text/html", @OA\Schema(type="string"), example="<!doctype html><html><body>Marcas</body></html>")))
     */
    public function catalogos() {}

    /**
     * @OA\Get(path="/reporte", summary="Reportes - panel", tags={"Web"}, security={{"SessionAuth":{}}},
     *   @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", minimum=1, default=1)),
     *   @OA\Response(response=200, description="OK (HTML)")
     * )
     * @OA\Get(path="/reporte/venta", summary="Reportes - ventas (PDF)", tags={"Web"}, security={{"SessionAuth":{}}},
     *   @OA\Parameter(name="fechaInicio", in="query", required=true, description="Fecha inicio (YYYY-MM-DD)", @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="fechaFin", in="query", required=true, description="Fecha fin (YYYY-MM-DD)", @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="id_sucursal", in="query", required=false, description="Solo Admin matriz: sucursal a consultar", @OA\Schema(type="integer")),
     *   @OA\Response(
     *     response=200, description="OK (PDF)",
     *     @OA\MediaType(mediaType="application/pdf", @OA\Schema(type="string", format="binary"))
     *   )
     * )
     * @OA\Get(path="/reporte/inventario", summary="Reportes - inventario (PDF)", tags={"Web"}, security={{"SessionAuth":{}}},
     *   @OA\Parameter(name="id_clasificacion", in="query", required=false, description="Filtrar por clasificación", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="id_marca", in="query", required=false, description="Filtrar por marca", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="id_sucursal", in="query", required=false, description="Solo Admin matriz: sucursal a consultar", @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK (PDF)", @OA\MediaType(mediaType="application/pdf", @OA\Schema(type="string", format="binary")))
     * )
     * @OA\Get(path="/reporte/ventaPorProductoMarca", summary="Reportes - ventas por producto/marca (PDF)", tags={"Web"}, security={{"SessionAuth":{}}},
     *   @OA\Parameter(name="fechaInicio", in="query", required=true, description="Fecha inicio (YYYY-MM-DD)", @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="fechaFin", in="query", required=true, description="Fecha fin (YYYY-MM-DD)", @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="id_producto", in="query", required=false, description="Filtrar por producto (alternativo a id_marca)", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="id_marca", in="query", required=false, description="Filtrar por marca (alternativo a id_producto)", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="id_sucursal", in="query", required=false, description="Solo Admin matriz: sucursal a consultar", @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK (PDF)", @OA\MediaType(mediaType="application/pdf", @OA\Schema(type="string", format="binary"))),
     *   description="Requiere al menos uno: id_producto o id_marca."
     * )
     */
    public function reportes() {}

    /**
     * @OA\Get(path="/compra", summary="Compras - listado", tags={"Web"}, security={{"SessionAuth":{}}}, @OA\Response(response=200, description="OK (HTML)"))
     */
    public function compras() {}

    /**
     * @OA\Get(path="/facturas/index", summary="Facturas - índice", tags={"Web"}, security={{"SessionAuth":{}}}, @OA\Response(response=200, description="OK (HTML)"))
     * @OA\Put(path="/facturas/update", summary="Facturas - actualizar", tags={"Web"}, security={{"SessionAuth":{}}}, @OA\Response(response=302, description="Redirección"))
     */
    public function facturas() {}

    /**
     * @OA\Get(path="/pedidos/index", summary="Pedidos - listado", tags={"Web"}, security={{"SessionAuth":{}}}, @OA\Response(response=200, description="OK (HTML)"))
     * @OA\Put(path="/pedidos/{pedido}/completar", summary="Pedidos - completar", tags={"Web"}, security={{"SessionAuth":{}}}, @OA\Parameter(name="pedido", in="path", required=true, @OA\Schema(type="integer")), @OA\Response(response=302, description="Redirección"))
     */
    public function pedidos() {}
}
