<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AltaInventario;
use App\Models\Producto;
use App\Models\Sucursales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SucursalProductosController extends Controller
{
    public function index(Request $request, Sucursales $sucursal)
    {
        $productoIds = AltaInventario::where('id_sucursal', $sucursal->id)
            ->pluck('id_producto')
            ->unique();

        $query = Producto::whereIn('id', $productoIds);

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
