<?php

namespace App\Http\Controllers;

use App\Models\AbonoCuenta;
use App\Models\AltaInventario;
use App\Models\Producto;
use App\Models\Clientes;
use App\Models\Empresa;
use App\Models\ProductoVenta;
use App\Models\Venta;
use App\Models\CatClasificacion;
use App\Models\CatMarca;
use App\Models\Sucursales;
use App\Models\User;
use App\Services\SucursalService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use DateTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReporteController extends Controller
{

    public function index(Request $request)
    {
        $clasificaciones = CatClasificacion::get();
        $marca = CatMarca::get();
        $productos = Producto::get();

        $sucursales = [];
        $sucursalUser = Sucursales::find(SucursalService::getSucursalActiva());
        $usuario = Auth::user();
        if ($usuario->tipo === 'admin' && $sucursalUser && $sucursalUser->es_matriz) {
            $sucursales = Sucursales::where('id_empresa', $sucursalUser->id_empresa)->get();
        } elseif ($usuario->tipo === 'adminEmpresa') {
            $sucursales = $usuario->sucursalesEmpresa();
        }

        return Inertia::render('Reporte/Reporte', [
            'clasificaciones' => $clasificaciones,
            'marcas' => $marca,
            'productos' => $productos,
            'sucursales' => $sucursales,
        ]);
    }

    public function venta(Request $request)
    {
        $request->validate([
            'fechaInicio' => 'required',
            'fechaFin' => 'required'
        ]);

        $fechaInicio = new DateTime($request->fechaInicio);
        $fechaInicio->setTime(0, 0, 0);
        $fechaInicio->format('Y-m-d h:i:s a');

        $fechaFin = new DateTime($request->fechaFin);
        $fechaFin->setTime(23, 59, 59);
        $fechaFin->format('Y-m-d h:i:s a');
        $idSucursal = SucursalService::getSucursalActiva();
        $sucursalUser = Sucursales::find(SucursalService::getSucursalActiva());
        if ($request->has('id_sucursal') && Auth::user()->tipo === 'admin' && $sucursalUser->es_matriz) {
            $sucursal = Sucursales::find($request->id_sucursal);
            if ($sucursal && $sucursal->id_empresa === $sucursalUser->id_empresa) {
                $idSucursal = $request->id_sucursal;
            }
        }

        $ventas = Venta::where('created_at', '>=', $fechaInicio)
            ->where('created_at', '<=', $fechaFin)
            ->where('id_sucursal', $idSucursal)
            ->get();

        $montoContado = 0;
        $montoCredito = 0;
        foreach ($ventas as $venta) {

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

        $sucursalUser = Sucursales::where('id', $idSucursal)->first();
        $empresa = Empresa::where('id', $sucursalUser->id_empresa)->first();

        $abonos = AbonoCuenta::where('created_at', '>=', $fechaInicio)
            ->where('created_at', '<=', $fechaFin)
            ->where('id_sucursal', $idSucursal)
            ->get();

        $totalAbonadoRango = 0;
        foreach ($abonos as $abono) {
            $totalAbonadoRango += $abono->cantidad_abonada;
        }

        $fechaInicio = $fechaInicio->format('Y-m-d h:i:s a');
        $fechaFin = $fechaFin->format('Y-m-d h:i:s a');

        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->set_option("enable_php", true);
        $pdf->loadView(
            'reportes/ventas',
            compact("ventas", "empresa", "fechaInicio", "fechaFin", 'montoCredito', 'montoContado', 'totalAbonadoRango')
        );
        $pdf->setOption('javascript-delay', 3000);

        return $pdf->stream('ventas.pdf');
    }

    // Versión para impresión térmica (80mm)
    public function ventaTicket(Request $request)
    {
        $request->validate([
            'fechaInicio' => 'required',
            'fechaFin' => 'required'
        ]);

        $fechaInicio = new DateTime($request->fechaInicio);
        $fechaInicio->setTime(0, 0, 0);
        $fechaFin = new DateTime($request->fechaFin);
        $fechaFin->setTime(23, 59, 59);

        $idSucursal = SucursalService::getSucursalActiva();
        $sucursalUser = Sucursales::find(SucursalService::getSucursalActiva());
        if ($request->has('id_sucursal') && Auth::user()->tipo === 'admin' && $sucursalUser->es_matriz) {
            $sucursal = Sucursales::find($request->id_sucursal);
            if ($sucursal && $sucursal->id_empresa === $sucursalUser->id_empresa) {
                $idSucursal = $request->id_sucursal;
            }
        }

        $ventas = Venta::where('created_at', '>=', $fechaInicio)
            ->where('created_at', '<=', $fechaFin)
            ->where('id_sucursal', $idSucursal)
            ->get();

        $montoContado = 0;
        $montoCredito = 0;
        foreach ($ventas as $venta) {
            if ($venta->tipo_venta === 'Contado') {
                $montoContado += $venta->total;
            } else {
                $montoCredito += $venta->total;
            }
        }

        $sucursalUser = Sucursales::where('id', $idSucursal)->first();
        $empresa = Empresa::where('id', $sucursalUser->id_empresa)->first();

        $fechaInicioFmt = (clone $fechaInicio)->format('Y-m-d');
        $fechaFinFmt = (clone $fechaFin)->format('Y-m-d');

        $sucursalPref = optional($sucursalUser)->ticket_width_mm ?? 80;
        $ticketWidthMm = (int) $request->query('size', $sucursalPref);
        $ticketWidthMm = in_array($ticketWidthMm, [58, 80]) ? $ticketWidthMm : 80;
        $widthPoints = $ticketWidthMm * 2.83465;

        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->set_option('enable_php', true);
        $pdf->loadView('reportes/ventas_ticket', compact('ventas', 'empresa', 'fechaInicioFmt', 'fechaFinFmt', 'montoContado', 'montoCredito', 'ticketWidthMm'));
        $customPaper = [0, 0, $widthPoints, 2834.65];
        $pdf->setPaper($customPaper, 'portrait');
        $pdf->setOption('javascript-delay', 500);
        return $pdf->stream('ventas_ticket.pdf');
    }

    public function inventario(Request $request)
    {
        $id_clasificacion = $request->id_clasificacion;
        $id_marca = $request->id_marca;

        $inventario = Producto::where('id', '>', 0);

        $fechaCreacion = Date('Y-m-d h:i:s a');
        $clasificacion = null;
        $marca = null;
        $idSucursal = SucursalService::getSucursalActiva();
        $sucursalUser = Sucursales::find(SucursalService::getSucursalActiva());
        if ($request->has('id_sucursal') && Auth::user()->tipo === 'admin' && $sucursalUser->es_matriz) {
            $sucursal = Sucursales::find($request->id_sucursal);
            if ($sucursal && $sucursal->id_empresa === $sucursalUser->id_empresa) {
                $idSucursal = $request->id_sucursal;
            }
        }
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
            $altaInventario = $producto->alta = AltaInventario::where('id_producto', $producto->id)
                ->where('id_sucursal',  $idSucursal)
                ->orderBy('id', 'desc')
                ->limit(1)
                ->first();
            $producto->cantidad = $altaInventario !== null ?  $altaInventario->cantidad_nueva : "0";
            $id_usuario = optional($producto->alta)->id_usuario;
            $producto->usuario = optional(User::where('id', $id_usuario)->first());
        }
        $sucursalUser = Sucursales::where('id', $idSucursal)->first();
        $empresa = Empresa::where('id', $sucursalUser->id_empresa)->first();

        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->set_option("enable_php", true);
        $pdf->loadView(
            'reportes/inventario',
            compact("inventario", "empresa", 'clasificacion', 'marca', 'fechaCreacion')
        );
        $pdf->set_paper('letter', 'landscape');
        $pdf->set_paper('A4', 'landscape');
        $pdf->setOption('javascript-delay', 3000);

        return $pdf->stream('inventario.pdf');
    }

    // Versión para impresión térmica (80mm)
    public function inventarioTicket(Request $request)
    {
        $id_clasificacion = $request->id_clasificacion;
        $id_marca = $request->id_marca;

        $inventario = Producto::where('id', '>', 0);

        $idSucursal = SucursalService::getSucursalActiva();
        $sucursalUser = Sucursales::find(SucursalService::getSucursalActiva());
        if ($request->has('id_sucursal') && Auth::user()->tipo === 'admin' && $sucursalUser->es_matriz) {
            $sucursal = Sucursales::find($request->id_sucursal);
            if ($sucursal && $sucursal->id_empresa === $sucursalUser->id_empresa) {
                $idSucursal = $request->id_sucursal;
            }
        }
        if ($request->has('id_clasificacion')) {
            $inventario->where('id_clasificacion', $request->id_clasificacion);
        }
        if ($request->has('id_marca')) {
            $inventario->where('id_marca', $request->id_marca);
        }
        $inventario = $inventario->get();

        foreach ($inventario as $producto) {
            $producto->marca = CatMarca::find($producto->id_marca);
            $alta = AltaInventario::where('id_producto', $producto->id)
                ->where('id_sucursal',  $idSucursal)
                ->orderBy('id', 'desc')
                ->first();
            $producto->cantidad = $alta ? $alta->cantidad_nueva : 0;
        }
        $sucursalUser = Sucursales::where('id', $idSucursal)->first();
        $empresa = Empresa::where('id', $sucursalUser->id_empresa)->first();

        $sucursalPref = optional($sucursalUser)->ticket_width_mm ?? 80;
        $ticketWidthMm = (int) $request->query('size', $sucursalPref);
        $ticketWidthMm = in_array($ticketWidthMm, [58, 80]) ? $ticketWidthMm : 80;
        $widthPoints = $ticketWidthMm * 2.83465;

        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->set_option('enable_php', true);
        $pdf->loadView('reportes/inventario_ticket', compact('inventario', 'empresa', 'ticketWidthMm'));
        $customPaper = [0, 0, $widthPoints, 2834.65];
        $pdf->setPaper($customPaper, 'portrait');
        $pdf->setOption('javascript-delay', 500);
        return $pdf->stream('inventario_ticket.pdf');
    }

    public function gananciasDiarias(Request $request)
    {
        $usuario = Auth::user();
        Log::debug($usuario);
        if ($usuario->tipo !== 'adminEmpresa' && $usuario->tipo !== 'superAdmin') {
            abort(403);
        }

        $request->validate(
            [
                'fechaInicio' => ['required'],
                'fechaFin' => ['required'],
            ],
            [
                'fechaInicio.required' => 'La fecha de inicio es requerida.',
                'fechaFin.required' => 'La fecha de fin es requerida.',
            ]
        );

        $empresa = $usuario->empresa;
        if (!$empresa) {
            abort(404, 'No se encontró la empresa asociada al usuario.');
        }

        $fechaInicio = new DateTime($request->fechaInicio);
        $fechaFin = new DateTime($request->fechaFin);
        $fechaInicioTexto = $fechaInicio->format('d/m/Y');
        $fechaFinTexto = $fechaFin->format('d/m/Y');

        $fechaInicio->setTime(0, 0, 0);
        $fechaFin->setTime(23, 59, 59);

        $fechaInicioFiltro = $fechaInicio->format('Y-m-d H:i:s');
        $fechaFinFiltro = $fechaFin->format('Y-m-d H:i:s');

        $sucursalesEmpresa = Sucursales::where('id_empresa', $empresa->id)->get();
        $sucursalIds = $sucursalesEmpresa->pluck('id');

        $sucursalSeleccionada = null;
        if ($request->filled('id_sucursal')) {
            $sucursalSeleccionada = $sucursalesEmpresa->firstWhere('id', (int) $request->id_sucursal);
            if ($sucursalSeleccionada) {
                $sucursalIds = collect([$sucursalSeleccionada->id]);
            }
        }

        $gananciasPorDia = collect();
        $detallesGanancia = collect();
        if ($sucursalIds->isNotEmpty()) {
            $gananciasPorDia = ProductoVenta::selectRaw(
                'DATE(ventas.created_at) as fecha,
                 SUM((COALESCE(productos.precio_ieps, 0) - COALESCE(productos.precio_unitario, 0)) * producto_ventas.cantidad) AS ganancia_total,
                 SUM(producto_ventas.cantidad) as unidades_vendidas'
            )
                ->join('ventas', 'producto_ventas.id_venta', '=', 'ventas.id')
                ->join('productos', 'producto_ventas.id_producto', '=', 'productos.id')
                ->whereBetween('ventas.created_at', [$fechaInicioFiltro, $fechaFinFiltro])
                ->whereIn('ventas.id_sucursal', $sucursalIds->all())
                ->groupBy(DB::raw('DATE(ventas.created_at)'))
                ->orderBy('fecha')
                ->get();

            $detallesGanancia = ProductoVenta::selectRaw(
                'DATE(ventas.created_at) as fecha,
                 ventas.id as id_venta,
                 sucursales.nombre as sucursal_nombre,
                 COALESCE(clientes.nombre, \'Cliente público\') as cliente_nombre,
                 productos.nombre as producto_nombre,
                 producto_ventas.cantidad,
                 (COALESCE(productos.precio_ieps, 0) - COALESCE(productos.precio_unitario, 0)) * producto_ventas.cantidad as ganancia_linea'
            )
                ->join('ventas', 'producto_ventas.id_venta', '=', 'ventas.id')
                ->join('productos', 'producto_ventas.id_producto', '=', 'productos.id')
                ->join('sucursales', 'ventas.id_sucursal', '=', 'sucursales.id')
                ->leftJoin('clientes', 'ventas.id_cliente', '=', 'clientes.id')
                ->whereBetween('ventas.created_at', [$fechaInicioFiltro, $fechaFinFiltro])
                ->whereIn('ventas.id_sucursal', $sucursalIds->all())
                ->orderBy('fecha')
                ->orderBy('sucursal_nombre')
                ->orderBy('cliente_nombre')
                ->orderBy('producto_nombre')
                ->get();
        }

        $gananciaTotal = (float) $gananciasPorDia->sum('ganancia_total');
        $totalUnidades = (int) $gananciasPorDia->sum('unidades_vendidas');

        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->set_option("enable_php", true);
        $pdf->loadView(
            'reportes/ganancias_diarias',
            [
                'empresa' => $empresa,
                'fechaInicio' => $fechaInicioTexto,
                'fechaFin' => $fechaFinTexto,
                'gananciasPorDia' => $gananciasPorDia,
                'gananciaTotal' => $gananciaTotal,
                'totalUnidades' => $totalUnidades,
                'sucursal' => $sucursalSeleccionada,
                'incluyeTodasSucursales' => $sucursalSeleccionada === null,
                'detallesGanancia' => $detallesGanancia,
            ]
        );
        $pdf->setOption('javascript-delay', 3000);

        return $pdf->stream('ganancias_diarias.pdf');
    }

    public function clientesAdeudo(Request $request)
    {
        $usuario = Auth::user();
        $sucursalActiva = Sucursales::find(SucursalService::getSucursalActiva());
        $sucursales = collect();
        $sucursalSeleccionada = null;
        $sucursalSolicitud = $request->filled('id_sucursal') ? (int) $request->id_sucursal : null;

        if ($usuario->tipo === 'admin' && $sucursalActiva && $sucursalActiva->es_matriz) {
            if ($sucursalSolicitud) {
                $sucursal = Sucursales::where('id', $sucursalSolicitud)
                    ->where('id_empresa', $sucursalActiva->id_empresa)
                    ->first();
                if (!$sucursal) {
                    abort(403, 'No tiene permisos para consultar la sucursal solicitada.');
                }
                $sucursales = collect([$sucursal]);
                $sucursalSeleccionada = $sucursal;
            } else {
                $sucursales = Sucursales::where('id_empresa', $sucursalActiva->id_empresa)->get();
            }
        } elseif ($usuario->tipo === 'adminEmpresa') {
            $sucursalesEmpresa = $usuario->sucursalesEmpresa();
            if ($sucursalSolicitud) {
                $sucursal = $sucursalesEmpresa->firstWhere('id', $sucursalSolicitud);
                if (!$sucursal) {
                    abort(403, 'No tiene permisos para consultar la sucursal solicitada.');
                }
                $sucursales = collect([$sucursal]);
                $sucursalSeleccionada = $sucursal;
            } else {
                $sucursales = $sucursalesEmpresa;
            }
        } elseif ($usuario->tipo === 'superAdmin') {
            if ($sucursalSolicitud) {
                $sucursal = Sucursales::find($sucursalSolicitud);
                if (!$sucursal) {
                    abort(404, 'La sucursal seleccionada no existe.');
                }
                $sucursales = collect([$sucursal]);
                $sucursalSeleccionada = $sucursal;
            } else {
                $sucursales = Sucursales::all();
            }
        } else {
            if (!$sucursalActiva) {
                abort(404, 'No se encontró la sucursal activa para el usuario.');
            }
            $sucursales = collect([$sucursalActiva]);
            $sucursalSeleccionada = $sucursalActiva;
        }

        if ($sucursales->isEmpty()) {
            abort(404, 'No se encontraron sucursales para generar el reporte.');
        }

        $datos = $sucursales->map(function (Sucursales $sucursal) {
            $clientes = Clientes::where('id_sucursal', $sucursal->id)
                ->where('balance', '>', 0)
                ->where('activo', true)
                ->orderBy('nombre')
                ->get();

            return [
                'sucursal' => $sucursal,
                'clientes' => $clientes,
                'totalAdeudo' => (float) $clientes->sum('balance'),
                'totalAbonos' => (float) $clientes->sum('abono_total'),
                'totalAdeudoOriginal' => (float) $clientes->sum('adeudo_total'),
                'totalClientes' => $clientes->count(),
            ];
        })->values();

        $hayClientesConAdeudo = $datos->contains(function ($item) {
            return $item['clientes']->isNotEmpty();
        });

        $totalGeneralAdeudo = (float) $datos->sum('totalAdeudo');
        $totalGeneralAdeudoOriginal = (float) $datos->sum('totalAdeudoOriginal');
        $totalGeneralAbonos = (float) $datos->sum('totalAbonos');
        $totalGeneralClientes = (int) $datos->sum('totalClientes');

        $empresa = null;
        $empresasIds = $datos->pluck('sucursal.id_empresa')->filter()->unique();
        if ($empresasIds->count() === 1) {
            $empresa = Empresa::find($empresasIds->first());
        }

        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->set_option('enable_php', true);
        $pdf->loadView(
            'reportes/clientes_adeudo',
            [
                'empresa' => $empresa,
                'datos' => $datos,
                'totalGeneralAdeudo' => $totalGeneralAdeudo,
                'totalGeneralAdeudoOriginal' => $totalGeneralAdeudoOriginal,
                'totalGeneralAbonos' => $totalGeneralAbonos,
                'totalGeneralClientes' => $totalGeneralClientes,
                'generadoEn' => now()->format('d/m/Y H:i:s'),
                'usuario' => $usuario,
                'sucursalSeleccionada' => $sucursalSeleccionada,
                'hayClientesConAdeudo' => $hayClientesConAdeudo,
            ]
        );
        $pdf->setOption('javascript-delay', 3000);

        return $pdf->stream('clientes_adeudo.pdf');
    }

    public function ventaPorProductoMarca(Request $request)
    {
        $request->validate([
            'fechaInicio' => ['required'],
            'fechaFin' => ['required']
        ]);

        $fechaInicio = new DateTime($request->fechaInicio);
        $fechaInicio->setTime(0, 0, 0);
        $fechaInicio->format('Y-m-d h:i:s a');

        $fechaFin = new DateTime($request->fechaFin);
        $fechaFin->setTime(23, 59, 59);
        $fechaFin->format('Y-m-d h:i:s a');

        $ventas = [];
        $porProducto = $request->id_producto != 0;
        $porMarca = $request->id_marca != 0;
        $nameFilter = "";
        $idSucursal = SucursalService::getSucursalActiva();
        $sucursalUser = Sucursales::find(SucursalService::getSucursalActiva());
        if ($request->has('id_sucursal') && Auth::user()->tipo === 'admin' && $sucursalUser->es_matriz) {
            $sucursal = Sucursales::find($request->id_sucursal);
            if ($sucursal && $sucursal->id_empresa === $sucursalUser->id_empresa) {
                $idSucursal = $request->id_sucursal;
            }
        }

        if ($porProducto) {
            $ventas = Venta::select('ventas.*')
                ->where('producto_ventas.id_producto', $request->id_producto)
                ->where('ventas.created_at', '>=', $fechaInicio)
                ->where('ventas.created_at', '<=', $fechaFin)
                ->where('ventas.id_sucursal', $idSucursal)
                ->join('producto_ventas', 'producto_ventas.id_venta', 'ventas.id')
                ->get();

            $nameFilter = Producto::select('nombre')
                ->where('id', $request->id_producto)
                ->first()->nombre;
        }

        if ($porMarca) {
            $ventas = Venta::select('ventas.*')
                ->where('productos.id_marca', $request->id_marca)
                ->where('ventas.created_at', '>=', $fechaInicio)
                ->where('ventas.created_at', '<=', $fechaFin)
                ->where('ventas.id_sucursal', $idSucursal)
                ->join('producto_ventas', 'producto_ventas.id_venta', 'ventas.id')
                ->join('productos', 'productos.id', 'producto_ventas.id_producto')
                ->get();

            $nameFilter = CatMarca::select('nombre')
                ->where('id', $request->id_marca)
                ->first()->nombre;
        }

        $productosVendidos = 0;

        foreach ($ventas as $venta) {
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

        $sucursalUser = Sucursales::where('id',  $idSucursal)->first();
        $empresa = Empresa::where('id', $sucursalUser->id_empresa)->first();

        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->set_option("enable_php", true);
        $pdf->loadView(
            'reportes/ventasMarcaProducto',
            compact("ventas", "empresa", "productosVendidos", "nameFilter")
        );
        $pdf->setOption('javascript-delay', 3000);

        return $pdf->stream('ventas.pdf');
    }

    // Versión para impresión térmica (80mm)
    public function ventaPorProductoMarcaTicket(Request $request)
    {
        $request->validate([
            'fechaInicio' => ['required'],
            'fechaFin' => ['required']
        ]);

        $fechaInicio = new DateTime($request->fechaInicio);
        $fechaInicio->setTime(0, 0, 0);
        $fechaFin = new DateTime($request->fechaFin);
        $fechaFin->setTime(23, 59, 59);

        $ventas = [];
        $porProducto = $request->id_producto != 0;
        $porMarca = $request->id_marca != 0;
        $nameFilter = "";
        $idSucursal = SucursalService::getSucursalActiva();
        $sucursalUser = Sucursales::find(SucursalService::getSucursalActiva());
        if ($request->has('id_sucursal') && Auth::user()->tipo === 'admin' && $sucursalUser->es_matriz) {
            $sucursal = Sucursales::find($request->id_sucursal);
            if ($sucursal && $sucursal->id_empresa === $sucursalUser->id_empresa) {
                $idSucursal = $request->id_sucursal;
            }
        }

        if ($porProducto) {
            $ventas = Venta::select('ventas.*')
                ->where('producto_ventas.id_producto', $request->id_producto)
                ->where('ventas.created_at', '>=', $fechaInicio)
                ->where('ventas.created_at', '<=', $fechaFin)
                ->where('ventas.id_sucursal', $idSucursal)
                ->join('producto_ventas', 'producto_ventas.id_venta', 'ventas.id')
                ->get();
            $nameFilter = Producto::select('nombre')->where('id', $request->id_producto)->first()->nombre;
        }

        if ($porMarca) {
            $ventas = Venta::select('ventas.*')
                ->where('productos.id_marca', $request->id_marca)
                ->where('ventas.created_at', '>=', $fechaInicio)
                ->where('ventas.created_at', '<=', $fechaFin)
                ->where('ventas.id_sucursal', $idSucursal)
                ->join('producto_ventas', 'producto_ventas.id_venta', 'ventas.id')
                ->join('productos', 'productos.id', 'producto_ventas.id_producto')
                ->get();
            $nameFilter = CatMarca::select('nombre')->where('id', $request->id_marca)->first()->nombre;
        }

        $productosVendidos = 0;
        foreach ($ventas as $venta) {
            $productos = ProductoVenta::where('id_venta', $venta->id)->get();
            foreach ($productos as $producto) {
                $producto->detail = Producto::find($producto->id_producto);
                if ($porProducto && $producto->id_producto == $request->id_producto) {
                    $productosVendidos += $producto->cantidad;
                } else if ($porMarca && $producto->detail->id_marca == $request->id_marca) {
                    $productosVendidos += $producto->cantidad;
                }
            }
        }

        $sucursalUser = Sucursales::where('id', $idSucursal)->first();
        $empresa = Empresa::where('id', $sucursalUser->id_empresa)->first();

        $sucursalPref = optional($sucursalUser)->ticket_width_mm ?? 80;
        $ticketWidthMm = (int) $request->query('size', $sucursalPref);
        $ticketWidthMm = in_array($ticketWidthMm, [58, 80]) ? $ticketWidthMm : 80;
        $widthPoints = $ticketWidthMm * 2.83465;

        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->set_option('enable_php', true);
        $pdf->loadView('reportes/ventasMarcaProducto_ticket', compact('ventas', 'empresa', 'productosVendidos', 'nameFilter', 'ticketWidthMm'));
        $customPaper = [0, 0, $widthPoints, 2834.65];
        $pdf->setPaper($customPaper, 'portrait');
        $pdf->setOption('javascript-delay', 500);
        return $pdf->stream('ventas_marca_producto_ticket.pdf');
    }
}
