<?php

namespace App\Http\Controllers;

use App\Models\AltaInventario;
use App\Models\EnfermedadesTipoFlor;
use App\Models\CatEnfermedades;
use App\Models\CatTipoFlor;
use App\Models\Producto;
use App\Models\SolucionEnfermedad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MainController extends Controller
{
      /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        // Escapa comodines para LIKE (_ y %) y arma el patrón
        $escaped = str_replace(['%', '_'], ['\%', '\_'], $q);
        $pattern = "%{$escaped}%";

        $solucionesByProduct = SolucionEnfermedad::query()
            ->join('productos', 'productos.id', '=', 'solucion_enfermedads.id_producto')
            ->join('enfermedades_tipo_flors', 'enfermedades_tipo_flors.id', '=', 'solucion_enfermedads.id_enfermedad_tipo_flor')
            ->join('cat_tipo_flors', 'cat_tipo_flors.id', '=', 'enfermedades_tipo_flors.id_tipo_flor')
            ->join('cat_enfermedades', 'cat_enfermedades.id', '=', 'enfermedades_tipo_flors.id_enfermedad')
            ->where('solucion_enfermedads.id_sucursal', Auth::user()->id_sucursal)
            ->when($q !== '', function ($query) use ($pattern) {
                $query->where(function ($w) use ($pattern) {
                    // Búsqueda case-insensitive + accent-insensitive
                    $w->orWhereRaw('unaccent(productos.nombre) ILIKE unaccent(?)', [$pattern])
                        ->orWhereRaw('unaccent(productos.ingrediente_activo) ILIKE unaccent(?)', [$pattern])
                        ->orWhereRaw('unaccent(cat_enfermedades.nombre) ILIKE unaccent(?)', [$pattern])
                        ->orWhereRaw('unaccent(cat_tipo_flors.nombre) ILIKE unaccent(?)', [$pattern]);
                });
            })
            ->select('solucion_enfermedads.*') // evita colisiones de columnas
            ->latest('solucion_enfermedads.created_at')
            ->paginate(10)
            ->withQueryString();

        foreach ($solucionesByProduct as $solucionByProd) {
            $enfermedadTipo = EnfermedadesTipoFlor::where('id', $solucionByProd->id_enfermedad_tipo_flor)->first();
            $tipoFlor = CatTipoFlor::where('id', $enfermedadTipo->id_tipo_flor)->first();
            $enfermedad = CatEnfermedades::where('id', $enfermedadTipo->id_enfermedad)->first();
            $producto = Producto::where('id', $solucionByProd->id_producto)->first();

            $actualStock = AltaInventario::where('id_producto', $producto->id)
                ->where('id_sucursal', Auth::user()->id_sucursal)
                ->orderBy('created_at', 'desc')
                ->first();

            $producto->cantidad = $actualStock?->cantidad_nueva ?? 0;

            $solucionByProd->tipoFlor = $tipoFlor;
            $solucionByProd->enfermedad = $enfermedad;
            $solucionByProd->producto = $producto;
        }

        return Inertia::render('Dashboard', [
            'solucionesByProduct' => $solucionesByProduct,
        ]);
    }

}
