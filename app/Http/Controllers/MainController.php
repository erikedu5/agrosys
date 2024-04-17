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
        $solucionesByProduct = SolucionEnfermedad::where(function ($query) use ($request) {
            return $query->orwhere('productos.nombre', 'LIKE', "%$request->q%")
            ->orWhere('productos.ingrediente_activo', 'LIKE', "%$request->q%")
            ->orWhere('cat_enfermedades.nombre', 'LIKE', "%$request->q%")
            ->orWhere('cat_tipo_flors.nombre', 'LIKE', "%$request->q%");
        })->where('solucion_enfermedads.id_sucursal', Auth::user()->id_sucursal)
        ->join('productos', 'productos.id', 'solucion_enfermedads.id_producto')
        ->join('enfermedades_tipo_flors', 'enfermedades_tipo_flors.id', 'solucion_enfermedads.id_enfermedad_tipo_flor')
        ->join('cat_tipo_flors', 'cat_tipo_flors.id', 'enfermedades_tipo_flors.id_tipo_flor')
        ->join('cat_enfermedades', 'cat_enfermedades.id', 'enfermedades_tipo_flors.id_enfermedad')
        ->latest('solucion_enfermedads.created_at')
        ->paginate(10);

        foreach($solucionesByProduct as $solucionByProd) {
            $enfermedadTipo = EnfermedadesTipoFlor::where('id', $solucionByProd->id_enfermedad_tipo_flor)->first();
            $tipoFlor = CatTipoFlor::where('id', $enfermedadTipo->id_tipo_flor)->first();
            $enfermedad = CatEnfermedades::where('id', $enfermedadTipo->id_enfermedad)->first();
            $producto = Producto::where('id', $solucionByProd->id_producto)->first();

            $actualStock = AltaInventario::where('id_producto', $producto->id)
                ->where('id_sucursal', Auth::user()->id_sucursal)
                ->orderBy('created_at', 'desc')->first();

            if ($actualStock != null) {
                $producto->cantidad = $actualStock->cantidad_nueva;
            } else {
                $producto->cantidad = 0;
            }

            $solucionByProd->tipoFlor = $tipoFlor;
            $solucionByProd->enfermedad = $enfermedad;
            $solucionByProd->producto = $producto;
        }

        return Inertia::render('Dashboard', [
            'solucionesByProduct' => $solucionesByProduct
        ]);

    }

}
