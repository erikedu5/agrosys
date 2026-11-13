<?php

namespace App\Http\Controllers;

use App\Models\Clientes;
use App\Exceptions\FacturapiException;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Services\FacturapiService;
use App\Services\SucursalService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
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
            'tipo_precio' => ['nullable', Rule::in(array_keys(Producto::PRECIOS))],
            'limite_credito' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'plazo_credito_dias' => ['nullable', 'integer', 'min:1', 'max:3650'],
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
            'tipo_precio.in' => 'El tipo de precio seleccionado no es valido.',
            'limite_credito.numeric' => 'El limite de credito debe ser numerico.',
            'limite_credito.min' => 'El limite de credito no puede ser negativo.',
            'plazo_credito_dias.integer' => 'El plazo de credito debe ser un numero entero de dias.',
            'plazo_credito_dias.min' => 'El plazo de credito debe ser de al menos 1 dia.',
            'requiereFactura.required' => 'Favor de validar si requiere factura.',
            'requiereFactura.boolean' => 'El campo requiere factura debe ser verdadero o falso.',
            'rfc.required' => 'El RFC es obligatorio para clientes que requieren factura.',
            'regimen_fiscal.required' => 'El regimen fiscal es obligatorio para clientes que requieren factura.',
            'codigo_postal.required' => 'El codigo postal es obligatorio para clientes que requieren factura.',
            'uso_cfdi.required' => 'El uso de CFDI es obligatorio para clientes que requieren factura.',
            'email_facturacion.email' => 'El correo de facturacion debe tener un formato valido.',
        ]);



        $clienteData = [
            'nombre' => $request->nombre,
            'tipo_precio' => $request->input('tipo_precio') ?: 'publico',
            'limite_credito' => $request->input('limite_credito'),
            'plazo_credito_dias' => $request->input('plazo_credito_dias'),
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
    public function update(Request $request, string $cliente)
    {
        $request->validate([
            'nombre' => ['required'],
            'tipo_precio' => ['nullable', Rule::in(array_keys(Producto::PRECIOS))],
            'limite_credito' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'plazo_credito_dias' => ['nullable', 'integer', 'min:1', 'max:3650'],
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
            'tipo_precio.in' => 'El tipo de precio seleccionado no es valido.',
            'limite_credito.numeric' => 'El limite de credito debe ser numerico.',
            'limite_credito.min' => 'El limite de credito no puede ser negativo.',
            'plazo_credito_dias.integer' => 'El plazo de credito debe ser un numero entero de dias.',
            'plazo_credito_dias.min' => 'El plazo de credito debe ser de al menos 1 dia.',
            'rfc.required' => 'El RFC es obligatorio para clientes que requieren factura.',
            'regimen_fiscal.required' => 'El regimen fiscal es obligatorio para clientes que requieren factura.',
            'codigo_postal.required' => 'El codigo postal es obligatorio para clientes que requieren factura.',
            'uso_cfdi.required' => 'El uso de CFDI es obligatorio para clientes que requieren factura.',
            'email_facturacion.email' => 'El correo de facturacion debe tener un formato valido.',
        ]);



        $cliente = Clientes::where('id_sucursal', SucursalService::getSucursalActiva())
            ->where('activo', true)->findOrFail($cliente);

        $cliente->nombre = $request->nombre;
        $cliente->tipo_precio = $request->input('tipo_precio') ?: 'publico';
        $cliente->limite_credito = $request->input('limite_credito');
        $cliente->plazo_credito_dias = $request->input('plazo_credito_dias');
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
        $cliente = Clientes::where('id_sucursal', SucursalService::getSucursalActiva())
            ->where('activo', true)->findOrFail($idCliente);
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
