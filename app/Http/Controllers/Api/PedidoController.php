<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class PedidoController extends Controller
{
    /**
     * @OA\Post(
     *   path="/api/pedidos",
     *   summary="Crear pedidos por lote",
     *   description="Crea uno o más pedidos internos para una sucursal.",
     *   tags={"Pedidos"},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(ref="#/components/schemas/Pedido"),
     *       example={
     *         "sucursal_id": 1,
     *         "productos": {{"producto_id": 10, "cantidad": 3}, {"producto_id": 12, "cantidad": 1}},
     *         "nombre_solicitante": "Juan Perez",
     *         "numero_solicitante": "555-123-4567"
     *       }
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Creado",
     *     @OA\JsonContent(
     *       type="array",
     *       @OA\Items(ref="#/components/schemas/Pedido"),
     *       example={{
     *         "id": 1001,
     *         "id_sucursal": 1,
     *         "id_producto": 10,
     *         "cantidad": 3,
     *         "nombre_solicitante": "Juan Perez",
     *         "numero_solicitante": "555-123-4567",
     *         "completado": false
     *       },{
     *         "id": 1002,
     *         "id_sucursal": 1,
     *         "id_producto": 12,
     *         "cantidad": 1,
     *         "nombre_solicitante": "Juan Perez",
     *         "numero_solicitante": "555-123-4567",
     *         "completado": false
     *       }}
     *     )
     *   ),
     *   @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'sucursal_id' => 'required|exists:sucursales,id',
            'productos' => 'required|array|min:1',
            'nombre_solicitante' => 'required|string',
            'numero_solicitante' => 'required|string',
        ]);

        $pedidos = [];
        foreach ($data['productos'] as $item) {
            $pedidos[] = Pedido::create([
                'id_sucursal' => $data['sucursal_id'],
                'id_producto' => $item['producto_id'],
                'cantidad' => $item['cantidad'],
                'nombre_solicitante' => $data['nombre_solicitante'],
                'numero_solicitante' => $data['numero_solicitante'],
                'completado' => false,
            ]);
        }

        return response()->json($pedidos, 201);
    }
}
