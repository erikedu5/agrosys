<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\clientes;
use App\Models\Empresa;
use App\Models\ProductoVenta;
use App\Models\Venta;
use App\Models\AbonoCuenta;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use PDF;  
use DateTime;
use Illuminate\Support\Facades\Redirect;


class VentaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $productos = Producto::where('nombre', 'LIKE', "%$request->q%")
        ->where('cantidad', '!=' , 0)
        ->get();

        $clientes = Clientes::get();
        
        return Inertia::render('Venta/Venta', [
            'productos' => $productos,
            'clientes' => $clientes,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
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
        ]);

        foreach ($request->producto_venta as $producto_venta) {
            ProductoVenta::create([
                'id_producto' => $producto_venta['producto']['id'],
                'id_venta' => $venta->id,
                'cantidad' => $producto_venta['cantidad'],
                'total_productos' => $producto_venta['importe'],
            ]);

            //Reduccion de stock;
            $producto = Producto::find($producto_venta['producto']['id']);
            $producto->cantidad = $producto->cantidad - $producto_venta['cantidad'];
            $producto->save();
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
            ];
        } else if ($request->tipo_venta == 'Credito') {
            $abonado = $request->abono;
            $abonoVenta = [
                'cantidad_abonada' => $request->abono,
                'cuenta_pagada' => false,
                'id_cliente' => $request->id_cliente,
                'id_usuario' => Auth::user()->id,
                'is_active' => true,
            ];
        }

        AbonoCuenta::Create($abonoVenta);

        $cliente = Clientes::find($request->id_cliente);
        $cliente->adeudo_total +=  $request->total;
        $cliente->abono_total += $abonado;
        $cliente->balance = $cliente->adeudo_total - $cliente->abono_total;
        $cliente->save();

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

    public function ticket(Venta $venta) {
        $productos = ProductoVenta::where('id_venta', $venta->id)->get();
        foreach ($productos as $producto) {
            $producto->detail = Producto::find($producto->id_producto);
        }

        $cliente = Clientes::where('id', $venta->id_cliente)->first();
        $usuario = User::where('id', $venta->id_usuario)->first();

        $empresas = Empresa::get();

        if (!empty($empresas)) {
            $empresa = $empresas[0];
        }

        $abonos = AbonoCuenta::where('id_cliente', $venta->id_cliente)
        ->where('is_active', true)
        ->get();
        
        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->set_option("enable_php", true);
        $pdf->loadView('reportes/venta', 
            compact("venta", "productos", "cliente", 'empresa', 'abonos', 'usuario'));
        $pdf->setOption('javascript-delay', 3000);

        return $pdf->stream('venta.pdf');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $cliente = clientes::where('id', $id)->first();
        $ventas = Venta::where('id_cliente', $id)
        ->where('venta_pagada', false)
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
}
