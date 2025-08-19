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
        $query = Empresa::query()
            ->where('nombre', 'LIKE', "%$request->q%");

        if ($request->boolean('deleted')) {
            $query->onlyTrashed();
        }

        $empresas = $query->latest()->paginate(10);

        return Inertia::render('Empresa/Empresa', [
            'empresas' => $empresas,
            'showDeleted' => $request->boolean('deleted'),
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
        ],[
            'nombre.required' => 'Agregar un nombre de empresa.',
            'direccion.required' => 'Agregar una dirección de la empresa.',
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
        ],[
            'nombre.required' => 'Agregar un nombre de empresa.',
            'direccion.required' => 'Agregar una dirección de la empresa.',
        ]);

        $empresa = Empresa::find($request->id);
        $empresa->nombre = $request->nombre;
        $empresa->direccion = $request->direccion;
        $empresa->telefono = $request->telefono;
        $empresa->email= $request->email;
        $empresa->rfc= $request->rfc;
        $empresa->aviso = $request->aviso;
        $empresa->numero_sucursales = $request->numero_sucursales;
        $empresa->save();

        $empresas = Empresa::get();
        return redirect()->route('empresa.index', [
            'empresas' => $empresas
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Empresa $empresa)
    {
        $empresa->delete(); // Soft delete
        return redirect()->route('empresa.index');
    }

    public function restore($id)
    {
        $empresa = Empresa::onlyTrashed()->findOrFail($id);
        $empresa->restore();
        return redirect()->route('empresa.index');
    }
}
