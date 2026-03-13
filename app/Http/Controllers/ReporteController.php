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
    private const MAX_PDF_LINEAS_VENTA = 2000;

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
        } elseif ($usuario->tipo === 'superAdmin') {
            $sucursales = Sucursales::all();
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
            'fechaInicio' => ['required', 'date_format:Y-m-d'],
            'fechaFin' => ['required', 'date_format:Y-m-d'],
        ]);

        $fechaInicio = new DateTime($request->fechaInicio);
        $fechaInicio->setTime(0, 0, 0);
        $fechaInicio->format('Y-m-d h:i:s a');

        $fechaFin = new DateTime($request->fechaFin);
        $fechaFin->setTime(23, 59, 59);
        $fechaFin->format('Y-m-d h:i:s a');
        $idSucursal = $this->resolveSucursalIdForReport($request);

        $lineasCount = ProductoVenta::join('ventas', 'producto_ventas.id_venta', '=', 'ventas.id')
            ->whereBetween('ventas.created_at', [$fechaInicio, $fechaFin])
            ->where('ventas.id_sucursal', $idSucursal)
            ->count();

        if ($lineasCount > self::MAX_PDF_LINEAS_VENTA) {
            $ventasBase = Venta::whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->where('id_sucursal', $idSucursal);
            $montoContado = (clone $ventasBase)->where('tipo_venta', 'Contado')->sum('total');
            $montoCredito = (clone $ventasBase)->where('tipo_venta', 'Credito')->sum('total');
            $sucursalUser = Sucursales::where('id', $idSucursal)->first();

            return $this->streamVentasCsv($fechaInicio, $fechaFin, $idSucursal, (float) $montoContado, (float) $montoCredito, $sucursalUser);
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
                $detalle = Producto::withTrashed()->find($producto->id_producto);
                if ($detalle) {
                    if ($detalle->trashed()) {
                        $detalle->nombre = $detalle->nombre . ' (BORRADO)';
                    }
                } else {
                    $detalle = (object) [
                        'id' => $producto->id_producto,
                        'nombre' => 'Producto (BORRADO)',
                    ];
                }
                $producto->detail = $detalle;
                [$stockAnterior, $stockNuevo] = $this->resolveStockMovimientoForProductoVenta($producto, $venta, $idSucursal);
                $producto->stock_anterior = $stockAnterior;
                $producto->stock_nuevo = $stockNuevo;
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
            compact("ventas", "empresa", "fechaInicio", "fechaFin", 'montoCredito', 'montoContado', 'totalAbonadoRango', 'sucursalUser')
        );
        $pdf->setOption('javascript-delay', 3000);

        return $pdf->stream('ventas.pdf');
    }

    // Versión para impresión térmica (80mm)
    public function ventaTicket(Request $request)
    {
        $request->validate([
            'fechaInicio' => ['required', 'date_format:Y-m-d'],
            'fechaFin' => ['required', 'date_format:Y-m-d'],
        ]);

        $fechaInicio = new DateTime($request->fechaInicio);
        $fechaInicio->setTime(0, 0, 0);
        $fechaFin = new DateTime($request->fechaFin);
        $fechaFin->setTime(23, 59, 59);

        $idSucursal = $this->resolveSucursalIdForReport($request);

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
        $idSucursal = $this->resolveSucursalIdForReport($request);
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

        $idSucursal = $this->resolveSucursalIdForReport($request);
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
                'fechaInicio' => ['required', 'date_format:Y-m-d'],
                'fechaFin' => ['required', 'date_format:Y-m-d'],
            ],
            [
                'fechaInicio.required' => 'La fecha de inicio es requerida.',
                'fechaFin.required' => 'La fecha de fin es requerida.',
                'fechaInicio.date_format' => 'La fecha de inicio debe tener el formato YYYY-MM-DD.',
                'fechaFin.date_format' => 'La fecha de fin debe tener el formato YYYY-MM-DD.',
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
                ->whereNull('productos.deleted_at')
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
                ->whereNull('productos.deleted_at')
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

        if ($usuario->tipo === 'admin' && $sucursalActiva && $sucursalActiva->es_matriz) {
            $sucursales = Sucursales::where('id_empresa', $sucursalActiva->id_empresa)->get();
        } elseif ($usuario->tipo === 'adminEmpresa') {
            $sucursales = $usuario->sucursalesEmpresa();
        } elseif ($usuario->tipo === 'superAdmin') {
            $sucursales = Sucursales::all();
        } else {
            if (!$sucursalActiva) {
                abort(404, 'No se encontró la sucursal activa para el usuario.');
            }
            $sucursales = collect([$sucursalActiva]);
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
            'fechaInicio' => ['required', 'date_format:Y-m-d'],
            'fechaFin' => ['required', 'date_format:Y-m-d'],
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
        $idSucursal = $this->resolveSucursalIdForReport($request);

        if ($porProducto) {
            $ventas = Venta::select('ventas.*')
                ->where('producto_ventas.id_producto', $request->id_producto)
                ->where('ventas.created_at', '>=', $fechaInicio)
                ->where('ventas.created_at', '<=', $fechaFin)
                ->where('ventas.id_sucursal', $idSucursal)
                ->join('producto_ventas', 'producto_ventas.id_venta', 'ventas.id')
                ->join('productos', 'productos.id', 'producto_ventas.id_producto')
                ->whereNull('productos.deleted_at')
                ->get();

            $productoFiltro = Producto::withTrashed()->find($request->id_producto);
            if ($productoFiltro) {
                $nameFilter = $productoFiltro->nombre . ($productoFiltro->trashed() ? ' (BORRADO)' : '');
            } else {
                $nameFilter = 'Producto (BORRADO)';
            }
        }

        if ($porMarca) {
            $ventas = Venta::select('ventas.*')
                ->where('productos.id_marca', $request->id_marca)
                ->where('ventas.created_at', '>=', $fechaInicio)
                ->where('ventas.created_at', '<=', $fechaFin)
                ->where('ventas.id_sucursal', $idSucursal)
                ->join('producto_ventas', 'producto_ventas.id_venta', 'ventas.id')
                ->join('productos', 'productos.id', 'producto_ventas.id_producto')
                ->whereNull('productos.deleted_at')
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
                $detalle = Producto::withTrashed()->find($producto->id_producto);
                if ($detalle) {
                    if ($detalle->trashed()) {
                        $detalle->nombre = $detalle->nombre . ' (BORRADO)';
                    }
                    $detalle->marca = CatMarca::find($detalle->id_marca);
                } else {
                    $detalle = (object) [
                        'id' => $producto->id_producto,
                        'nombre' => 'Producto (BORRADO)',
                        'id_marca' => null,
                        'marca' => (object) ['nombre' => ''],
                    ];
                }
                $producto->detail = $detalle;
                if ($porProducto && $producto->id_producto == $request->id_producto) {
                    $productosVendidos += $producto->cantidad;
                } else if ($porMarca && $producto->detail && $producto->detail->id_marca == $request->id_marca) {
                    $productosVendidos += $producto->cantidad;
                }
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
            'fechaInicio' => ['required', 'date_format:Y-m-d'],
            'fechaFin' => ['required', 'date_format:Y-m-d'],
        ]);

        $fechaInicio = new DateTime($request->fechaInicio);
        $fechaInicio->setTime(0, 0, 0);
        $fechaFin = new DateTime($request->fechaFin);
        $fechaFin->setTime(23, 59, 59);

        $ventas = [];
        $porProducto = $request->id_producto != 0;
        $porMarca = $request->id_marca != 0;
        $nameFilter = "";
        $idSucursal = $this->resolveSucursalIdForReport($request);

        if ($porProducto) {
            $ventas = Venta::select('ventas.*')
                ->where('producto_ventas.id_producto', $request->id_producto)
                ->where('ventas.created_at', '>=', $fechaInicio)
                ->where('ventas.created_at', '<=', $fechaFin)
                ->where('ventas.id_sucursal', $idSucursal)
                ->join('producto_ventas', 'producto_ventas.id_venta', 'ventas.id')
                ->join('productos', 'productos.id', 'producto_ventas.id_producto')
                ->whereNull('productos.deleted_at')
                ->get();
            $productoFiltro = Producto::withTrashed()->find($request->id_producto);
            if ($productoFiltro) {
                $nameFilter = $productoFiltro->nombre . ($productoFiltro->trashed() ? ' (BORRADO)' : '');
            } else {
                $nameFilter = 'Producto (BORRADO)';
            }
        }

        if ($porMarca) {
            $ventas = Venta::select('ventas.*')
                ->where('productos.id_marca', $request->id_marca)
                ->where('ventas.created_at', '>=', $fechaInicio)
                ->where('ventas.created_at', '<=', $fechaFin)
                ->where('ventas.id_sucursal', $idSucursal)
                ->join('producto_ventas', 'producto_ventas.id_venta', 'ventas.id')
                ->join('productos', 'productos.id', 'producto_ventas.id_producto')
                ->whereNull('productos.deleted_at')
                ->get();
            $nameFilter = CatMarca::select('nombre')->where('id', $request->id_marca)->first()->nombre;
        }

        $productosVendidos = 0;
        foreach ($ventas as $venta) {
            $productos = ProductoVenta::where('id_venta', $venta->id)->get();
            foreach ($productos as $producto) {
                $detalle = Producto::withTrashed()->find($producto->id_producto);
                if ($detalle) {
                    if ($detalle->trashed()) {
                        $detalle->nombre = $detalle->nombre . ' (BORRADO)';
                    }
                } else {
                    $detalle = (object) [
                        'id' => $producto->id_producto,
                        'nombre' => 'Producto (BORRADO)',
                        'id_marca' => null,
                    ];
                }
                $producto->detail = $detalle;
                if ($porProducto && $producto->id_producto == $request->id_producto) {
                    $productosVendidos += $producto->cantidad;
                } else if ($porMarca && $producto->detail && $producto->detail->id_marca == $request->id_marca) {
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

    public function ventasDia(Request $request)
    {
        $idSucursal = $this->resolveSucursalIdForReport($request);
        $fechaInicio = now()->startOfDay();
        $fechaFin = now()->endOfDay();

        $ventas = Venta::whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->where('id_sucursal', $idSucursal)
            ->orderBy('id')
            ->get();

        $ventaIds = $ventas->pluck('id');
        $lineas = $ventaIds->isEmpty()
            ? collect()
            : ProductoVenta::whereIn('id_venta', $ventaIds)->get();

        $productos = $lineas->isEmpty()
            ? collect()
            : Producto::withTrashed()
                ->whereIn('id', $lineas->pluck('id_producto')->unique())
                ->get()
                ->keyBy('id');

        $clientes = $ventas->isEmpty()
            ? collect()
            : Clientes::whereIn('id', $ventas->pluck('id_cliente')->filter()->unique())
                ->get()
                ->keyBy('id');

        $usuarios = $ventas->isEmpty()
            ? collect()
            : User::whereIn('id', $ventas->pluck('id_usuario')->filter()->unique())
                ->get()
                ->keyBy('id');

        $lineasPorVenta = $lineas->groupBy('id_venta');
        $rows = [];

        foreach ($ventas as $venta) {
            $clienteNombre = optional($clientes->get($venta->id_cliente))->nombre ?? 'Cliente público';
            $usuarioNombre = optional($usuarios->get($venta->id_usuario))->name ?? 'N/D';
            $ventaFecha = $venta->created_at ? \Carbon\Carbon::parse($venta->created_at)->format('Y-m-d H:i:s') : null;

            foreach ($lineasPorVenta->get($venta->id, collect()) as $linea) {
                $productoDetalle = $productos->get($linea->id_producto);
                if ($productoDetalle) {
                    $productoNombre = $productoDetalle->nombre;
                    if ($productoDetalle->trashed()) {
                        $productoNombre .= ' (BORRADO)';
                    }
                } else {
                    $productoNombre = 'Producto (BORRADO)';
                }

                $precioUnitario = $linea->cantidad ? ($linea->total_productos / $linea->cantidad) : 0;
                [$stockAnterior, $stockNuevo] = $this->resolveStockMovimientoForProductoVenta($linea, $venta, $idSucursal);

                $rows[] = [
                    'venta_id' => $venta->id,
                    'fecha_venta' => $ventaFecha,
                    'tipo_venta' => $venta->tipo_venta,
                    'producto' => $productoNombre,
                    'cantidad' => (float) $linea->cantidad,
                    'precio_unitario' => (float) $precioUnitario,
                    'total' => (float) $linea->total_productos,
                    'cliente' => $clienteNombre,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $stockNuevo,
                    'usuario' => $usuarioNombre,
                ];
            }
        }

        $sucursal = Sucursales::find($idSucursal);
        $totalVentas = (float) $ventas->sum('total');

        return response()->json([
            'sucursal' => $sucursal ? ['id' => $sucursal->id, 'nombre' => $sucursal->nombre] : null,
            'fecha' => now()->format('Y-m-d'),
            'total' => $totalVentas,
            'rows' => $rows,
        ]);
    }

    private function resolveSucursalIdForReport(Request $request): int
    {
        $idSucursal = SucursalService::getSucursalActiva();
        $usuario = Auth::user();
        $sucursalActiva = Sucursales::find($idSucursal);

        if (!$request->filled('id_sucursal')) {
            return $idSucursal;
        }

        $idSolicitada = (int) $request->id_sucursal;

        if ($usuario->tipo === 'admin' && $sucursalActiva && $sucursalActiva->es_matriz) {
            $sucursal = Sucursales::where('id', $idSolicitada)
                ->where('id_empresa', $sucursalActiva->id_empresa)
                ->first();
            if ($sucursal) {
                return $sucursal->id;
            }
        } elseif ($usuario->tipo === 'adminEmpresa') {
            $sucursal = $usuario->sucursalesEmpresa()->firstWhere('id', $idSolicitada);
            if ($sucursal) {
                return $sucursal->id;
            }
        } elseif ($usuario->tipo === 'superAdmin') {
            $sucursal = Sucursales::find($idSolicitada);
            if ($sucursal) {
                return $sucursal->id;
            }
        }

        return $idSucursal;
    }

    private function resolveStockMovimientoForProductoVenta(ProductoVenta $producto, Venta $venta, int $idSucursal): array
    {
        $createdAt = $producto->created_at ?? $venta->created_at;
        if (!$createdAt) {
            return [null, null];
        }

        $createdAt = $createdAt instanceof \Carbon\Carbon
            ? $createdAt
            : \Carbon\Carbon::parse($createdAt);

        $windowEnd = $createdAt->copy()->addMinutes(5);
        $movimientos = AltaInventario::where('id_producto', $producto->id_producto)
            ->where('id_sucursal', $idSucursal)
            ->where('id_usuario', $venta->id_usuario)
            ->whereBetween('created_at', [$createdAt, $windowEnd])
            ->where(function ($query) {
                $query->whereNull('tipo_evento')
                    ->orWhere('tipo_evento', AltaInventario::EVENTO_VENTA);
            })
            ->orderBy('created_at')
            ->limit(10)
            ->get();

        if ($movimientos->isEmpty() && $venta->created_at) {
            $ventaAt = $venta->created_at instanceof \Carbon\Carbon
                ? $venta->created_at
                : \Carbon\Carbon::parse($venta->created_at);
            $windowStart = $ventaAt->copy()->subMinutes(2);
            $windowEnd = $ventaAt->copy()->addMinutes(5);

            $movimientos = AltaInventario::where('id_producto', $producto->id_producto)
                ->where('id_sucursal', $idSucursal)
                ->whereBetween('created_at', [$windowStart, $windowEnd])
                ->where(function ($query) {
                    $query->whereNull('tipo_evento')
                        ->orWhere('tipo_evento', AltaInventario::EVENTO_VENTA);
                })
                ->orderBy('created_at')
                ->limit(10)
                ->get();
        }

        $stockMovimiento = $movimientos->first(function ($movimiento) use ($producto) {
            $vendido = (float) $producto->cantidad;
            $delta = (float) $movimiento->cantidad_actual - (float) $movimiento->cantidad_nueva;
            return abs($delta - $vendido) < 0.01;
        }) ?? $movimientos->first();

        if (!$stockMovimiento) {
            return [null, null];
        }

        return [$stockMovimiento->cantidad_actual, $stockMovimiento->cantidad_nueva];
    }

    public function reporteAumentosInventario(Request $request)
    {
        $request->validate([
            'fechaInicio' => ['required', 'date_format:Y-m-d'],
            'fechaFin'    => ['required', 'date_format:Y-m-d'],
        ]);

        $fechaInicio = new DateTime($request->fechaInicio);
        $fechaInicio->setTime(0, 0, 0);
        $fechaFin = new DateTime($request->fechaFin);
        $fechaFin->setTime(23, 59, 59);

        $idSucursal = $this->resolveSucursalIdForReport($request);

        $aumentos = AltaInventario::with(['producto.marca', 'usuario'])
            ->where('id_sucursal', $idSucursal)
            ->where('tipo_evento', AltaInventario::EVENTO_ALTA)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($alta) {
                $alta->cantidad_agregada = (float)$alta->cantidad_nueva - (float)$alta->cantidad_actual;
                return $alta;
            });

        $sucursalUser = Sucursales::find($idSucursal);
        $empresa = $sucursalUser ? Empresa::find($sucursalUser->id_empresa) : null;

        $fechaInicioTexto = (new DateTime($request->fechaInicio))->format('d/m/Y');
        $fechaFinTexto    = (new DateTime($request->fechaFin))->format('d/m/Y');

        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->set_option('enable_php', true);
        $pdf->loadView(
            'reportes/aumentos_inventario',
            compact('aumentos', 'empresa', 'sucursalUser', 'fechaInicioTexto', 'fechaFinTexto')
        );
        $pdf->set_paper('A4', 'landscape');
        $pdf->setOption('javascript-delay', 500);

        return $pdf->stream('aumentos_inventario.pdf');
    }

    private function streamVentasCsv(
        DateTime $fechaInicio,
        DateTime $fechaFin,
        int $idSucursal,
        float $montoContado,
        float $montoCredito,
        ?Sucursales $sucursalUser
    ) {
        $fechaInicioCsv = $fechaInicio->format('Y-m-d');
        $fechaFinCsv = $fechaFin->format('Y-m-d');
        $sucursalNombre = optional($sucursalUser)->nombre ?? '';
        $fileName = 'ventas_' . $fechaInicioCsv . '_' . $fechaFinCsv . '.csv';

        return response()->streamDownload(function () use (
            $fechaInicio,
            $fechaFin,
            $fechaInicioCsv,
            $fechaFinCsv,
            $idSucursal,
            $sucursalNombre,
            $montoContado,
            $montoCredito
        ) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Sucursal',
                'Fecha reporte inicio',
                'Fecha reporte fin',
                'Id venta',
                'Fecha de venta',
                'Nombre del producto',
                'Cantidad',
                'Precio unitario',
                'Total',
                'Cliente',
                'Stock anterior',
                'Nuevo stock',
                'Usuario',
            ]);

            $ventasQuery = Venta::whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->where('id_sucursal', $idSucursal)
                ->orderBy('id')
                ->select(['id', 'created_at', 'id_cliente', 'id_usuario']);

            $ventasQuery->chunkById(200, function ($ventas) use (
                $handle,
                $idSucursal,
                $sucursalNombre,
                $fechaInicioCsv,
                $fechaFinCsv
            ) {
                if ($ventas->isEmpty()) {
                    return;
                }

                $ventaIds = $ventas->pluck('id');
                $productosVenta = ProductoVenta::whereIn('id_venta', $ventaIds)->get();
                if ($productosVenta->isEmpty()) {
                    return;
                }

                $productosByVenta = $productosVenta->groupBy('id_venta');

                $productoIds = $productosVenta->pluck('id_producto')->unique()->values();
                $productosCatalogo = $productoIds->isEmpty()
                    ? collect()
                    : Producto::withTrashed()->whereIn('id', $productoIds)->get()->keyBy('id');

                $clienteIds = $ventas->pluck('id_cliente')->filter()->unique()->values();
                $clientes = $clienteIds->isEmpty()
                    ? collect()
                    : Clientes::whereIn('id', $clienteIds)->get()->keyBy('id');

                $usuarioIds = $ventas->pluck('id_usuario')->filter()->unique()->values();
                $usuarios = $usuarioIds->isEmpty()
                    ? collect()
                    : User::whereIn('id', $usuarioIds)->get()->keyBy('id');

                foreach ($ventas as $venta) {
                    $clienteNombre = optional($clientes->get($venta->id_cliente))->nombre ?? 'Cliente público';
                    $usuarioNombre = optional($usuarios->get($venta->id_usuario))->name ?? 'N/D';
                    $lineas = $productosByVenta->get($venta->id, collect());

                    foreach ($lineas as $productoVenta) {
                        $productoDetalle = $productosCatalogo->get($productoVenta->id_producto);
                        if ($productoDetalle) {
                            $productoNombre = $productoDetalle->nombre;
                            if ($productoDetalle->trashed()) {
                                $productoNombre .= ' (BORRADO)';
                            }
                        } else {
                            $productoNombre = 'Producto (BORRADO)';
                        }

                        $precioUnitario = $productoVenta->cantidad
                            ? ($productoVenta->total_productos / $productoVenta->cantidad)
                            : 0;

                        [$stockAnterior, $stockNuevo] = $this->resolveStockMovimientoForProductoVenta($productoVenta, $venta, $idSucursal);
                        $stockAnterior = $stockAnterior === null ? 'N/D' : $stockAnterior;
                        $stockNuevo = $stockNuevo === null ? 'N/D' : $stockNuevo;

                        fputcsv($handle, [
                            $sucursalNombre,
                            $fechaInicioCsv,
                            $fechaFinCsv,
                            $venta->id,
                            $venta->created_at,
                            $productoNombre,
                            $productoVenta->cantidad,
                            $precioUnitario,
                            $productoVenta->total_productos,
                            $clienteNombre,
                            $stockAnterior,
                            $stockNuevo,
                            $usuarioNombre,
                        ]);
                    }
                }

                fflush($handle);
            });

            $totalVentas = $montoContado + $montoCredito;
            fputcsv($handle, []);
            fputcsv($handle, [
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'Sumatoria total de las ventas',
                $totalVentas,
                '',
                '',
                '',
            ]);

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
