<?php

namespace App\Http\Controllers;

use App\Models\EnfermedadesTipoFlor;
use App\Models\CatEnfermedades;
use App\Models\CatTipoFlor;
use App\Models\Producto;
use App\Models\SolucionEnfermedad;
use App\Models\AltaInventario;
use App\Services\SucursalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Mockery\Undefined;

class SolucionEnfermedadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sucursal = SucursalService::getSucursalActiva();

        $solucion = SolucionEnfermedad::where('id_enfermedad_tipo_flor', intval($request->q))
            ->where('id_producto', $request->id_producto)
            ->where('id_sucursal', $sucursal)
            ->first();

        $producto = Producto::where('id', $request->id_producto)->first();

        // Obtener el stock actual del producto
        $actualStock = AltaInventario::where('id_producto', $request->id_producto)
            ->where('id_sucursal', $sucursal)
            ->orderBy('created_at', 'desc')->first();

        $producto->cantidad = $actualStock ? $actualStock->cantidad_nueva : 0;

        $solucionesByProduct = SolucionEnfermedad::where('id_producto', $request->id_producto)
            ->where('id_sucursal', $sucursal)
            ->latest()
            ->paginate(10);

        foreach ($solucionesByProduct as $solucionByProd) {
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
            $enfermedadTipoFlor->nombre = $enfermedad->nombre . ' en ' . $tipoFlor->nombre;
            array_push($enfermedadesFlorArray, $enfermedadTipoFlor);
        }

        return Inertia::render('Inventario/AgregarSolucion', [
            'producto' => $producto,
            'enfermedadesFlor' => $enfermedadesFlorArray,
            'solucion' => $solucion,
            'solucionesByProduct' => $solucionesByProduct,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $sucursal = SucursalService::getSucursalActiva();

        // Normalizar si llega como objeto desde el select de Vue
        $enfermedadTipoFlorId = is_array($request->id_enfermedad_tipo_flor)
            ? ($request->id_enfermedad_tipo_flor['id'] ?? null)
            : $request->id_enfermedad_tipo_flor;
        $request->merge(['id_enfermedad_tipo_flor' => $enfermedadTipoFlorId]);

        $request->validate([
            'dosis_tambo_ml' => 'required|numeric|gt:0',
            'dosis_bomba_ml' => 'required|numeric|gt:0',
            'id_producto' => 'required|numeric|not_in:0',
            'id_enfermedad_tipo_flor' => 'required|numeric|not_in:0',
        ]);

        $dosisTambo = $request->input('dosis_tambo_ml');

        $dosisBomba = $request->input('dosis_bomba_ml');

        $solu = SolucionEnfermedad::where('id_producto', $request->id_producto)
            ->where('id_enfermedad_tipo_flor', $request->id_enfermedad_tipo_flor)
            ->where('id_sucursal', $sucursal)
            ->first();

        if ($solu === null) {
            $solu = SolucionEnfermedad::create([
                'dosis_tambo_ml' => $dosisTambo,
                'dosis_bomba_ml' => $dosisBomba,
                'id_producto' => $request->id_producto,
                'id_enfermedad_tipo_flor' => $request->id_enfermedad_tipo_flor,
                'id_sucursal' => $sucursal,
                'condiciones' => $request->condiciones,
            ]);
        } else {
            $solu->dosis_tambo_ml = $dosisTambo;
            $solu->dosis_bomba_ml = $dosisBomba;
            $solu->condiciones = $request->condiciones;
            $solu->save();
        }

        $producto = Producto::where('id', $request->id_producto)->first();

        return redirect()->route('solucion.index', [
            'id_producto' => $producto->id,
            'solucion' => $solu,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $enfermedadTipoFlorId = is_array($request->id_enfermedad_tipo_flor)
            ? ($request->id_enfermedad_tipo_flor['id'] ?? null)
            : $request->id_enfermedad_tipo_flor;
        $request->merge(['id_enfermedad_tipo_flor' => $enfermedadTipoFlorId]);

        $request->validate([
            'dosis_tambo_ml' => 'required|numeric|gt:0',
            'dosis_bomba_ml' => 'required|numeric|gt:0',
            'id_enfermedad_tipo_flor' => 'required|numeric|not_in:0',
            'id_producto' => 'required|numeric|not_in:0',
        ]);

        $dosisTambo = $request->input('dosis_tambo_ml');

        $dosisBomba = $request->input('dosis_bomba_ml');

        $solucion = SolucionEnfermedad::find($request->id);
        $solucion->id_producto = $request->id_producto;
        $solucion->id_enfermedad_tipo_flor = $request->id_enfermedad_tipo_flor;
        $solucion->dosis_bomba_ml = $dosisBomba;
        $solucion->dosis_tambo_ml = $dosisTambo;
        $solucion->condiciones = $request->condiciones;
        $solucion->save();

        $producto = Producto::where('id', $request->id_producto)->first();

        return redirect()->route('solucion.index', [
            'id_producto' => $producto->id,
        ]);
    }
}



