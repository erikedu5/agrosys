<?php

namespace App\Http\Controllers;

use App\Models\AltaInventario;
use App\Models\Compras;
use App\Models\ComprasProductos;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\Transferencia;
use App\Models\TransferenciaDetalle;
use App\Services\SucursalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class TransferenciaController extends Controller
{
    public function index()
    {
        $sucursalActiva = SucursalService::getSucursalActiva();

        $sucursalActivaCompleta = Sucursales::find($sucursalActiva);

        // Transferencias donde la sucursal activa es Origen o Destino
        $transferencias = Transferencia::with([
            'sucursalOrigen',
            'sucursalDestino',
            'usuarioEnvia',
            'usuarioRecibe',
            'detalles.producto'
        ])
            ->where(function ($query) use ($sucursalActiva) {
                $query->where('id_sucursal_origen', $sucursalActiva)
                    ->orWhere('id_sucursal_destino', $sucursalActiva);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Inventario/Transferencias/Index', [
            'transferencias' => $transferencias,
            'sucursalActiva' => $sucursalActivaCompleta
        ]);
    }

    public function create()
    {
        $sucursalId = SucursalService::getSucursalActiva();
        $sucursal = Sucursales::find($sucursalId);

        // Validar que sea bodega (opcional, segun requerimiento)
        // if (!$sucursal->es_bodega) { ... }

        $sucursalesDestino = Sucursales::where('id', '!=', $sucursalId)
            ->where('id_empresa', $sucursal->id_empresa)
            ->get();

        return Inertia::render('Inventario/Transferencias/Create', [
            'sucursalesDestino' => $sucursalesDestino,
            'sucursalOrigen' => $sucursal
        ]);
    }

    public function store(Request $request)
    {

        $request->validate([
            'id_sucursal_destino' => 'required|exists:sucursales,id',
            'productos' => 'required|array|min:1',
            'observaciones' => 'nullable|string'
        ]);


        $sucursalOrigenId = SucursalService::getSucursalActiva();
        $sucursalDestinoId = $request->id_sucursal_destino;


        try {
            DB::beginTransaction();

            // Crear Transferencia PENDIENTE (sin deducir stock)
            $transferencia = Transferencia::create([
                'folio' => 'TRANS-' . time(),
                'id_sucursal_origen' => $sucursalOrigenId,
                'id_sucursal_destino' => $sucursalDestinoId,
                'id_usuario_envia' => Auth::id(),
                'fecha_envio' => now(),
                'status' => 'pendiente',
                'notas' => $request->observaciones
            ]);

            // Crear detalles de transferencia (solo registro, no stock)
            foreach ($request->productos as $prodInfo) {
                TransferenciaDetalle::create([
                    'id_transferencia' => $transferencia->id,
                    'id_producto' => $prodInfo['id'],
                    'cantidad' => $prodInfo['cantidad'],
                    'lote_origen_id' => null // Se asignará al recibir
                ]);
            }

            DB::commit();

            return redirect()->route('transferencias.index')
                ->with('success', 'Transferencia creada. Pendiente de recepción por sucursal destino.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear transferencia: ' . $e->getMessage());
            return back()->with('error', 'Error al crear transferencia: ' . $e->getMessage());
        }
    }

    public function recibir(Request $request, $id)
    {
        $transferencia = Transferencia::with(['detalles', 'sucursalOrigen', 'sucursalDestino'])->findOrFail($id);
        $sucursalActiva = SucursalService::getSucursalActiva();

        // Validar que sea la sucursal destino
        if ($transferencia->id_sucursal_destino != $sucursalActiva) {
            abort(403, 'No autorizado para recibir esta transferencia.');
        }

        // Validar estado
        if ($transferencia->status !== 'pendiente') {
            return back()->withErrors(['error' => 'Esta transferencia ya ha sido procesada.']);
        }

        try {
            DB::beginTransaction();

            $sucursalOrigenId = $transferencia->id_sucursal_origen;
            $sucursalDestinoId = $transferencia->id_sucursal_destino;

            // Array para almacenar info de lotes para destino
            $detallesDestino = [];

            // === PASO 1: DEDUCIR STOCK DE ORIGEN USANDO FIFO ===
            foreach ($transferencia->detalles as $detalle) {
                $productoId = $detalle->id_producto;
                $cantidadSolicitada = $detalle->cantidad;

                // Verificar stock disponible en origen
                $stockActual = AltaInventario::where('id_producto', $productoId)
                    ->where('id_sucursal', $sucursalOrigenId)
                    ->latest()
                    ->first();
                $cantidadActual = $stockActual ? $stockActual->cantidad_nueva : 0;

                if ($cantidadActual < $cantidadSolicitada) {
                    throw new \Exception("Stock insuficiente en origen para producto ID {$productoId}. Disponible: {$cantidadActual}");
                }

                // Aplicar FIFO en ComprasProductos de origen
                $cantidadRestante = $cantidadSolicitada;

                $lotes = ComprasProductos::select('compras_productos.*')
                    ->join('compras', 'compras.id', '=', 'compras_productos.id_compra')
                    ->where('compras.id_sucursal', $sucursalOrigenId)
                    ->where('compras_productos.id_producto', $productoId)
                    ->where('compras_productos.cantidad_disponible', '>', 0)
                    ->orderBy('compras.fecha_compra', 'asc')
                    ->orderBy('compras_productos.id', 'asc')
                    ->get();

                foreach ($lotes as $lote) {
                    if ($cantidadRestante <= 0)
                        break;

                    $disponible = $lote->cantidad_disponible;
                    $tomar = min($disponible, $cantidadRestante);

                    // Deducir del lote origen
                    $lote->cantidad_disponible -= $tomar;
                    $lote->save();

                    // Actualizar detalle con lote origen
                    if (!$detalle->lote_origen_id) {
                        $detalle->lote_origen_id = $lote->id;
                        $detalle->save();
                    }

                    // Guardar info para crear lote en destino
                    $detallesDestino[] = [
                        'id_producto' => $productoId,
                        'cantidad' => $tomar,
                        'precio_costo' => $lote->precio
                    ];

                    $cantidadRestante -= $tomar;
                }

                // Si queda cantidad sin lote (inventario inicial)
                if ($cantidadRestante > 0) {
                    $prodModelo = Producto::find($productoId);
                    $detallesDestino[] = [
                        'id_producto' => $productoId,
                        'cantidad' => $cantidadRestante,
                        'precio_costo' => $prodModelo->precio_unitario
                    ];
                }

                // Registrar movimiento de SALIDA en origen
                $altaOrigen = new AltaInventario();
                $altaOrigen->cantidad_actual = $cantidadActual;
                $altaOrigen->cantidad_nueva = $cantidadActual - $cantidadSolicitada;
                $altaOrigen->id_usuario = Auth::id();
                $altaOrigen->id_producto = $productoId;
                $altaOrigen->id_sucursal = $sucursalOrigenId;
                $altaOrigen->tipo_evento = AltaInventario::EVENTO_TRANSFERENCIA_SALIDA;
                $altaOrigen->save();
            }

            // === PASO 2: CREAR COMPRA INTERNA EN DESTINO ===
            $compraDestino = Compras::create([
                'proveedor' => 'TRANSFERENCIA ' . ($transferencia->folio ?? '#' . $transferencia->id),
                'fecha_compra' => now(),
                'total_compra' => 0,
                'status' => 'pagado',
                'fecha_credito' => now(),
                'total_credito' => 0,
                'id_sucursal' => $sucursalDestinoId
            ]);

            // Agrupar por producto para AltaInventario destino
            $sumaPorProductoDestino = [];

            foreach ($detallesDestino as $item) {
                // Crear lote en destino
                ComprasProductos::create([
                    'id_compra' => $compraDestino->id,
                    'id_producto' => $item['id_producto'],
                    'cantidad' => $item['cantidad'],
                    'cantidad_disponible' => $item['cantidad'],
                    'precio' => $item['precio_costo']
                ]);

                if (!isset($sumaPorProductoDestino[$item['id_producto']])) {
                    $sumaPorProductoDestino[$item['id_producto']] = 0;
                }
                $sumaPorProductoDestino[$item['id_producto']] += $item['cantidad'];
            }

            // === PASO 3: REGISTRAR ENTRADA EN DESTINO ===
            foreach ($sumaPorProductoDestino as $prodId => $cantidadRecibida) {
                $stockActualDest = AltaInventario::where('id_producto', $prodId)
                    ->where('id_sucursal', $sucursalDestinoId)
                    ->latest()
                    ->first();

                $cantDest = $stockActualDest ? $stockActualDest->cantidad_nueva : 0;

                $altaDestino = new AltaInventario();
                $altaDestino->cantidad_actual = $cantDest;
                $altaDestino->cantidad_nueva = $cantDest + $cantidadRecibida;
                $altaDestino->id_usuario = Auth::id();
                $altaDestino->id_producto = $prodId;
                $altaDestino->id_sucursal = $sucursalDestinoId;
                $altaDestino->tipo_evento = AltaInventario::EVENTO_TRANSFERENCIA_ENTRADA;
                $altaDestino->save();
            }

            // === PASO 4: ACTUALIZAR TRANSFERENCIA ===
            $transferencia->status = 'completado';
            $transferencia->id_usuario_recibe = Auth::id();
            $transferencia->fecha_recepcion = now();
            $transferencia->save();

            DB::commit();

            return redirect()->route('transferencias.show', $transferencia->id)
                ->with('success', 'Transferencia recibida exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al recibir: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $transferencia = Transferencia::with([
            'sucursalOrigen',
            'sucursalDestino',
            'usuarioEnvia',
            'usuarioRecibe',
            'detalles.producto'
        ])->findOrFail($id);

        $sucursalActiva = SucursalService::getSucursalActiva();

        $sucursalActivaCompleta = Sucursales::find($sucursalActiva);

        return Inertia::render('Inventario/Transferencias/Show', [
            'transferencia' => $transferencia,
            'sucursalActiva' => $sucursalActivaCompleta
        ]);
    }
    public function buscarProductos(Request $request)
    {
        $term = $request->query('busqueda');
        $sucursalId = SucursalService::getSucursalActiva();

        $productos = Producto::where(function ($q) use ($term) {
            $q->where('nombre', 'LIKE', "%{$term}%")
                ->orWhere('barcode', 'LIKE', "%{$term}%");
        })
            // We need to verify stock in the current branch
            ->get()
            // Map to add quantity
            ->map(function ($prod) use ($sucursalId) {
                $stock = AltaInventario::where('id_producto', $prod->id)
                    ->where('id_sucursal', $sucursalId)
                    ->latest()
                    ->first();
                $prod->cantidad = $stock ? $stock->cantidad_nueva : 0;
                return $prod;
            })
            // Filter only positive stock
            ->filter(function ($prod) {
                return $prod->cantidad > 0;
            })
            ->values();

        return response()->json($productos);
    }

    public function generarRecibo($id)
    {
        $transferencia = Transferencia::with([
            'sucursalOrigen',
            'sucursalDestino',
            'usuarioEnvia',
            'usuarioRecibe',
            'detalles.producto'
        ])->findOrFail($id);

        // Solo permitir generar recibo si está completada
        if ($transferencia->status !== 'completado') {
            return back()->with('error', 'Solo se pueden imprimir recibos de transferencias completadas.');
        }

        // Calcular total de items
        $totalItems = $transferencia->detalles->sum('cantidad');

        $pdf = Pdf::loadView('pdf.transferencia-recibo', compact('transferencia', 'totalItems'))
            ->setPaper('letter', 'portrait');

        return $pdf->stream('Transferencia-' . ($transferencia->folio ?? $transferencia->id) . '.pdf');
    }
}
