<?php

namespace App\Http\Controllers;

use App\Models\EnfermedadesTipoFlor;
use App\Models\CatEnfermedades;
use App\Models\CatTipoFlor;
use App\Models\Producto;
use App\Models\SolucionEnfermedad;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MainController extends Controller
{
      /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $solucionesByProduct = SolucionEnfermedad::where('productos.nombre', 'LIKE', "%$request->q%")
        ->orWhere('cat_enfermedades.nombre', 'LIKE', "%$request->q%")
        ->orWhere('cat_tipo_flors.nombre', 'LIKE', "%$request->q%")
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
            $solucionByProd->tipoFlor = $tipoFlor;
            $solucionByProd->enfermedad = $enfermedad;
            $solucionByProd->producto = $producto;
        }

        return Inertia::render('Dashboard', [
            'solucionesByProduct' => $solucionesByProduct
        ]);

    }

}
