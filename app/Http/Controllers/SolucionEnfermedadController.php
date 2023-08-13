<?php

namespace App\Http\Controllers;

use App\Models\EnfermedadesTipoFlor;
use App\Models\CatEnfermedades;
use App\Models\CatTipoFlor;
use App\Models\Producto;
use App\Models\SolucionEnfermedad;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SolucionEnfermedadController extends Controller
{
         /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $solucion = SolucionEnfermedad::where('id_enfermedad_tipo_flor', intval($request->q))
                                      ->where('id_producto', $request->id_producto)
                                      ->first();    
                                    
        $producto = Producto::where('id', $request->id_producto)->first();

        $solucionesByProduct = SolucionEnfermedad::where('id_producto', $request->id_producto)
        ->latest()
        ->paginate(10);

        foreach($solucionesByProduct as $solucionByProd) {
            $enfermedadTipo = EnfermedadesTipoFlor::where('id', $solucionByProd->id_enfermedad_tipo_flor)->first();
            $tipoFlor = CatTipoFlor::where('id', $enfermedadTipo->id_tipo_flor)->first();
            $enfermedad = CatEnfermedades::where('id', $enfermedadTipo->id_enfermedad)->first();
            $solucionByProd->tipoFlor = $tipoFlor;
            $solucionByProd->enfermedad = $enfermedad;
        }

        $enfermedadesTipoFlores = EnfermedadesTipoFlor::get();
        $enfermedadesFlorArray = [];

        foreach ($enfermedadesTipoFlores as $enfermedadTipoFlor) {
            $tipoFlor = CatTipoFlor::where('id', $enfermedadTipoFlor->id_tipo_flor)->first();
            $enfermedad = CatEnfermedades::where('id', $enfermedadTipoFlor->id_enfermedad)->first();
            $enfermedadTipoFlor->tipoFlor = $tipoFlor;
            $enfermedadTipoFlor->enfermedad = $enfermedad;
            $enfermedadTipoFlor->nombre = $enfermedad->nombre.' en '.$tipoFlor->nombre;
            array_push($enfermedadesFlorArray, $enfermedadTipoFlor);
        }

        return Inertia::render('Inventario/AgregarSolucion', [
            'producto' => $producto,
            'enfermedadesFlor' => $enfermedadesFlorArray,
            'solucion' => $solucion,
            'solucionesByProduct' =>$solucionesByProduct,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $request->validate([
            'dosis_tambo_ml' => 'required|numeric|not_in:0',
            'dosis_bomba_ml' => 'required|numeric|not_in:0',
            'id_producto' => 'required|numeric|not_in:0',
        ]);

        SolucionEnfermedad::create([
            'dosis_tambo_ml' => $request->dosis_tambo_ml,
            'dosis_bomba_ml' => $request->dosis_bomba_ml,
            'id_producto' => $request->id_producto,
            'id_enfermedad_tipo_flor' => $request->id_enfermedad_tipo_flor['id'],
        ]);

        $solucion = SolucionEnfermedad::where('id_enfermedad_tipo_flor', $request->id_enfermedad_tipo_flor['id'])
                                      ->where('id_producto', $request->id_producto)
                                      ->first();    

        $producto = Producto::where('id', $request->id_producto)->first();

        return redirect()->route('solucion.index', [
            'id_producto' => $producto->id,
            'solucion' => $solucion,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {  

        $request->validate([
            'dosis_tambo_ml' => 'required|numeric|not_in:0',
            'dosis_bomba_ml' => 'required|numeric|not_in:0',
            'id_enfermedad_tipo_flor' => 'required|numeric|not_in:0',
            'id_producto' => 'required|numeric|not_in:0',
        ]);

        $solucion = SolucionEnfermedad::find($request->id);
        $solucion->id_producto = $request->id_producto;
        $solucion->id_enfermedad_tipo_flor = $request->id_enfermedad_tipo_flor;
        $solucion->dosis_bomba_ml = $request->dosis_bomba_ml;
        $solucion->dosis_tambo_ml = $request->dosis_tambo_ml;
        $solucion->save();

        $producto = Producto::where('id', $request->id_producto)->first();

        return redirect()->route('solucion.index', [
            'id_producto' => $producto->id,
        ]);
    }
    
}
