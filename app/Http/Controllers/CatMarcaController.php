<?php

namespace App\Http\Controllers;

use App\Models\CatMarca;
use Inertia\Inertia;
use Illuminate\Http\Request;

class CatMarcaController extends Controller
{

    public function index(Request $request)
    {
        return Inertia::render('Catalogos/Marca/Marca', [
            'marcas' => CatMarca::where('nombre', 'LIKE', "%$request->q%")
            ->latest()
            ->paginate(10)
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogos/Marca/CreateMarca');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required'
        ], [
            'nombre' => 'Agregar un nombre de marca.'
        ]);

        CatMarca::create($request->all());
        return redirect()->route('marca.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CatMarca $marca)
    {
        return Inertia::render('Catalogos/Marca/CreateMarca', compact('marca'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
        ],[
            'nombre' => 'Agregar un nombre de marca.'
        ]);

        $catMarca = CatMarca::find($request->id);
        $catMarca->nombre = $request->nombre;
        $catMarca->save();
        return redirect()->route('marca.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CatMarca $marca)
    {
        $marca->delete();
        return redirect()->route('marca.index');
    }
}
