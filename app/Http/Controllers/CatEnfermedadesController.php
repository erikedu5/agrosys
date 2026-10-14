<?php

namespace App\Http\Controllers;

use App\Models\CatEnfermedades;
use Inertia\Inertia;
use Illuminate\Http\Request;

class CatEnfermedadesController extends Controller
{
     /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Inertia::render('Catalogos/Enfermedad/Enfermedad', [
            'enfermedades' => CatEnfermedades::where('nombre', 'LIKE', "%$request->q%")
            ->latest()
            ->paginate(10)
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Catalogos/Enfermedad/CreateEnfermedad');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'descripcion' => 'required|',
        ], [
            'nombre.required' => 'Agregar un nombre de enfermedad.',
            'descripcion.required' => 'Agregar un descripción a la enferemedad.'
        ]);

        $enfermedad = CatEnfermedades::create($request->all());
        return redirect()->route('enfermedad.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CatEnfermedades $enfermedad)
    {
        return Inertia::render('Catalogos/Enfermedad/CreateEnfermedad', compact('enfermedad'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'descripcion' => 'required',
        ], [
            'nombre.required' => 'Agregar un nombre de enfermedad.',
            'descripcion.required' => 'Agregar un descripción a la enferemedad.'
        ]);

        $catEnfermedades = CatEnfermedades::find($request->id);
        $catEnfermedades->nombre = $request->nombre;
        $catEnfermedades->descripcion = $request->descripcion;
        $catEnfermedades->save();
        return redirect()->route('enfermedad.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CatEnfermedades $enfermedad)
    {
        $enfermedad->delete();
        return redirect()->route('enfermedad.index');
    }
}
