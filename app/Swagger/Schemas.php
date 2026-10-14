<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * Esquemas OpenAPI (agrupados para facilitar el autoload).
 *
 * @OA\Schema(
 *   schema="Empresa",
 *   type="object",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="nombre", type="string", example="Agro del Valle"),
 *   @OA\Property(property="direccion", type="string", example="Calle 123, CDMX"),
 *   @OA\Property(property="telefono", type="string", example="5551234567"),
 *   @OA\Property(property="email", type="string", example="contacto@agro.com"),
 *   @OA\Property(property="rfc", type="string", example="XAXX010101000"),
 *   @OA\Property(property="aviso", type="string", example="Aviso de privacidad..."),
 *   @OA\Property(property="numero_sucursales", type="integer", example=3)
 * )
 *
 * @OA\Schema(
 *   schema="Sucursal",
 *   type="object",
 *   @OA\Property(property="id", type="integer", example=2),
 *   @OA\Property(property="nombre", type="string", example="Sucursal Centro"),
 *   @OA\Property(property="direccion", type="string", example="Av. Siempre Viva 742"),
 *   @OA\Property(property="telefono", type="string", example="5559876543"),
 *   @OA\Property(property="email", type="string", example="centro@agro.com"),
 *   @OA\Property(property="es_matriz", type="boolean", example=false),
 *   @OA\Property(property="id_empresa", type="integer", example=1),
 *   @OA\Property(property="empresa", ref="#/components/schemas/Empresa")
 * )
 *
 * @OA\Schema(
 *   schema="Producto",
 *   type="object",
 *   @OA\Property(property="id", type="integer", example=10),
 *   @OA\Property(property="nombre", type="string", example="Herbicida X"),
 *   @OA\Property(property="id_clasificacion", type="integer", example=1),
 *   @OA\Property(property="id_marca", type="integer", example=4),
 *   @OA\Property(property="precio_unitario", type="number", format="float", example=120.50),
 *   @OA\Property(property="ieps", type="number", format="float", example=8.0),
 *   @OA\Property(property="precio_ieps", type="number", format="float", example=9.64),
 *   @OA\Property(property="tamano", type="string", example="1L"),
 *   @OA\Property(property="ingrediente_activo", type="string", example="Glifosato"),
 *   @OA\Property(property="cantidad", type="number", example=15, description="Stock actual en la sucursal (derivado de inventario)")
 * )
 *
 * @OA\Schema(
 *   schema="Pedido",
 *   type="object",
 *   @OA\Property(property="id", type="integer", example=1001),
 *   @OA\Property(property="id_sucursal", type="integer", example=2),
 *   @OA\Property(property="id_producto", type="integer", example=10),
 *   @OA\Property(property="cantidad", type="number", example=3),
 *   @OA\Property(property="nombre_solicitante", type="string", example="Juan Pérez"),
 *   @OA\Property(property="numero_solicitante", type="string", example="555-123-4567"),
 *   @OA\Property(property="completado", type="boolean", example=false)
 * )
 *
 * @OA\Schema(
 *   schema="ErrorResponse",
 *   type="object",
 *   @OA\Property(property="message", type="string", example="Unauthorized")
 * )
 */
class Schemas {}
