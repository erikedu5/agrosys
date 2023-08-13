<?php

namespace App\Http\Controllers;

use App\Models\AbonoCuenta;
use App\Models\AltaInventario;
use App\Models\Producto;
use App\Models\clientes;
use App\Models\Empresa;
use App\Models\ProductoVenta;
use App\Models\Venta;
use App\Models\CatClasificacion;
use App\Models\CatMarca;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use DateTime;

class ReporteController extends Controller
{

    public function index(Request $request)
    {
        $clasificaciones = CatClasificacion::get();
        $marca = CatMarca::get();
        $productos = Producto::get();


        return Inertia::render('Reporte/Reporte', [
            'clasificaciones' => $clasificaciones,
            'marcas' => $marca,
            'productos' =>$productos,
        ]);
    }

    public function venta(Request $request)
    {
        $fechaInicio = new DateTime($request->fechaInicio);
        $fechaInicio->setTime(0,0,0);
        $fechaInicio->format('Y-m-d h:i:s a');

        $fechaFin = new DateTime($request->fechaFin);
        $fechaFin->setTime(23,59,59);
        $fechaFin->format('Y-m-d h:i:s a');

        $ventas = Venta::where('created_at', '>=', $fechaInicio)
        ->where('created_at', '<=', $fechaFin)
        ->get();

        $montoContado = 0;
        $montoCredito = 0;
        foreach($ventas as $venta) {

            if ($venta->tipo_venta === "Contado") {
                $montoContado = $montoContado + $venta->total;
            } else {
                $montoCredito = $montoCredito + $venta->total;
            }

            $productos = ProductoVenta::where('id_venta', $venta->id)->get();
            $productosArray = [];
            foreach ($productos as $producto) {
                $producto->detail = Producto::find($producto->id_producto);
                array_push($productosArray, $producto);
            }
            $venta->productos = $productosArray;

            $cliente = clientes::where('id', $venta->id_cliente)->first();
            $venta->cliente = $cliente;

            $usuario = User::where('id', $venta->id_usuario)->first();
            $venta->usuario = $usuario;
        }

        $empresas = Empresa::get();

        if (!empty($empresas)) {
            $empresa = $empresas[0];
        }

        $abonos = AbonoCuenta::where('created_at', '>=', $fechaInicio)
        ->where('created_at', '<=', $fechaFin)
        ->get();

        $totalAbonadoRango = 0;
        foreach ($abonos as $abono) {
            $totalAbonadoRango += $abono->cantidad_abonada;
        }

        $fechaInicio = $fechaInicio->format('Y-m-d h:i:s a');
        $fechaFin = $fechaFin->format('Y-m-d h:i:s a');

        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->set_option("enable_php", true);
        $pdf->loadView('reportes/ventas',
            compact("ventas", "empresa", "fechaInicio", "fechaFin", 'montoCredito', 'montoContado', 'totalAbonadoRango'));
        $pdf->setOption('javascript-delay', 3000);

        return $pdf->stream('ventas.pdf');
    }

    public function inventario(Request $request)
    {
        $id_clasificacion = $request->id_clasificacion;
        $id_marca = $request->id_marca;

        $inventario = Producto::where('id', '>', 0);

        $fechaCreacion = Date('Y-m-d h:i:s a');
        $clasificacion = null;
        $marca = null;
        if ($request->has('id_clasificacion')) {
            $inventario->where('id_clasificacion', $request->id_clasificacion);
            $clasificacion = CatClasificacion::find($request->id_clasificacion);
        }
        if ($request->has('id_marca')) {
            $inventario->where('id_marca', $request->id_marca);
            $marca = CatMarca::find($request->id_marca);
        }
        $inventario = $inventario->get();

        foreach ($inventario as $producto) {
            $producto->marca = CatMarca::find($producto->id_marca);
            $producto->alta = AltaInventario::where('id_producto', $producto->id)
            ->orderBy('id', 'desc')
            ->limit(1)
            ->first();
            $id_usuario = optional($producto->alta)->id_usuario;
            $producto->usuario = optional(User::where('id', $id_usuario)->first());

        }
        $empresas = Empresa::get();

        if (!empty($empresas)) {
            $empresa = $empresas[0];
        }

        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->set_option("enable_php", true);
        $pdf->loadView('reportes/inventario',
            compact("inventario", "empresa", 'clasificacion', 'marca', 'fechaCreacion'));
        $pdf->set_paper('letter', 'landscape');
        $pdf->set_paper('A4', 'landscape');
        $pdf->setOption('javascript-delay', 3000);

        return $pdf->stream('inventario.pdf');
    }

    public function ventaPorProductoMarca(Request $request)
    {

        $fechaInicio = new DateTime($request->fechaInicio);
        $fechaInicio->setTime(0,0,0);
        $fechaInicio->format('Y-m-d h:i:s a');

        $fechaFin = new DateTime($request->fechaFin);
        $fechaFin->setTime(23,59,59);
        $fechaFin->format('Y-m-d h:i:s a');

        $ventas = [];
        $porProducto = $request->id_producto != 0;
        $porMarca = $request->id_marca != 0;
        $nameFilter = "";

        if($porProducto) {
            $ventas = Venta::select('ventas.*')
            ->where('producto_ventas.id_producto', $request->id_producto)
            ->where('ventas.created_at', '>=', $fechaInicio)
            ->where('ventas.created_at', '<=', $fechaFin)
            ->join('producto_ventas', 'producto_ventas.id_venta', 'ventas.id')
            ->get();

            $nameFilter = Producto::select('nombre')
            ->where('id', $request->id_producto)
            ->first()->nombre;
        }

        if ($porMarca){
            $ventas = Venta::select('ventas.*')
            ->where('productos.id_marca', $request->id_marca)
            ->where('ventas.created_at', '>=', $fechaInicio)
            ->where('ventas.created_at', '<=', $fechaFin)
            ->join('producto_ventas', 'producto_ventas.id_venta', 'ventas.id')
            ->join('productos', 'productos.id', 'producto_ventas.id_producto')
            ->get();

            $nameFilter = CatMarca::select('nombre')
            ->where('id', $request->id_marca)
            ->first()->nombre;
        }

        $productosVendidos = 0;

        foreach($ventas as $venta) {
            $productos = ProductoVenta::where('id_venta', $venta->id)->get();
            $productosArray = [];
            foreach ($productos as $producto) {
                $producto->detail = Producto::find($producto->id_producto);
                if ($porProducto && $producto->id_producto == $request->id_producto) {
                    $productosVendidos += $producto->cantidad;
                } else if ($porMarca && $producto->detail->id_marca == $request->id_marca) {
                    $productosVendidos += $producto->cantidad;
                }
                $producto->detail->marca = CatMarca::find($producto->detail->id_marca);
                array_push($productosArray, $producto);
            }
            $venta->productos = $productosArray;

            $cliente = clientes::where('id', $venta->id_cliente)->first();
            $venta->cliente = $cliente;

            $usuario = User::where('id', $venta->id_usuario)->first();
            $venta->usuario = $usuario;
        }

        $empresas = Empresa::get();

        if (!empty($empresas)) {
            $empresa = $empresas[0];
        }

        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->set_option("enable_php", true);
        $pdf->loadView('reportes/ventasMarcaProducto',
            compact("ventas", "empresa", "productosVendidos", "nameFilter"));
        $pdf->setOption('javascript-delay', 3000);

        return $pdf->stream('ventas.pdf');
    }
}
