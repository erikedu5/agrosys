<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Sucursales;
use App\Models\Clientes;
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
            $sucursalUser = Sucursales::where('id', SucursalService::getSucursalActiva())->first();
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
            $sucursalUser = Sucursales::where('id', SucursalService::getSucursalActiva())->first();
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

        // Iniciar logging del evento
        $logger = \App\Services\EventLogger::start('CREAR_SUCURSAL', [
            'nombre' => $request->nombre,
            'empresa_id' => $request->id_empresa,
            'es_matriz' => $request->es_matriz,
        ]);

        // Enforcement: limite de sucursales segun plan (si aplica).
        $empresa = $request->id_empresa ? Empresa::find($request->id_empresa) : null;
        if ($empresa && $empresa->plan_code && $empresa->numero_sucursales) {
            $actual = Sucursales::where('id_empresa', $empresa->id)->count();
            if ($actual >= (int) $empresa->numero_sucursales) {
                $logger->warning('Limite de sucursales alcanzado', [
                    'empresa_id' => $empresa->id,
                    'max' => (int) $empresa->numero_sucursales,
                    'actual' => $actual,
                ]);
                return back()->with('error', 'Limite de sucursales alcanzado para tu plan. Actualiza tu suscripcion para agregar mas.');
            }
        }

        try {
            $sucursal = Sucursales::create([
                'nombre' => $request->nombre,
                'direccion' => $request->direccion,
                'telefono' => $request->telefono,
                'email' => $request->email,
                'id_empresa' => $request->id_empresa,
                'es_matriz' => $request->es_matriz,
                'ticket_width_mm' => $request->ticket_width_mm ?? 80,
            ]);

            $logger->step('Sucursal creada', [
                'sucursal_id' => $sucursal->id,
                'nombre' => $sucursal->nombre,
            ]);

            // Crear cliente "Público en general" para la nueva sucursal
            $clientePublico = Clientes::create([
                'nombre' => 'Público en general',
                'porcentaje_descuento' => 0,
                'adeudo_total' => 0,
                'abono_total' => 0,
                'balance' => 0,
                'requiereFactura' => false,
                'activo' => true,
                'rfc' => null,
                'id_sucursal' => $sucursal->id,
            ]);

            $logger->step('Cliente público creado', [
                'cliente_id' => $clientePublico->id,
            ]);

            // Actualizar la sucursal con el ID del cliente público
            $sucursal->update(['id_cliente_publico' => $clientePublico->id]);

            $logger->success([
                'sucursal_id' => $sucursal->id,
                'cliente_publico_id' => $clientePublico->id,
            ]);

            return redirect()->route('sucursal.index');
        } catch (\Exception $e) {
            $logger->error($e, [
                'nombre' => $request->nombre,
            ]);

            return back()->with('error', 'Error al crear sucursal: ' . $e->getMessage());
        }
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
            $sucursalUser = Sucursales::where('id', SucursalService::getSucursalActiva())->first();
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

        // Iniciar logging del evento
        $logger = \App\Services\EventLogger::start('ACTUALIZAR_SUCURSAL', [
            'sucursal_id' => $request->id,
            'nombre' => $request->nombre,
        ]);

        try {
            $sucursal = Sucursales::find($request->id);

            if (!$sucursal) {
                $logger->warning('Sucursal no encontrada', [
                    'sucursal_id' => $request->id,
                ]);
                return redirect()->route('sucursal.index')->with('error', 'Sucursal no encontrada.');
            }

            $logger->step('Valores anteriores', [
                'nombre' => $sucursal->nombre,
                'direccion' => $sucursal->direccion,
                'es_matriz' => $sucursal->es_matriz,
            ]);

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

            $logger->success([
                'sucursal_id' => $sucursal->id,
                'nombre' => $sucursal->nombre,
            ]);

            return redirect()->route('sucursal.index');
        } catch (\Exception $e) {
            $logger->error($e, [
                'sucursal_id' => $request->id,
            ]);

            return back()->with('error', 'Error al actualizar sucursal: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sucursales $sucursal)
    {
        // Iniciar logging del evento
        $logger = \App\Services\EventLogger::start('ELIMINAR_SUCURSAL', [
            'sucursal_id' => $sucursal->id,
            'nombre' => $sucursal->nombre,
        ]);

        try {
            $sucursal->delete(); // Soft delete

            $logger->success([
                'sucursal_id' => $sucursal->id,
                'operacion' => 'soft_delete',
            ]);

            return redirect()->route('sucursal.index');
        } catch (\Exception $e) {
            $logger->error($e, [
                'sucursal_id' => $sucursal->id,
            ]);

            return back()->with('error', 'Error al eliminar sucursal: ' . $e->getMessage());
        }
    }

    public function restore($id)
    {
        // Iniciar logging del evento
        $logger = \App\Services\EventLogger::start('RESTAURAR_SUCURSAL', [
            'sucursal_id' => $id,
        ]);

        try {
            $sucursal = Sucursales::onlyTrashed()->findOrFail($id);

            $logger->step('Sucursal encontrada', [
                'nombre' => $sucursal->nombre,
            ]);

            $sucursal->restore();

            $logger->success([
                'sucursal_id' => $sucursal->id,
                'nombre' => $sucursal->nombre,
            ]);

            return redirect()->route('sucursal.index');
        } catch (\Exception $e) {
            $logger->error($e, [
                'sucursal_id' => $id,
            ]);

            return back()->with('error', 'Error al restaurar sucursal: ' . $e->getMessage());
        }
    }
}
