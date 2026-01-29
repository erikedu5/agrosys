<?php

namespace App\Http\Controllers;

use App\Models\AltaInventario;
use App\Models\Clientes;
use App\Models\Devoluciones;
use App\Models\DevolucionesDetalle;
use App\Models\Producto;
use App\Models\ProductoVenta;
use App\Models\Venta;
use App\Services\SucursalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DevolucionesController extends Controller
{
    public function index()
    {
        $ventas = Venta::where('id_sucursal', SucursalService::getSucursalActiva())
            ->whereDate('created_at', now()->toDateString())
            ->orderByDesc('created_at')
            ->get(['id', 'total']);

        $ventas = $ventas->map(function ($v) {
            return [
                'id' => $v->id,
                'label' => 'Venta #' . $v->id . ' - $' . $v->total,
            ];
        });

        return Inertia::render('Venta/BuscarDevoluciones', [
            'ventasHoy' => $ventas,
        ]);
    }

    public function list()
    {
        $devoluciones = Devoluciones::where('id_sucursal', SucursalService::getSucursalActiva())
            ->with('detalles', 'detalles.producto')
            ->orderByDesc('created_at')
            ->get(['id', 'id_venta', 'total_devuelto', 'created_at']);

        $devoluciones = $devoluciones->map(function ($d) {
            return [
                'id' => $d->id,
                'venta_id' => $d->id_venta,
                'total' => $d->total_devuelto,
                'fecha' => $d->created_at->toDateTimeString(),
                'detalles' => $d->detalles,
            ];
        });

        return Inertia::render('Venta/ListadoDevoluciones', [
            'devoluciones' => $devoluciones,
        ]);
    }

    public function create(Venta $venta)
    {
        $productosVenta = ProductoVenta::where('id_venta', $venta->id)->get();

        $devueltos = Devoluciones::where('id_venta', $venta->id)
            ->with('detalles')
            ->get()
            ->flatMap->detalles
            ->groupBy('id_producto')
            ->map->sum('cantidad');

        $items = [];
        foreach ($productosVenta as $pv) {
            $prod = Producto::withTrashed()->find($pv->id_producto);
            $yaDevuelto = (float) ($devueltos[$pv->id_producto] ?? 0);
            $nombreProducto = $prod ? $prod->nombre : ('ID ' . $pv->id_producto);
            if ($prod && $prod->trashed()) {
                $nombreProducto .= ' (BORRADO)';
            }
            $items[] = [
                'producto_id' => $pv->id_producto,
                'producto_nombre' => $nombreProducto,
                'vendido' => (float) $pv->cantidad,
                'devuelto' => $yaDevuelto,
                'max_devolver' => max(0, (float) $pv->cantidad - $yaDevuelto),
                'precio_unitario' => $pv->cantidad > 0 ? ($pv->total_productos / $pv->cantidad) : 0,
            ];
        }

        return Inertia::render('Venta/Devoluciones', [
            'venta' => $venta,
            'items' => $items,
        ]);
    }

    public function store(Request $request, Venta $venta)
    {
        $data = $request->validate([
            'observaciones' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|integer',
            'items.*.cantidad' => 'required|numeric|min:0.01',
        ]);

        // Iniciar logging del evento
        $logger = \App\Services\EventLogger::start('PROCESAR_DEVOLUCION', [
            'venta_id' => $venta->id,
            'venta_total' => $venta->total,
            'num_items' => count($data['items']),
            'sucursal_id' => SucursalService::getSucursalActiva(),
        ]);

        try {
            $productosVenta = ProductoVenta::where('id_venta', $venta->id)->get()->keyBy('id_producto');
            $devueltos = Devoluciones::where('id_venta', $venta->id)
                ->with('detalles')
                ->get()
                ->flatMap->detalles
                ->groupBy('id_producto')
                ->map->sum('cantidad');

            $logger->step('Datos de venta cargados', [
                'productos_venta' => $productosVenta->count(),
                'devoluciones_previas' => $devueltos->count(),
            ]);

            DB::transaction(function () use ($data, $venta, $productosVenta, $devueltos, $logger) {
                $totalDevuelto = 0;
                $detallesValidos = [];

                foreach ($data['items'] as $item) {
                    $productoId = (int) $item['producto_id'];
                    $cantidad = (float) $item['cantidad'];
                    if ($cantidad <= 0)
                        continue;

                    $pv = $productosVenta[$productoId] ?? null;
                    if (!$pv) {
                        continue; // producto no pertenece a la venta
                    }
                    $yaDev = (float) ($devueltos[$productoId] ?? 0);
                    $max = max(0, (float) $pv->cantidad - $yaDev);
                    if ($cantidad > $max) {
                        $cantidad = $max; // clamp
                    }
                    if ($cantidad <= 0)
                        continue;

                    $precioUnit = $pv->cantidad > 0 ? ($pv->total_productos / $pv->cantidad) : 0;
                    $importe = round($precioUnit * $cantidad, 2);
                    $detallesValidos[] = [
                        'id_producto' => $productoId,
                        'cantidad' => $cantidad,
                        'total' => $importe,
                    ];
                    $totalDevuelto += $importe;
                }

                if (empty($detallesValidos)) {
                    $logger->warning('No hay devoluciones válidas');
                    abort(422, 'No hay Devoluciones válidas.');
                }

                $logger->step('Items validados', [
                    'items_validos' => count($detallesValidos),
                    'total_devuelto' => $totalDevuelto,
                ]);

                $Devoluciones = Devoluciones::create([
                    'id_venta' => $venta->id,
                    'id_usuario' => Auth::id(),
                    'id_sucursal' => SucursalService::getSucursalActiva(),
                    'total_devuelto' => $totalDevuelto,
                    'observaciones' => $data['observaciones'] ?? null,
                ]);

                $logger->step('Devolución creada', [
                    'devolucion_id' => $Devoluciones->id,
                ]);

                foreach ($detallesValidos as $index => $det) {
                    DevolucionesDetalle::create([
                        'id_devolucion' => $Devoluciones->id,
                        'id_producto' => $det['id_producto'],
                        'cantidad' => $det['cantidad'],
                        'total' => $det['total'],
                    ]);

                    // Regresar al inventario
                    $ultima = AltaInventario::where('id_producto', $det['id_producto'])
                        ->where('id_sucursal', SucursalService::getSucursalActiva())
                        ->orderByDesc('id')
                        ->first();

                    $actual = $ultima ? (float) $ultima->cantidad_nueva : 0;
                    $alta = new AltaInventario();
                    $alta->cantidad_actual = $actual;
                    $alta->cantidad_nueva = $actual + (float) $det['cantidad'];
                    $alta->id_usuario = Auth::id();
                    $alta->id_producto = $det['id_producto'];
                    $alta->id_sucursal = SucursalService::getSucursalActiva();
                    $alta->tipo_evento = AltaInventario::EVENTO_ALTA;
                    $alta->save();

                    $logger->step("Producto {$index} devuelto a inventario", [
                        'producto_id' => $det['id_producto'],
                        'cantidad' => $det['cantidad'],
                        'stock_anterior' => $actual,
                        'stock_nuevo' => $alta->cantidad_nueva,
                    ]);
                }

                // Ajustar venta total
                $totalAnterior = $venta->total;
                $venta->total = max(0, (float) $venta->total - $totalDevuelto);
                $venta->save();

                $logger->step('Venta ajustada', [
                    'total_anterior' => $totalAnterior,
                    'total_nuevo' => $venta->total,
                    'monto_devuelto' => $totalDevuelto,
                ]);

                // Ajustar cuenta del cliente
                $cliente = Clientes::find($venta->id_cliente);
                if ($cliente) {
                    $balanceAnterior = $cliente->balance;
                    // Reducir adeudo por lo devuelto
                    $cliente->adeudo_total = max(0, (float) $cliente->adeudo_total - $totalDevuelto);
                    if ($venta->tipo_venta === 'Contado') {
                        // Si fue contado y se reembolsa, reducimos también lo abonado para mantener balance
                        $cliente->abono_total = max(0, (float) $cliente->abono_total - $totalDevuelto);
                    }
                    $cliente->balance = (float) $cliente->adeudo_total - (float) $cliente->abono_total;
                    $cliente->save();

                    $logger->step('Cliente ajustado', [
                        'cliente_id' => $cliente->id,
                        'balance_anterior' => $balanceAnterior,
                        'balance_nuevo' => $cliente->balance,
                    ]);
                }
            });

            $logger->success([
                'venta_id' => $venta->id,
                'total_devuelto' => $totalDevuelto ?? 0,
            ]);

            return redirect()->route('devoluciones.list')->with('success', 'Devolución registrada correctamente');

        } catch (\Exception $e) {
            $logger->error($e, [
                'venta_id' => $venta->id,
                'items' => $data['items'],
            ]);

            throw $e;
        }
    }
}
