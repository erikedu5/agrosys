<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Sucursales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Services\EventLogger;

class PedidoController extends Controller
{
    public function index(Request $request)
    {
        $sucursal = Auth::user()->sucursal_id;
        $query = Pedido::with(['sucursal', 'producto'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->whereHas('producto', function ($p) use ($request) {
                        $p->where('nombre', 'LIKE', "%{$request->q}%");
                    })->orWhere('nombre_solicitante', 'LIKE', "%{$request->q}%");
                });
            })
            ->when($request->filled('completado'), function ($q) use ($request) {
                $q->where('completado', $request->completado);
            })
            ->latest();

        $pedidos = $query->paginate(10)->withQueryString();

        return Inertia::render('Pedido/Index', [
            'pedidos' => $pedidos,
            'sucursales' => Sucursales::all(),
            'filtroSucursal' => $sucursal,
            'filtroCompletado' => $request->completado,
            'search' => $request->q,
        ]);
    }

    public function complete(Pedido $pedido)
    {
        // Iniciar logging del evento
        $logger = EventLogger::start('COMPLETAR_PEDIDO', [
            'pedido_id' => $pedido->id,
            'producto_id' => $pedido->id_producto,
            'sucursal_id' => $pedido->id_sucursal,
            'estado_anterior' => $pedido->completado,
        ]);

        try {
            $logger->step('Pedido cargado', [
                'nombre_solicitante' => $pedido->nombre_solicitante,
                'cantidad' => $pedido->cantidad,
            ]);

            $pedido->update(['completado' => true]);

            $logger->success([
                'pedido_id' => $pedido->id,
                'completado' => true,
            ]);

            return back();
        } catch (\Exception $e) {
            $logger->error($e, [
                'pedido_id' => $pedido->id,
            ]);

            return back()->with('error', 'Error al completar pedido: ' . $e->getMessage());
        }
    }
}
