<?php

namespace App\Http\Controllers;

use App\Models\Clientes;
use App\Services\SucursalService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ClientesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $clientes = Clientes::where('nombre', 'LIKE', "%$request->q%")
            ->where('id_sucursal', SucursalService::getSucursalActiva())
            ->where('activo', true)
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

        $request->validate(
            [
                'nombre' => 'required',
                'porcentaje_descuento' => 'required',
                'requiereFactura' => 'required',
            ],
            [
                'nombre.required' => 'Por favor ingresa el nombre del cliente.',
                'porcentaje_descuento.required' => 'Agregar un porcentage para el cliente.',
                'requiereFactura.required' => 'Favor de validar si require factura.',
            ]
        );

        $cliente = [
            'nombre' => $request->nombre,
            'porcentaje_descuento' => $request->porcentaje_descuento,
            'adeudo_total' => 0,
            'abono_total' => 0,
            'balance' => 0,
            'requiereFactura' => $request->requiereFactura,
            'activo' => true,
            'rfc' => $request->rfc,
            'id_sucursal' => SucursalService::getSucursalActiva(),
        ];
        Clientes::create($cliente);
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
            'requiereFactura' => 'required',
        ]);

        $cliente = Clientes::where('id', $request->id)
            ->where('activo', true)->first();

        $cliente->nombre = $request->nombre;
        $cliente->porcentaje_descuento = $request->porcentaje_descuento;
        $cliente->requiereFactura = $request->requiereFactura;
        if ($request->requiereFactura) {
            $cliente->rfc = $request->rfc;
        }
        $cliente->save();
        return redirect()->route('cliente.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $idCliente)
    {
        $cliente = Clientes::where($idCliente)
            ->where('activo', true)->first();
        $cliente->activo = false;
        $cliente->save();
        return redirect()->route('cliente.index');
    }
}
