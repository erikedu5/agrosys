<?php

namespace App\Http\Controllers;

use App\Exceptions\FacturapiException;
use App\Models\Producto;
use App\Models\Clientes;
use App\Models\Empresa;
use App\Models\ProductoVenta;
use App\Models\Venta;
use App\Models\AbonoCuenta;
use App\Models\AltaInventario;
use App\Models\Factura;
use App\Models\Sucursales;
use App\Models\User;
use App\Services\FacturapiService;
use App\Services\SucursalService;
use App\Helpers\DatabaseHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;

class VentaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sucursal = SucursalService::getSucursalActiva();

        if (!$sucursal) {
            return redirect()->route('sucursal.selection');
        }

        $sucursalInfo = Sucursales::with('empresa')->find($sucursal);

        if (!$sucursalInfo) {
            return redirect()->route('sucursal.selection');
        }

        $empresaActiva = $sucursalInfo->empresa;
        $ventasBloqueadas = $empresaActiva?->ventas_bloqueadas ?? false;
        $motivoBloqueo = $ventasBloqueadas
            ? ($empresaActiva->motivo_bloqueo ?? 'Esta sección está bloqueada, Contacte a su administrador.')
            : null;

        $productosSucursalFiltrado = [];
        if ($request->b != null) {
            $sucursales = Sucursales::where('id_empresa', $sucursalInfo->id_empresa)
                ->where('id', '!=', $sucursal)
                ->pluck('id')
                ->toArray();

            $productosSucursal = Producto::where(function ($query) use ($request) {
                [$nombreSql, $nombreValue] = DatabaseHelper::getUnaccentFunction('productos.nombre', "%$request->b%");
                [$barcodeSql, $barcodeValue] = DatabaseHelper::getUnaccentFunction('productos.barcode', "%$request->b%");

                $query->whereRaw($nombreSql, [$nombreValue])
                    ->orWhereRaw($barcodeSql, [$barcodeValue]);
            })
                ->join('cat_marcas', 'cat_marcas.id', 'productos.id_marca')
                ->select('productos.*', 'cat_marcas.nombre as marca')
                ->get();

            foreach ($productosSucursal as $product) {
                $actualStock = AltaInventario::where('id_producto', $product->id)
                    ->whereIn('id_sucursal', $sucursales)
                    ->orderBy('created_at', 'desc')->first();
                if ($actualStock != null) {
                    $product->sucursal = Sucursales::find($actualStock->id_sucursal);
                    $product->cantidad = $actualStock !== null ? $actualStock->cantidad_nueva : 0;
                    if ($product->cantidad > 0) {
                        array_push($productosSucursalFiltrado, $product);
                    }
                }
            }
        }

        $productos = Producto::where(function ($query) use ($request) {
            [$nombreSql, $nombreValue] = DatabaseHelper::getUnaccentFunction('nombre', "%$request->q%");
            [$barcodeSql, $barcodeValue] = DatabaseHelper::getUnaccentFunction('barcode', "%$request->q%");

            $query->whereRaw($nombreSql, [$nombreValue])
                ->orWhereRaw($barcodeSql, [$barcodeValue]);
        })
            ->get();
        $productoFiltrado = [];

        foreach ($productos as $product) {
            $actualStock = AltaInventario::where('id_producto', $product->id)
                ->where('id_sucursal', $sucursal)
                ->orderBy('created_at', 'desc')->first();
            $product->cantidad = $actualStock !== null ? $actualStock->cantidad_nueva : 0;
            if ($product->cantidad > 0) {
                array_push($productoFiltrado, $product);
            }
        }
        $clientes = Clientes::where('id_sucursal', $sucursal)
            ->where('activo', true)
            ->get();

        // Obtener la sucursal actual para acceder al cliente público por defecto
        $clientePublicoId = $sucursalInfo ? $sucursalInfo->id_cliente_publico : null;

        return Inertia::render('Venta/Venta', [
            'productos' => $productoFiltrado,
            'clientes' => $clientes,
            'productosSucursal' => $productosSucursalFiltrado,
            'clientePublicoDefault' => $clientePublicoId,
            'tipoVentaDefault' => 'contado',
            'ventasBloqueadas' => $ventasBloqueadas,
            'motivoBloqueo' => $motivoBloqueo,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $sucursal = SucursalService::getSucursalActiva();

        $sucursalInfo = Sucursales::with('empresa')->find($sucursal);

        if (!$sucursalInfo) {
            throw ValidationException::withMessages([
                'bloqueo' => 'No se encontró la sucursal activa. Intente seleccionar nuevamente la sucursal.',
            ]);
        }

        $empresaActiva = $sucursalInfo->empresa;
        if ($empresaActiva && $empresaActiva->ventas_bloqueadas) {
            throw ValidationException::withMessages([
                'bloqueo' => $empresaActiva->motivo_bloqueo ?? 'Esta sección está bloqueada, Contacte a su administrador.',
            ]);
        }
        $facturacionAutomaticaEmpresa = (bool) ($empresaActiva?->enviar_facturas_automaticas);

        $request->validate(
            [
                'id_cliente' => ['required', 'integer', 'exists:clientes,id'],
                'total' => ['required', 'numeric', 'min:0'],
                'abono' => ['nullable', 'numeric', 'min:0'],
                'tipo_venta' => ['required', 'string'],
                'producto_venta' => ['required', 'array', 'min:1'],
                'producto_venta.*.producto.id' => ['required', 'integer', 'exists:productos,id'],
                'producto_venta.*.cantidad' => ['required', 'numeric', 'min:0.01'],
                'producto_venta.*.importe' => ['required', 'numeric', 'min:0'],
            ],
            [
                'id_cliente.required' => 'El cliente es requerido',
                'total.required' => 'El total es requerido.',
                'tipo_venta.required' => 'El tipo de venta es requerido.',
                'producto_venta.required' => 'Agregar al menos un producto para la venta.'
            ]
        );

        $total = (float) $request->total;
        if (!is_finite($total)) {
            throw ValidationException::withMessages([
                'total' => 'El total debe ser un valor numerico valido.',
            ]);
        }

        $abonoInput = $request->abono !== null ? (float) $request->abono : 0;
        if (!is_finite($abonoInput) || $abonoInput < 0) {
            throw ValidationException::withMessages([
                'abono' => 'El abono debe ser un monto numerico valido.',
            ]);
        }

        $productosSanitizados = collect($request->producto_venta)->map(function ($producto) {
            $cantidad = (float) ($producto['cantidad'] ?? 0);
            $importe = (float) ($producto['importe'] ?? 0);

            if (!is_finite($cantidad) || !is_finite($importe)) {
                throw ValidationException::withMessages([
                    'producto_venta' => 'Uno de los productos tiene valores no numericos. Verifique cantidades e importes.',
                ]);
            }

            $producto['cantidad'] = $cantidad;
            $producto['importe'] = $importe;
            return $producto;
        })->all();
        $id_usuario = Auth::user()->id;
        $venta_pagada = false;
        $fecha_pago = null;
        if ($request->tipo_venta === 'Contado') {
            $venta_pagada = true;
            $fecha_pago = date("Y-m-d H:i:s");
        }

        $venta = Venta::create([
            'id_cliente' => $request->id_cliente,
            'total' => $total,
            'id_usuario' => $id_usuario,
            'tipo_venta' => $request->tipo_venta,
            'venta_pagada' => $venta_pagada,
            'fecha_pago' => $fecha_pago,
            'id_sucursal' => $sucursal,
        ]);

        foreach ($productosSanitizados as $producto_venta) {
            ProductoVenta::create([
                'id_producto' => $producto_venta['producto']['id'],
                'id_venta' => $venta->id,
                'cantidad' => $producto_venta['cantidad'],
                'total_productos' => round($producto_venta['importe'], 2),
            ]);

            //Reduccion de stock;
            $stockStatus = AltaInventario::where('id_producto', $producto_venta['producto']['id'])
                ->where('id_sucursal', $sucursalInfo->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $altaInventario = new AltaInventario();
            $cantidadBase = $stockStatus?->cantidad_nueva ?? 0;
            $altaInventario->cantidad_actual = $cantidadBase;
            $altaInventario->cantidad_nueva = $cantidadBase - $producto_venta['cantidad'];
            $altaInventario->id_usuario = Auth::user()->id;
            $altaInventario->id_producto = $producto_venta['producto']['id'];
            $altaInventario->id_sucursal = $sucursalInfo->id;
            $altaInventario->tipo_evento = AltaInventario::EVENTO_VENTA;
            $altaInventario->save();
        }

        $abonoVenta = [];
        $abonado = 0;
        if ($request->tipo_venta == 'Contado') {
            $abonado = $total;
            $abonoVenta = [
                'cantidad_abonada' => $total,
                'cuenta_pagada' => true,
                'id_cliente' => $request->id_cliente,
                'id_usuario' => Auth::user()->id,
                'is_active' => false,
                'id_sucursal' => $sucursal,
            ];
        } else if ($request->tipo_venta == 'Credito') {
            $abonado = $abonoInput;
            $abonoVenta = [
                'cantidad_abonada' => $abonado,
                'cuenta_pagada' => false,
                'id_cliente' => $request->id_cliente,
                'id_usuario' => Auth::user()->id,
                'is_active' => true,
                'id_sucursal' => $sucursal,
            ];
        }

        AbonoCuenta::create($abonoVenta);

        $cliente = Clientes::find($request->id_cliente);
        $cliente->adeudo_total += $total;
        $cliente->abono_total += $abonado;
        $cliente->balance = $cliente->adeudo_total - $cliente->abono_total;
        $cliente->save();

        if ($facturacionAutomaticaEmpresa && $cliente->requiereFactura) {
            Log::info('Iniciando proceso de facturación automática para venta', [
                'venta_id' => $venta->id,
                'cliente_id' => $cliente->id,
            ]);
            $this->agregarFacturacion($venta, $cliente);
        }

        if ($cliente->balance <= 0) {
            $this->limpiarCredito($cliente);
        }

        [$nombreSql, $nombreValue] = DatabaseHelper::getUnaccentFunction('nombre', "%$request->q%");
        $productos = Producto::whereRaw($nombreSql, [$nombreValue])
            ->where('cantidad', '!=', 0)
            ->get();

        $clientes = Clientes::get();

        return Inertia::render('Venta/Venta', [
            'productos' => $productos,
            'clientes' => $clientes,
            'venta' => $venta,
        ]);
    }

    public function ticket(\Illuminate\Http\Request $request, Venta $venta)
    {
        $sucursal = SucursalService::getSucursalActiva();

        $productos = ProductoVenta::where('id_venta', $venta->id)->get();
        foreach ($productos as $producto) {
            $producto->detail = Producto::find($producto->id_producto);
        }

        $cliente = Clientes::where('id', $venta->id_cliente)->first();
        $usuario = User::where('id', $venta->id_usuario)->first();

        $sucursalActual = Sucursales::find($sucursal);
        $empresa = $sucursalActual ? Empresa::find($sucursalActual->id_empresa) : null;

        $abonos = AbonoCuenta::where('id_cliente', $venta->id_cliente)
            ->where('id_sucursal', $sucursal)
            ->where('is_active', true)
            ->get();

        // Elegir ancho por parámetro ?size=80|58 (mm); por sucursal por defecto (80 si no definido)
        $sucursalPref = optional($sucursalActual)->ticket_width_mm ?? 80;
        $ticketWidthMm = (int) $request->query('size', $sucursalPref);
        $ticketWidthMm = in_array($ticketWidthMm, [58, 80]) ? $ticketWidthMm : 80;
        $widthPoints = $ticketWidthMm * 2.83465; // mm a puntos

        $viewData = compact('venta', 'productos', 'cliente', 'empresa', 'abonos', 'usuario', 'ticketWidthMm');
        $viewData['sucursal'] = $sucursalActual;

        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->set_option('enable_php', true);
        $pdf->loadView('reportes/venta_ticket_80mm', $viewData);
        // Ancho según parámetro, alto largo para contenido
        $customPaper = [0, 0, $widthPoints, 2834.65];
        $pdf->setPaper($customPaper, 'portrait');
        $pdf->setOption('javascript-delay', 500);

        return $pdf->stream('ticket_venta.pdf');
    }

    public function ticketHtml(\Illuminate\Http\Request $request, Venta $venta)
    {
        $sucursal = SucursalService::getSucursalActiva();

        $productos = ProductoVenta::where('id_venta', $venta->id)->get();
        foreach ($productos as $producto) {
            $producto->detail = Producto::find($producto->id_producto);
        }

        $cliente = Clientes::find($venta->id_cliente);
        $usuario = User::find($venta->id_usuario);

        $sucursalActual = Sucursales::find($sucursal);
        $empresa = $sucursalActual ? Empresa::find($sucursalActual->id_empresa) : null;

        $abonos = AbonoCuenta::where('id_cliente', $venta->id_cliente)
            ->where('id_sucursal', $sucursal)
            ->where('is_active', true)
            ->get();

        // 58 o 80 mm
        $sucursalPref = optional($sucursalActual)->ticket_width_mm ?? 80;
        $ticketWidthMm = (int) $request->query('size', $sucursalPref);
        $ticketWidthMm = in_array($ticketWidthMm, [58, 80]) ? $ticketWidthMm : 80;

        $viewData = compact(
            'venta',
            'productos',
            'cliente',
            'empresa',
            'abonos',
            'usuario',
            'ticketWidthMm'
        );
        $viewData['sucursal'] = $sucursalActual;

        return view('reportes.venta_ticket_80mm', $viewData);
    }

    /**
     * Obtiene la última venta registrada en la sucursal activa para reimpresión.
     */
    public function ultimoTicketPorSucursal(Request $request)
    {
        $sucursal = SucursalService::getSucursalActiva();

        if (!$sucursal) {
            return response()->json([
                'message' => 'Seleccione una sucursal para reimprimir tickets.',
            ], 422);
        }

        $ultimaVenta = Venta::where('id_sucursal', $sucursal)
            ->latest('created_at')
            ->first();

        if (!$ultimaVenta) {
            return response()->json([
                'message' => 'No existen tickets registrados para esta sucursal.',
            ], 404);
        }

        return response()->json([
            'venta_id' => $ultimaVenta->id,
            'total' => $ultimaVenta->total,
            'tipo_venta' => $ultimaVenta->tipo_venta,
            'fecha' => optional($ultimaVenta->created_at)->toDateTimeString(),
        ]);
    }


    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $sucursal = SucursalService::getSucursalActiva();

        $cliente = clientes::where('id', $id)
            ->where('id_sucursal', $sucursal)
            ->first();

        $ventas = Venta::where('id_cliente', $id)
            ->where('venta_pagada', false)
            ->where('id_sucursal', $sucursal)
            ->latest()
            ->paginate(5);

        foreach ($ventas as $venta) {
            $productosVenta = ProductoVenta::where('id_venta', $venta->id)->get();
            foreach ($productosVenta as $productoVenta) {
                $detalleProducto = Producto::where('id', $productoVenta->id_producto)->first();
                if ($detalleProducto !== null) {
                    $productoVenta->detalle = $detalleProducto->nombre;
                }
            }
            $venta->productos = $productosVenta;
        }

        $abonos = AbonoCuenta::where('id_cliente', $id)
            ->where('is_active', true)
            ->latest()
            ->paginate(5);

        return Inertia::render('Cliente/ViewCredit', [
            'ventas' => $ventas,
            'cliente' => $cliente,
            'abonos' => $abonos
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $sucursal = SucursalService::getSucursalActiva();

        $request->validate([
            'id' => ['required', 'integer', 'exists:clientes,id'],
            'abono' => ['required', 'numeric', 'min:0.01'],
        ]);

        $abono = (float) $request->abono;
        if (!is_finite($abono)) {
            throw ValidationException::withMessages([
                'abono' => 'El abono debe ser un monto numerico valido.',
            ]);
        }

        AbonoCuenta::Create([
            'cantidad_abonada' => $abono,
            'cuenta_pagada' => true,
            'id_usuario' => Auth::user()->id,
            'id_sucursal' => $sucursal,
            'id_cliente' => $request->id,
            'is_active' => true
        ]);

        $cliente = Clientes::find($request->id);
        $cliente->abono_total += $abono;
        $cliente->balance = $cliente->adeudo_total - $cliente->abono_total;
        $cliente->save();

        if ($cliente->balance <= 0) {
            $this->limpiarCredito($cliente);
        }

        return redirect()->route('venta.show', $request->id);
    }

    public function limpiarCredito($cliente)
    {
        $ventas = Venta::where('id_cliente', $cliente->id)->get();

        $abonos = AbonoCuenta::where('id_cliente', $cliente->id)->get();
        foreach ($abonos as $abono) {
            $abono->is_active = false;
            $abono->save();
        }

        foreach ($ventas as $venta) {
            $venta->venta_pagada = true;
            $venta->fecha_pago = date('Y-m-d h:i:s');
            $venta->save();
        }

        $cliente->adeudo_total = 0;
        $cliente->abono_total = 0;
        $cliente->balance = 0;
        $cliente->save();
    }

    private function agregarFacturacion(Venta $venta, Clientes $cliente): void
    {
        $sucursal = Sucursales::with('empresa')->find($venta->id_sucursal);
        $empresa = $sucursal?->empresa;

        if (!$empresa || !$empresa->enviar_facturas_automaticas) {
            Log::info('Facturación automática deshabilitada para la empresa', [
                'venta_id' => $venta->id,
                'cliente_id' => $cliente->id,
                'empresa_id' => $empresa?->id,
            ]);
            return;
        }

        $facturaData = [
            'facturaCompleta' => false,
            'id_venta' => $venta->id,
            'id_cliente' => $cliente->id,
        ];

        $service = FacturapiService::make($empresa);

        Log::info('Verificando servicio de Facturapi para facturación automática', [
            'venta_id' => $venta->id,
            'cliente_id' => $cliente->id,
            'empresa_id' => $empresa?->id,
            'service_exists' => $service,
        ]);

        if (!$service) {
            $facturaData['factura_status'] = 'sin_configuracion';
            $facturaData['factura_error'] = 'No hay llave de Facturapi configurada para la empresa.';
            Factura::create($facturaData);
            return;
        }

        try {
            $invoice = $service->createInvoice($venta, $cliente);
            $status = $invoice['status'] ?? null;

            $facturaData = array_merge($facturaData, [
                'facturapi_invoice_id' => $invoice['id'] ?? null,
                'facturapi_uuid' => $invoice['uuid'] ?? null,
                'facturapi_pdf_url' => $invoice['pdf_url'] ?? null,
                'facturapi_xml_url' => $invoice['xml_url'] ?? null,
                'factura_status' => $status,
                'facturaCompleta' => in_array($status, ['completed', 'issued', 'valid'], true),
            ]);
            log::info('Factura creada exitosamente en Facturapi', [
                'factura_data' => $facturaData,
                'venta' => $venta->id,
                'cliente' => $cliente->id,
                'empresa' => $empresa?->id,
            ]);
        } catch (FacturapiException $exception) {
            $facturaData['factura_status'] = 'error';
            $facturaData['factura_error'] = $exception->getMessage();
            Log::error('Error en la facturación automática', [
                'venta' => $venta->id,
                'cliente' => $cliente->id,
                'empresa' => $empresa?->id,
                'error' => $exception->getMessage(),
            ]);
        }

        $factura = Factura::create($facturaData);

        Log::info('Factura registrada en sistema', [
            'factura_id' => $factura->id,
            'venta' => $venta->id,
            'cliente' => $cliente->id,
            'status' => $factura->factura_status,
            'completa' => $factura->facturaCompleta,
            'facturapi_invoice_id' => $factura->facturapi_invoice_id,
        ]);
    }

    /**
     * Buscar precio y stock de productos por nombre o código de barras
     */
    public function buscarPrecio(Request $request)
    {
        $query = $request->get('q', '');

        if (empty($query)) {
            return response()->json(['productos' => []]);
        }

        $sucursalActiva = SucursalService::getSucursalActiva();

        $productos = Producto::where(function ($q) use ($query) {
            [$nombreSql, $nombreValue] = DatabaseHelper::getUnaccentFunction('productos.nombre', "%{$query}%");
            [$barcodeSql, $barcodeValue] = DatabaseHelper::getUnaccentFunction('productos.barcode', "%{$query}%");

            $q->whereRaw($nombreSql, [$nombreValue])
                ->orWhereRaw($barcodeSql, [$barcodeValue]);
        })
            ->join('cat_marcas', 'cat_marcas.id', '=', 'productos.id_marca')
            ->join('cat_clasificacions', 'cat_clasificacions.id', '=', 'productos.id_clasificacion')
            ->select(
                'productos.id',
                'productos.nombre',
                'productos.barcode',
                'productos.tamano',
                'productos.precio_unitario',
                'productos.precio_ieps',
                'cat_marcas.nombre as marca',
                'cat_clasificacions.nombre as clasificacion'
            )
            ->get();

        // Agregar información de stock para cada producto
        $productosConStock = $productos->map(function ($producto) use ($sucursalActiva) {
            $stock = AltaInventario::where('id_producto', $producto->id)
                ->where('id_sucursal', $sucursalActiva)
                ->orderBy('created_at', 'desc')
                ->first();

            $producto->stock = $stock ? $stock->cantidad_nueva : 0;
            $producto->nombre_completo = $producto->nombre . ' - ' . $producto->tamano;

            return $producto;
        });

        return response()->json(['productos' => $productosConStock]);
    }
}
