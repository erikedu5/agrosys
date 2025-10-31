<?php

namespace App\Http\Controllers;

use App\Models\AltaInventario;
use App\Models\Empresa;
use App\Models\EmpresaProducto;
use App\Models\Producto;
use App\Models\Sucursales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmpresaProductoController extends Controller
{
    // método de búsqueda amplia eliminado; ahora usamos sugerencia exacta (nombre + tamaño)

    public function store(Empresa $empresa, Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'id_sucursal' => 'nullable|exists:sucursales,id',
        ]);

        $productoId = (int) $request->integer('producto_id');
        $pivot = EmpresaProducto::withTrashed()
            ->firstOrNew([
                'id_empresa' => $empresa->id,
                'id_producto' => $productoId,
            ]);

        if ($pivot->exists && $pivot->trashed()) {
            $pivot->restore();
        } elseif (!$pivot->exists) {
            $pivot->save();
        }

        // Limpiar historial de inventario para que el stock inicie en 0 en TODAS las sucursales de la empresa
        $sucursalIds = Sucursales::where('id_empresa', $empresa->id)->pluck('id');
        if ($sucursalIds->isNotEmpty()) {
            AltaInventario::where('id_producto', $productoId)
                ->whereIn('id_sucursal', $sucursalIds)
                ->delete();
        }

        return response()->json(['ok' => true]);
    }

    public function suggestExact(Empresa $empresa, Request $request)
    {
        $request->validate([
            'nombre' => 'required|string',
            'tamano' => 'required|string',
        ]);

        $nombre = $request->input('nombre');
        $tamano = $request->input('tamano');

        $sub = EmpresaProducto::select('id_producto')
            ->where('id_empresa', $empresa->id)
            ->whereNull('deleted_at');

        $items = Producto::whereNotIn('id', $sub)
            ->where('nombre', $nombre)
            ->where('tamano', $tamano)
            ->orderBy('nombre')
            ->limit(10)
            ->get(['id', 'nombre', 'tamano']);

        return response()->json(['data' => $items]);
    }

    public function destroy(Empresa $empresa, Producto $producto)
    {
        $pivot = EmpresaProducto::where('id_empresa', $empresa->id)
            ->where('id_producto', $producto->id)
            ->first();

        if (!$pivot) {
            return response()->json(['message' => 'Relación no encontrada'], 404);
        }

        // Eliminar historial de inventario en TODAS las sucursales de la empresa
        $sucursalIds = Sucursales::where('id_empresa', $empresa->id)->pluck('id');
        if ($sucursalIds->isNotEmpty()) {
            AltaInventario::where('id_producto', $producto->id)
                ->whereIn('id_sucursal', $sucursalIds)
                ->delete();
        }

        $pivot->delete(); // Soft delete

        return response()->json(['ok' => true]);
    }
}
