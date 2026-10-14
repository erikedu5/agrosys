<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AltaInventario;
use App\Models\Producto;
use App\Models\Sucursales;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class SucursalProductosController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/sucursales/{sucursal}/productos",
     *   summary="Productos con stock por sucursal",
     *   description="Lista productos disponibles en una sucursal con su cantidad actual.",
     *   tags={"Sucursales"},
     *   security={{"ApiKeyAuth":{}}},
     *   @OA\Parameter(name="sucursal", in="path", required=true, description="ID de la sucursal", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="busqueda", in="query", required=false, description="Filtro por nombre de producto", @OA\Schema(type="string")),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="array",
     *       @OA\Items(ref="#/components/schemas/Producto"),
     *       example={{
     *         "id": 10,
     *         "nombre": "Herbicida X",
     *         "precio_unitario": 120.5,
     *         "ieps": 8.0,
     *         "precio_ieps": 9.64,
     *         "tamano": "1L",
     *         "ingrediente_activo": "Glifosato",
     *         "cantidad": 15
     *       }}
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     * )
     */
    public function index(Request $request, Sucursales $sucursal)
    {
        $productoIds = AltaInventario::where('id_sucursal', $sucursal->id)
            ->pluck('id_producto')
            ->unique();

        $query = Producto::whereIn('id', $productoIds)
            ->where('id_empresa', $sucursal->id_empresa);

        if ($request->filled('busqueda')) {
            $search = $request->query('busqueda');
            $driver = $query->getConnection()->getDriverName();

            if ($driver === 'pgsql') {
                $query->where('nombre', 'ILIKE', "%{$search}%");
            } else {
                $query->whereRaw('LOWER(nombre) LIKE ?', ['%' . strtolower($search) . '%']);
            }
        }

        $productos = $query->get();

        foreach ($productos as $producto) {
            $alta = AltaInventario::where('id_producto', $producto->id)
                ->where('id_sucursal', $sucursal->id)
                ->orderByDesc('id')
                ->first();
            $producto->cantidad = $alta ? $alta->cantidad_nueva : 0;
        }

        return response()->json($productos);
    }
}
