<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        
        Log::info(Auth::user());

        if (Auth::user()->tipo === 'adminEmpresa') {
            $query->where('id', Auth::user()->id_empresa);
        }

        if ($request->boolean('deleted')) {
            $query->onlyTrashed();
        }

        $empresas = $query->latest()->paginate(10);

        return Inertia::render('Empresa/Empresa', [
            'empresas' => $empresas,
            'all' => Auth::user()->tipo === 'adminEmpresa',
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
        $validated = $request->validate([
            'nombre' => 'required',
            'direccion' => 'required',
            'mostrar_campos_precio' => 'nullable|boolean',
        ],[
            'nombre.required' => 'Agregar un nombre de empresa.',
            'direccion.required' => 'Agregar una dirección de la empresa.',
        ]);

        $empresa = new Empresa();
        $empresa->nombre = $validated['nombre'];
        $empresa->direccion = $validated['direccion'];
        $empresa->telefono = $request->telefono;
        $empresa->email = $request->email;
        $empresa->rfc = $request->rfc;
        $empresa->aviso = $request->aviso;
        $empresa->mostrar_campos_precio = $request->boolean('mostrar_campos_precio', true);

        if (Auth::user()->tipo === 'superAdmin') {
            $empresa->numero_sucursales = $request->numero_sucursales ?? 1;
        } else {
            $empresa->numero_sucursales = 1;
        }

        $empresa->save();

        return redirect()->route('empresa.index');
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
            'all' => Auth::user()->tipo === 'adminEmpresa',
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required',
            'direccion' => 'required',
            'mostrar_campos_precio' => 'nullable|boolean',
        ],[
            'nombre.required' => 'Agregar un nombre de empresa.',
            'direccion.required' => 'Agregar una dirección de la empresa.',
        ]);

        $empresa = Empresa::find($request->id);
        $empresa->nombre = $validated['nombre'];
        $empresa->direccion = $validated['direccion'];
        $empresa->telefono = $request->telefono;
        $empresa->email= $request->email;
        $empresa->rfc= $request->rfc;
        $empresa->aviso = $request->aviso;
        $empresa->mostrar_campos_precio = $request->boolean('mostrar_campos_precio', true);
        if (Auth::user()->tipo === 'superAdmin') {
            $empresa->numero_sucursales = $request->numero_sucursales;
        }
        $empresa->save();

        return redirect()->route('empresa.index');
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
