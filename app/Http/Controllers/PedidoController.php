<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Sucursales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PedidoController extends Controller
{
    public function index(Request $request)
    {
        $sucursal = Auth::user()->sucursal_id;
        $query = Pedido::with(['sucursal', 'producto'])
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
            'filtroSucursal' => $sucursal,
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
