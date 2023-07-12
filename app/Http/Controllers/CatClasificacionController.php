<?php

namespace App\Http\Controllers;

use App\Models\CatClasificacion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CatClasificacionController extends Controller
{
    
    public function index(Request $request)
    {
        return Inertia::render('Catalogos/Clasificacion/Clasificacion', [
            'clasificaciones' => CatClasificacion::where('nombre', 'LIKE', "%$request->q%")
            ->latest()
            ->paginate(10)
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogos/Clasificacion/CreateClasificacion');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'criterio' => 'required',
        ]);

        CatClasificacion::create($request->all());
        return redirect()->route('clasificacion.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CatClasificacion $clasificacion)
    {
        return Inertia::render('Catalogos/Clasificacion/CreateClasificacion', compact('clasificacion'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {  
        $request->validate([
            'nombre' => 'required',
            'criterio' => 'required',
        ]);
        
        $catClasificacion = CatClasificacion::find($request->id);
        $catClasificacion->nombre = $request->nombre;
        $catClasificacion->criterio = $request->criterio;
        $catClasificacion->save();
        return redirect()->route('clasificacion.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CatClasificacion $clasificacion)
    {
        $clasificacion->delete();
        return redirect()->route('clasificacion.index');
    }
}
