<?php

namespace App\Http\Controllers;

use App\Models\Clientes;
use App\Exceptions\FacturapiException;
use App\Models\Sucursales;
use App\Services\FacturapiService;
use App\Services\SucursalService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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

        $request->validate([
            'nombre' => ['required'],
            'porcentaje_descuento' => ['required', 'numeric', 'min:0', 'max:100'],
            'requiereFactura' => ['required', 'boolean'],
            'rfc' => [
                $request->boolean('requiereFactura') ? 'required' : 'nullable',
                'string',
                'max:13',
            ],
            'regimen_fiscal' => [
                $request->boolean('requiereFactura') ? 'required' : 'nullable',
                'string',
                'max:4',
            ],
            'codigo_postal' => [
                $request->boolean('requiereFactura') ? 'required' : 'nullable',
                'string',
                'max:10',
            ],
            'uso_cfdi' => [
                $request->boolean('requiereFactura') ? 'required' : 'nullable',
                'string',
                'max:5',
            ],
            'email_facturacion' => [
                'nullable',
                'email',
                'max:255',
            ],
        ], [
            'nombre.required' => 'Por favor ingresa el nombre del cliente.',
            'porcentaje_descuento.required' => 'Agregar un porcentage para el cliente.',
            'porcentaje_descuento.numeric' => 'El porcentaje de descuento debe ser numerico.',
            'porcentaje_descuento.min' => 'El porcentaje de descuento no puede ser negativo.',
            'porcentaje_descuento.max' => 'El porcentaje de descuento no puede ser mayor a 100.',
            'requiereFactura.required' => 'Favor de validar si requiere factura.',
            'requiereFactura.boolean' => 'El campo requiere factura debe ser verdadero o falso.',
            'porcentaje_descuento.numeric' => 'El porcentaje de descuento debe ser numerico.',
            'porcentaje_descuento.min' => 'El porcentaje de descuento no puede ser negativo.',
            'porcentaje_descuento.max' => 'El porcentaje de descuento no puede ser mayor a 100.',
            'rfc.required' => 'El RFC es obligatorio para clientes que requieren factura.',
            'regimen_fiscal.required' => 'El regimen fiscal es obligatorio para clientes que requieren factura.',
            'codigo_postal.required' => 'El codigo postal es obligatorio para clientes que requieren factura.',
            'uso_cfdi.required' => 'El uso de CFDI es obligatorio para clientes que requieren factura.',
            'email_facturacion.email' => 'El correo de facturacion debe tener un formato valido.',
        ]);


        $porcentaje = (float) $request->porcentaje_descuento;
        if (!is_finite($porcentaje) || $porcentaje < 0 || $porcentaje > 100) {
            throw ValidationException::withMessages([
                'porcentaje_descuento' => 'El porcentaje de descuento debe ser un numero valido entre 0 y 100.',
            ]);
        }

        $clienteData = [
            'nombre' => $request->nombre,
            'porcentaje_descuento' => $porcentaje,
            'adeudo_total' => 0,
            'abono_total' => 0,
            'balance' => 0,
            'requiereFactura' => $request->boolean('requiereFactura'),
            'activo' => true,
            'rfc' => $request->boolean('requiereFactura') ? strtoupper($request->rfc) : null,
            'regimen_fiscal' => $request->boolean('requiereFactura') ? strtoupper($request->regimen_fiscal) : null,
            'codigo_postal' => $request->boolean('requiereFactura') ? $request->codigo_postal : null,
            'uso_cfdi' => $request->boolean('requiereFactura') ? strtoupper($request->uso_cfdi) : null,
            'email_facturacion' => $request->boolean('requiereFactura') ? $request->email_facturacion : null,
            'id_sucursal' => SucursalService::getSucursalActiva(),
        ];

        $cliente = Clientes::create($clienteData);

        if ($cliente->requiereFactura) {
            $this->sincronizarClienteFacturacion($cliente);
        }

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
            'nombre' => ['required'],
            'porcentaje_descuento' => ['required', 'numeric', 'min:0', 'max:100'],
            'requiereFactura' => ['required', 'boolean'],
            'rfc' => [
                $request->boolean('requiereFactura') ? 'required' : 'nullable',
                'string',
                'max:13',
            ],
            'regimen_fiscal' => [
                $request->boolean('requiereFactura') ? 'required' : 'nullable',
                'string',
                'max:4',
            ],
            'codigo_postal' => [
                $request->boolean('requiereFactura') ? 'required' : 'nullable',
                'string',
                'max:10',
            ],
            'uso_cfdi' => [
                $request->boolean('requiereFactura') ? 'required' : 'nullable',
                'string',
                'max:5',
            ],
            'email_facturacion' => [
                'nullable',
                'email',
                'max:255',
            ],
        ], [
            'porcentaje_descuento.numeric' => 'El porcentaje de descuento debe ser numerico.',
            'porcentaje_descuento.min' => 'El porcentaje de descuento no puede ser negativo.',
            'porcentaje_descuento.max' => 'El porcentaje de descuento no puede ser mayor a 100.',
            'rfc.required' => 'El RFC es obligatorio para clientes que requieren factura.',
            'regimen_fiscal.required' => 'El regimen fiscal es obligatorio para clientes que requieren factura.',
            'codigo_postal.required' => 'El codigo postal es obligatorio para clientes que requieren factura.',
            'uso_cfdi.required' => 'El uso de CFDI es obligatorio para clientes que requieren factura.',
            'email_facturacion.email' => 'El correo de facturacion debe tener un formato valido.',
        ]);


        $porcentaje = (float) $request->porcentaje_descuento;
        if (!is_finite($porcentaje) || $porcentaje < 0 || $porcentaje > 100) {
            throw ValidationException::withMessages([
                'porcentaje_descuento' => 'El porcentaje de descuento debe ser un numero valido entre 0 y 100.',
            ]);
        }

        $cliente = Clientes::where('id', $request->id)
            ->where('activo', true)->first();

        $cliente->nombre = $request->nombre;
        $cliente->porcentaje_descuento = $porcentaje;
        $cliente->requiereFactura = $request->boolean('requiereFactura');
        if ($request->requiereFactura) {
            $cliente->rfc = strtoupper($request->rfc);
            $cliente->regimen_fiscal = strtoupper($request->regimen_fiscal);
            $cliente->codigo_postal = $request->codigo_postal;
            $cliente->uso_cfdi = strtoupper($request->uso_cfdi);
            $cliente->email_facturacion = $request->email_facturacion;
        } else {
            $cliente->rfc = null;
            $cliente->regimen_fiscal = null;
            $cliente->codigo_postal = null;
            $cliente->uso_cfdi = null;
            $cliente->email_facturacion = null;
        }
        $cliente->save();

        if ($cliente->requiereFactura) {
            $this->sincronizarClienteFacturacion($cliente);
        }

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

    private function sincronizarClienteFacturacion(Clientes $cliente): void
    {
        $sucursal = Sucursales::with('empresa')->find($cliente->id_sucursal);
        $empresa = $sucursal?->empresa;
        $service = FacturapiService::make($empresa);

        if (!$service) {
            return;
        }

        try {
            $service->ensureCustomer($cliente);
        } catch (FacturapiException $exception) {
            Log::warning('No se pudo sincronizar el cliente en Facturapi', [
                'cliente' => $cliente->id,
                'empresa' => $empresa?->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
