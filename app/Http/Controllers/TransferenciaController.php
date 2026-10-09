<?php

namespace App\Http\Controllers;

use App\Models\AltaInventario;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\Transferencia;
use App\Services\SucursalService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class TransferenciaController extends Controller
{
    public function index()
    {
        $sucursalActiva = SucursalService::getSucursalActiva();

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
            'transferencias' => $transferencias
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
        $branch = Sucursales::findOrFail(SucursalService::getSucursalActiva());
        $logger = \App\Services\EventLogger::start('CREAR_TRANSFERENCIA', ['sucursal_origen_id' => $branch->id,
            'sucursal_destino_id' => $request->id_sucursal_destino]);
        try {
            $transfer = app(\App\Services\TransferManagement::class)->create($request->user(), $branch, $request->all());
            $logger->success(['transferencia_id' => $transfer->id, 'folio' => $transfer->folio]);
            return redirect()->route('transferencias.index')->with('success', 'Transferencia creada. Pendiente de recepción por sucursal destino.');
        } catch (\Throwable $e) {
            $logger->error($e);
            throw $e;
        }
    }

    public function recibir(Request $request, $id)
    {
        $branch = Sucursales::findOrFail(SucursalService::getSucursalActiva());
        $logger = \App\Services\EventLogger::start('RECIBIR_TRANSFERENCIA', ['transferencia_id' => $id, 'sucursal_id' => $branch->id]);
        try {
            app(\App\Services\TransferManagement::class)->receive($request->user(), $branch, (int) $id);
            $logger->success(['transferencia_id' => $id]);
            return redirect()->route('transferencias.show', $id)->with('success', 'Transferencia recibida exitosamente.');
        } catch (\Throwable $e) {
            $logger->error($e);
            throw $e;
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

        return Inertia::render('Inventario/Transferencias/Show', [
            'transferencia' => $transferencia
        ]);
    }
    public function buscarProductos(Request $request)
    {
        $term = $request->query('busqueda');
        $sucursalId = SucursalService::getSucursalActiva();

        // Iniciar logging del evento
        $logger = \App\Services\EventLogger::start('BUSCAR_PRODUCTOS_TRANSFERENCIA', [
            'termino_busqueda' => $term,
            'sucursal_id' => $sucursalId,
        ]);

        try {
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

            $logger->success([
                'productos_encontrados' => $productos->count(),
            ]);

            return response()->json($productos);
        } catch (\Exception $e) {
            $logger->error($e, [
                'termino_busqueda' => $term,
            ]);

            return response()->json(['error' => 'Error al buscar productos'], 500);
        }
    }

    public function generarRecibo($id)
    {
        // Iniciar logging del evento
        $logger = \App\Services\EventLogger::start('GENERAR_RECIBO_TRANSFERENCIA', [
            'transferencia_id' => $id,
        ]);

        try {
            $transferencia = Transferencia::with([
                'sucursalOrigen',
                'sucursalDestino',
                'usuarioEnvia',
                'usuarioRecibe',
                'detalles.producto'
            ])->findOrFail($id);

            $logger->step('Transferencia cargada', [
                'folio' => $transferencia->folio,
                'status' => $transferencia->status,
            ]);

            // Solo permitir generar recibo si está completada
            if ($transferencia->status !== 'completado') {
                $logger->warning('Intento de generar recibo de transferencia no completada', [
                    'status_actual' => $transferencia->status,
                ]);
                return back()->with('error', 'Solo se pueden imprimir recibos de transferencias completadas.');
            }

            // Calcular total de items
            $totalItems = $transferencia->detalles->sum('cantidad');

            $logger->step('Generando PDF', [
                'total_items' => $totalItems,
                'num_productos' => $transferencia->detalles->count(),
            ]);

            $pdf = Pdf::loadView('pdf.transferencia-recibo', compact('transferencia', 'totalItems'))
                ->setPaper('letter', 'portrait');

            $logger->success([
                'transferencia_id' => $transferencia->id,
                'folio' => $transferencia->folio,
                'total_items' => $totalItems,
            ]);

            return $pdf->stream('Transferencia-' . ($transferencia->folio ?? $transferencia->id) . '.pdf');
        } catch (\Exception $e) {
            $logger->error($e, [
                'transferencia_id' => $id,
            ]);

            return back()->with('error', 'Error al generar recibo: ' . $e->getMessage());
        }
    }
}
