<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Sucursales;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PedidoController extends Controller
{
    public function index(Request $request)
    {
        $query = Pedido::with(['sucursal', 'producto'])
            ->when($request->sucursal, function($q) use ($request) {
                $q->where('id_sucursal', $request->sucursal);
            })
            ->when($request->filled('q'), function($q) use ($request) {
                $q->where(function($sub) use ($request) {
                    $sub->whereHas('producto', function($p) use ($request) {
                        $p->where('nombre', 'LIKE', "%{$request->q}%");
                    })->orWhere('nombre_solicitante', 'LIKE', "%{$request->q}%");
                });
            })
            ->when($request->filled('completado'), function($q) use ($request) {
                $q->where('completado', $request->completado);
            })
            ->latest();

        $pedidos = $query->paginate(10)->withQueryString();

        return Inertia::render('Pedido/Index', [
            'pedidos' => $pedidos,
            'sucursales' => Sucursales::all(),
            'filtroSucursal' => $request->sucursal,
            'filtroCompletado' => $request->completado,
            'search' => $request->q,
        ]);
    }

    public function complete(Pedido $pedido)
    {
        $pedido->update(['completado' => true]);
        return back();
    }
}
