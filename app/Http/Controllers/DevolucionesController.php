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
            $prod = Producto::find($pv->id_producto);
            $yaDevuelto = (float) ($devueltos[$pv->id_producto] ?? 0);
            $items[] = [
                'producto_id' => $pv->id_producto,
                'producto_nombre' => $prod ? $prod->nombre : ('ID ' . $pv->id_producto),
                'vendido' => (float) $pv->cantidad,
                'devuelto' => $yaDevuelto,
                'max_devolver' => max(0, (float)$pv->cantidad - $yaDevuelto),
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

        $productosVenta = ProductoVenta::where('id_venta', $venta->id)->get()->keyBy('id_producto');
        $devueltos = Devoluciones::where('id_venta', $venta->id)
            ->with('detalles')
            ->get()
            ->flatMap->detalles
            ->groupBy('id_producto')
            ->map->sum('cantidad');

        DB::transaction(function () use ($data, $venta, $productosVenta, $devueltos) {
            $totalDevuelto = 0;
            $detallesValidos = [];

            foreach ($data['items'] as $item) {
                $productoId = (int) $item['producto_id'];
                $cantidad = (float) $item['cantidad'];
                if ($cantidad <= 0) continue;

                $pv = $productosVenta[$productoId] ?? null;
                if (!$pv) {
                    continue; // producto no pertenece a la venta
                }
                $yaDev = (float) ($devueltos[$productoId] ?? 0);
                $max = max(0, (float)$pv->cantidad - $yaDev);
                if ($cantidad > $max) {
                    $cantidad = $max; // clamp
                }
                if ($cantidad <= 0) continue;

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
                abort(422, 'No hay Devoluciones válidas.');
            }

            $Devoluciones = Devoluciones::create([
                'id_venta' => $venta->id,
                'id_usuario' => Auth::id(),
                'id_sucursal' => SucursalService::getSucursalActiva(),
                'total_devuelto' => $totalDevuelto,
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            foreach ($detallesValidos as $det) {
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

                $actual = $ultima ? (float)$ultima->cantidad_nueva : 0;
                $alta = new AltaInventario();
                $alta->cantidad_actual = $actual;
                $alta->cantidad_nueva = $actual + (float)$det['cantidad'];
                $alta->id_usuario = Auth::id();
                $alta->id_producto = $det['id_producto'];
                $alta->id_sucursal = SucursalService::getSucursalActiva();
                $alta->tipo_evento = AltaInventario::EVENTO_ALTA;
                $alta->save();
            }

            // Ajustar venta total
            $venta->total = max(0, (float)$venta->total - $totalDevuelto);
            $venta->save();

            // Ajustar cuenta del cliente
            $cliente = Clientes::find($venta->id_cliente);
            if ($cliente) {
                // Reducir adeudo por lo devuelto
                $cliente->adeudo_total = max(0, (float)$cliente->adeudo_total - $totalDevuelto);
                if ($venta->tipo_venta === 'Contado') {
                    // Si fue contado y se reembolsa, reducimos también lo abonado para mantener balance
                    $cliente->abono_total = max(0, (float)$cliente->abono_total - $totalDevuelto);
                }
                $cliente->balance = (float)$cliente->adeudo_total - (float)$cliente->abono_total;
                $cliente->save();
            }
        });

        return redirect()->route('devoluciones.list')->with('success', 'Devolución registrada correctamente');
    }
}
