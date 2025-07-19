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
        $query = Pedido::with(['sucursal', 'producto'])->latest();
        if ($request->sucursal) {
            $query->where('id_sucursal', $request->sucursal);
        }

        return Inertia::render('Pedido/Index', [
            'pedidos' => $query->get(),
            'sucursales' => Sucursales::all(),
            'filtroSucursal' => $request->sucursal,
        ]);
    }
}
