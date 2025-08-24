<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Sucursales;
use App\Services\SucursalService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class SucursalController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $empresa = Empresa::find(1);
        $query = Sucursales::where('nombre', 'LIKE', "%$request->q%");
        if (Auth::user()->tipo != 'superAdmin') {
            $sucursalUser = Sucursales::where('id',  SucursalService::getSucursalActiva())->first();
            $query = $query->where('id_empresa', $sucursalUser->id_empresa);
            $empresa = Empresa::find($sucursalUser->id_empresa);
        }

        if ($request->boolean('deleted')) {
            $query->onlyTrashed();
        }

        $sucursales = $query->latest()
            ->paginate(10);

        $count = $query->get()->count();

        return Inertia::render('Sucursal/Sucursal', [
            'sucursales' => $sucursales,
            'conteo' => $count,
            'empresa' => $empresa,
            'showDeleted' => $request->boolean('deleted'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $empresas = [];
        if (Auth::user()->tipo == 'superAdmin') {
            $empresas = Empresa::get();
        } else {
            $sucursalUser = Sucursales::where('id',  SucursalService::getSucursalActiva())->first();
            $empresas = Empresa::where('id', $sucursalUser->id_empresa)->get();
        }
        return Inertia::render('Sucursal/CreateSucursal', [
            'empresas' => $empresas,
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
            'ticket_width_mm' => 'nullable|in:58,80',
        ], [
            'nombre.required' => 'Agregar un nombre de sucursal.',
            'direccion.required' => 'Agregar una dirección.',
            'ticket_width_mm' => 'Agregar un formato de ticket valido.'
        ]);

        Sucursales::create([
            'nombre' => $request->nombre,
            'direccion' => $request->direccion,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'id_empresa' => $request->id_empresa,
            'es_matriz' => $request->es_matriz,
            'ticket_width_mm' => $request->ticket_width_mm ?? 80,
        ]);

        return redirect()->route('sucursal.index');
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $empresas = [];
        if (Auth::user()->tipo == 'superAdmin') {
            $empresas = Empresa::get();
        } else {
            $sucursalUser = Sucursales::where('id',  SucursalService::getSucursalActiva())->first();
            $empresas = Empresa::where('id', $sucursalUser->id_empresa)->get();
        }
        $sucursal = Sucursales::find($id);
        return Inertia::render('Sucursal/CreateSucursal', compact('sucursal', 'empresas'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'direccion' => 'required',
            'ticket_width_mm' => 'nullable|in:58,80',
        ], [
            'nombre.required' => 'Agregar un nombre de sucursal.',
            'direccion.required' => 'Agregar una dirección.',
            'ticket_width_mm.required' => 'Agregar un formato de ticket valido.'
        ]);
        $sucursal = Sucursales::find($request->id);
        $sucursal->nombre = $request->nombre;
        $sucursal->direccion = $request->direccion;
        $sucursal->telefono = $request->telefono;
        $sucursal->email = $request->email;
        $sucursal->es_matriz = $request->es_matriz;
        $sucursal->id_empresa = $request->id_empresa;
        if ($request->filled('ticket_width_mm')) {
            $sucursal->ticket_width_mm = (int) $request->ticket_width_mm;
        }
        $sucursal->save();
        return redirect()->route('sucursal.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sucursales $sucursal)
    {
        $sucursal->delete(); // Soft delete
        return redirect()->route('sucursal.index');
    }

    public function restore($id)
    {
        $sucursal = Sucursales::onlyTrashed()->findOrFail($id);
        $sucursal->restore();
        return redirect()->route('sucursal.index');
    }
}
