<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *   version="1.0.0",
 *   title="Agrosys API",
 *   description="Documentación de la API para sucursales, productos y pedidos."
 * )
 *
 * @OA\Server(
 *   url="/",
 *   description="Servidor base (usa APP_URL en tu entorno)"
 * )
 *
 * @OA\Tag(
 *   name="Sucursales",
 *   description="Operaciones para sucursales y sus productos"
 * )
 * @OA\Tag(
 *   name="Pedidos",
 *   description="Gestión de pedidos internos"
 * )
 * @OA\Tag(
 *   name="Web",
 *   description="Rutas web que devuelven HTML/Inertia"
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="ApiKeyAuth",
 *   type="apiKey",
 *   in="header",
 *   name="X-API-KEY",
 *   description="Clave para acceder a endpoints protegidos por api.key"
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="SessionAuth",
 *   type="apiKey",
 *   in="cookie",
 *   name="laravel_session",
 *   description="Sesión de Laravel para rutas web protegidas"
 * )
 */
class OpenApi {}
