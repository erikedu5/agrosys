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
        $escaped = str_replace(['%', '_'], ['\%', '\_'], $q);
        $pattern = "%{$escaped}%";

        $collation = 'utf8mb4_0900_ai_ci'; 

        $solucionesByProduct = SolucionEnfermedad::query()
            ->join('productos', 'productos.id', '=', 'solucion_enfermedads.id_producto')
            ->join('enfermedades_tipo_flors', 'enfermedades_tipo_flors.id', '=', 'solucion_enfermedads.id_enfermedad_tipo_flor')
            ->join('cat_tipo_flors', 'cat_tipo_flors.id', '=', 'enfermedades_tipo_flors.id_tipo_flor')
            ->join('cat_enfermedades', 'cat_enfermedades.id', '=', 'enfermedades_tipo_flors.id_enfermedad')
            ->where('solucion_enfermedads.id_sucursal', Auth::user()->id_sucursal)
            ->when($q !== '', function ($query) use ($pattern, $collation) {
                $query->where(function ($w) use ($pattern, $collation) {
                    $w->orWhereRaw("productos.nombre COLLATE {$collation} LIKE ?", [$pattern])
                        ->orWhereRaw("productos.ingrediente_activo COLLATE {$collation} LIKE ?", [$pattern])
                        ->orWhereRaw("cat_enfermedades.nombre COLLATE {$collation} LIKE ?", [$pattern])
                        ->orWhereRaw("cat_tipo_flors.nombre COLLATE {$collation} LIKE ?", [$pattern]);
                });
            })
            ->select('solucion_enfermedads.*')
            ->latest('solucion_enfermedads.created_at')
            ->paginate(10)
            ->withQueryString();

        foreach ($solucionesByProduct as $solucionByProd) {
            $enfermedadTipo = EnfermedadesTipoFlor::find($solucionByProd->id_enfermedad_tipo_flor);
            $tipoFlor = CatTipoFlor::find($enfermedadTipo->id_tipo_flor);
            $enfermedad = CatEnfermedades::find($enfermedadTipo->id_enfermedad);
            $producto = Producto::find($solucionByProd->id_producto);

            $actualStock = AltaInventario::where('id_producto', $producto->id)
                ->where('id_sucursal', Auth::user()->id_sucursal)
                ->latest('created_at')
                ->first();

            $producto->cantidad = $actualStock->cantidad_nueva ?? 0;

            $solucionByProd->tipoFlor = $tipoFlor;
            $solucionByProd->enfermedad = $enfermedad;
            $solucionByProd->producto = $producto;
        }

        return Inertia::render('Dashboard', [
            'solucionesByProduct' => $solucionesByProduct,
        ]);
    }
}
