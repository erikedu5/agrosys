<?php

namespace App\Http\Controllers;

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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class VentaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sucursal = Auth::user()->id_sucursal;
        $productosSucursalFiltrado = [];
        if ($request->b != null) {
            $sucursales = Sucursales::where('id_empresa', function ($query) use ($sucursal) {
                $query->select('id_empresa')
                      ->from('sucursales')
                      ->where('id', $sucursal);
            })->where('id', '!=', $sucursal)->get()
            ->pluck('id')
            ->toArray();

            $productosSucursal = Producto::where('productos.nombre', 'LIKE', "%$request->b%")
            ->join('cat_marcas', 'cat_marcas.id', 'productos.id_marca')
            ->select('productos.*', 'cat_marcas.nombre as marca')
            ->get();

            foreach ($productosSucursal as $product) {
                $actualStock = AltaInventario::where('id_producto', $product->id)
                    ->whereIn('id_sucursal', $sucursales)
                    ->orderBy('created_at', 'desc')->first();
                if ($actualStock != null) {
                    $product->sucursal = Sucursales::find($actualStock->id_sucursal);
                    $product->cantidad = $actualStock !== null? $actualStock->cantidad_nueva: 0;
                    if ($product->cantidad > 0) {
                        array_push($productosSucursalFiltrado, $product);
                    }
                }
            }
        } 

        $productos = Producto::where('nombre', 'LIKE', "%$request->q%")
        ->get();
        $productoFiltrado = [];

        foreach ($productos as $product) {
            $actualStock = AltaInventario::where('id_producto', $product->id)
                ->where('id_sucursal',  Auth::user()->id_sucursal)
                ->orderBy('created_at', 'desc')->first();
            $product->cantidad = $actualStock !== null? $actualStock->cantidad_nueva: 0;
            if ($product->cantidad > 0) {
                array_push($productoFiltrado, $product);
            }
        }
        $clientes = Clientes::where('id_sucursal',  Auth::user()->id_sucursal)
        ->where('activo', true)
        ->get();

        return Inertia::render('Venta/Venta', [
            'productos' => $productoFiltrado,
            'clientes' => $clientes,
            'productosSucursal' => $productosSucursalFiltrado
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_cliente' => ['required'],
            'total' => ['required'],
            'tipo_venta' => ['required'],
            'producto_venta' => ['required']
            ]
        );
        $id_usuario = Auth::user()->id;
        $venta_pagada = false;
        $fecha_pago = null;
        if ($request->tipo_venta === 'Contado') {
            $venta_pagada = true;
            $fecha_pago = date("Y-m-d H:i:s");
        }

        $venta = Venta::create([
            'id_cliente' => $request->id_cliente,
            'total' => $request->total,
            'id_usuario' => $id_usuario,
            'tipo_venta'=> $request->tipo_venta,
            'venta_pagada'=> $venta_pagada,
            'fecha_pago'=> $fecha_pago,
            'id_sucursal'=> Auth::user()->id_sucursal,
        ]);

        foreach ($request->producto_venta as $producto_venta) {
            ProductoVenta::create([
                'id_producto' => $producto_venta['producto']['id'],
                'id_venta' => $venta->id,
                'cantidad' => $producto_venta['cantidad'],
                'total_productos' => $producto_venta['importe'],
            ]);

            //Reduccion de stock;
            $stockStatus = AltaInventario::where('id_producto', $producto_venta['producto']['id'])
            ->orderBy('created_at', 'desc')->first();

            $altaInventario = new AltaInventario();
            $altaInventario->cantidad_actual = $stockStatus->cantidad_nueva;
            $altaInventario->cantidad_nueva = $stockStatus->cantidad_nueva - $producto_venta['cantidad'];
            $altaInventario->id_usuario = Auth::user()->id ;
            $altaInventario->id_producto = $producto_venta['producto']['id'];
            $altaInventario->id_sucursal = Auth::user()->id_sucursal;
            $altaInventario->save();
        }

        $abonoVenta = [];
        $abonado = 0;
        if ($request->tipo_venta == 'Contado') {
            $abonado = $request->total;
            $abonoVenta = [
                'cantidad_abonada' => $request->total,
                'cuenta_pagada' => true,
                'id_cliente' => $request->id_cliente,
                'id_usuario' => Auth::user()->id,
                'is_active' => false,
                'id_sucursal' =>Auth::user()->id_sucursal,
            ];
        } else if ($request->tipo_venta == 'Credito') {
            $abonado = $request->abono;
            $abonoVenta = [
                'cantidad_abonada' => $request->abono,
                'cuenta_pagada' => false,
                'id_cliente' => $request->id_cliente,
                'id_usuario' => Auth::user()->id,
                'is_active' => true,
                'id_sucursal' =>Auth::user()->id_sucursal,
            ];
        }

        AbonoCuenta::create($abonoVenta);

        $cliente = Clientes::find($request->id_cliente);
        $cliente->adeudo_total +=  $request->total;
        $cliente->abono_total += $abonado;
        $cliente->balance = $cliente->adeudo_total - $cliente->abono_total;
        $cliente->save();

        if ($cliente->requiereFactura) {
            $this->agregarFacturacion($venta, $cliente);
        }

        if ($cliente->balance <= 0) {
            $this->limpiarCredito($cliente);
        }

        $productos = Producto::where('nombre', 'LIKE', "%$request->q%")
        ->where('cantidad', '!=' , 0)
        ->get();

        $clientes = Clientes::get();

        return Inertia::render('Venta/Venta', [
            'productos' => $productos,
            'clientes' => $clientes,
            'venta' => $venta,
        ]);
    }

    public function ticket(\Illuminate\Http\Request $request, Venta $venta) {
        $productos = ProductoVenta::where('id_venta', $venta->id)->get();
        foreach ($productos as $producto) {
            $producto->detail = Producto::find($producto->id_producto);
        }

        $cliente = Clientes::where('id', $venta->id_cliente)->first();
        $usuario = User::where('id', $venta->id_usuario)->first();

        $sucursalUser = Sucursales::where('id',  Auth::user()->id_sucursal)->first();
        $empresa = Empresa::where('id', $sucursalUser->id_empresa)->first();

        $abonos = AbonoCuenta::where('id_cliente', $venta->id_cliente)
        ->where('id_sucursal',  Auth::user()->id_sucursal)
        ->where('is_active', true)
        ->get();

        // Elegir ancho por parámetro ?size=80|58 (mm); por sucursal por defecto (80 si no definido)
        $sucursalPref = optional(Sucursales::find(Auth::user()->id_sucursal))->ticket_width_mm ?? 80;
        $ticketWidthMm = (int) $request->query('size', $sucursalPref);
        $ticketWidthMm = in_array($ticketWidthMm, [58, 80]) ? $ticketWidthMm : 80;
        $widthPoints = $ticketWidthMm * 2.83465; // mm a puntos

        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->set_option('enable_php', true);
        $pdf->loadView('reportes/venta_ticket_80mm', compact('venta', 'productos', 'cliente', 'empresa', 'abonos', 'usuario', 'ticketWidthMm'));
        // Ancho según parámetro, alto largo para contenido
        $customPaper = [0, 0, $widthPoints, 2834.65];
        $pdf->setPaper($customPaper, 'portrait');
        $pdf->setOption('javascript-delay', 500);

        return $pdf->stream('ticket_venta.pdf');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $cliente = clientes::where('id', $id)
        ->where('id_sucursal',  Auth::user()->id_sucursal)
        ->first();

        $ventas = Venta::where('id_cliente', $id)
        ->where('venta_pagada', false)
        ->where('id_sucursal',  Auth::user()->id_sucursal)
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

        $abonos = AbonoCuenta::where('id_cliente',  $id)
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
        AbonoCuenta::Create([
            'cantidad_abonada' => $request->abono,
            'cuenta_pagada' => true,
            'id_usuario' => Auth::user()->id,
            'id_sucursal' =>  Auth::user()->id_sucursal,
            'id_cliente' => $request->id,
            'is_active' => true
        ]);

        $cliente = Clientes::find($request->id);
        $cliente->abono_total += $request->abono;
        $cliente->balance = $cliente->adeudo_total - $cliente->abono_total;
        $cliente->save();

        if ($cliente->balance <= 0) {
            $this->limpiarCredito($cliente);
        }

        return redirect()->route('venta.show', $request->id);
    }

    public function limpiarCredito($cliente) {
        $ventas = Venta::where('id_cliente', $cliente->id)->get();

        $abonos = AbonoCuenta::where('id_cliente', $cliente->id)->get();
        foreach($abonos as $abono) {
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

    private function agregarFacturacion($venta, $cliente) {
        Factura::Create([
            'facturaCompleta' => false,
            'id_venta' => $venta->id,
            'id_cliente' => $cliente->id
        ]);
    }
}
