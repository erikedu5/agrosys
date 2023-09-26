<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmpresaController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $empresas = Empresa::where('nombre', 'LIKE', "%$request->q%")
        ->latest()
        ->paginate(10);

        return Inertia::render('Empresa/Empresa', [
            'empresas' => $empresas,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Empresa/CrearEmpresa');
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

        $empresas = Empresa::create($request->all());
        return redirect()->route('empresa.index', [
            'empresas' => $empresas
        ]);
    }

     /**
     * Show the form for edit a resource.
     */
    public function edit(Empresa $empresa)
    {
        $empresa = Empresa::where('id', $empresa->id)->first();
        return Inertia::render('Empresa/CrearEmpresa',
        [
            'empresa' => $empresa,
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

        $empresas = Empresa::get();
        return redirect()->route('empresa.index', [
            'empresas' => $empresas
        ]);
    }
}
