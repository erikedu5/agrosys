<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmpresaController extends Controller
{

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $empresa = Empresa::first();
        return Inertia::render('Empresa/Empresa',
        [
            'empresa' => $empresa,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'direccion' => 'required',
        ]);
        
        $empresa = Empresa::create($request->all());
        return redirect()->route('empresa.create', [
            'empresa' => $empresa
        ]);
    }

    public function update(Request $request)
    {  
        $request->validate([
            'nombre' => 'required',
            'direccion' => 'required',
        ]);
        
        $empresa = Empresa::find($request->id);
        $empresa->nombre = $request->nombre;
        $empresa->direccion = $request->direccion;
        $empresa->telefono = $request->telefono;
        $empresa->email= $request->email;
        $empresa->rfc= $request->rfc;
        $empresa->aviso = $request->aviso;
        $empresa->save();
        return redirect()->route('empresa.create');
    }
}
