<?php

namespace App\Http\Controllers;

use App\Models\Clientes;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $clientes = Clientes::where('nombre', 'LIKE', "%$request->q%")
        ->latest()
        ->paginate(10);
        
        return Inertia::render('Cliente/Cliente', [
            'clientes' => $clientes,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Cliente/CreateCliente');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $request->validate([
            'nombre' => 'required',
            'porcentaje_descuento' => 'required'
        ]);

        Clientes::create($request->all());
        return redirect()->route('cliente.index');
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Clientes $cliente)
    {
        return Inertia::render('Cliente/CreateCliente', compact('cliente'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'porcentaje_descuento' => 'required',
        ]);
        
        $cliente = Clientes::find($request->id);
        $cliente->nombre = $request->nombre;
        $cliente->porcentaje_descuento = $request->porcentaje_descuento;
        $cliente->save();
        return redirect()->route('cliente.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Clientes $cliente)
    {
        $cliente->delete();
        return redirect()->route('cliente.index');
    }
}
