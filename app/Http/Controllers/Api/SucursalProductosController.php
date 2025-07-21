<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AltaInventario;
use App\Models\Producto;
use App\Models\Sucursales;

class SucursalProductosController extends Controller
{
    public function index(Sucursales $sucursal)
    {
        $productoIds = AltaInventario::where('id_sucursal', $sucursal->id)
            ->pluck('id_producto')
            ->unique();

        $productos = Producto::whereIn('id', $productoIds)->get();

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
