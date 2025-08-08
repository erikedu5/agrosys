<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sucursales;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;

class SucursalesApiController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/sucursales",
     *   summary="Listar sucursales",
     *   description="Retorna listado de sucursales con su empresa relacionada.",
     *   tags={"Sucursales"},
     *   security={{"ApiKeyAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="array",
     *       @OA\Items(ref="#/components/schemas/Sucursal"),
     *       example={{
     *         "id": 2,
     *         "nombre": "Sucursal Centro",
     *         "direccion": "Av. Siempre Viva 742",
     *         "telefono": "5559876543",
     *         "email": "centro@agro.com",
     *         "es_matriz": false,
     *         "id_empresa": 1,
     *         "empresa": {
     *           "id": 1,
     *           "nombre": "Agro del Valle",
     *           "rfc": "XAXX010101000"
     *         }
     *       }}
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index()
    {
        $sucursales = Sucursales::with('empresa')->get();
        Log::debug(json_encode($sucursales));
        return response()->json($sucursales);
    }
}
