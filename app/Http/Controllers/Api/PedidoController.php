<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\Http\Request;

class PedidoController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'sucursal_id' => 'required|exists:sucursales,id',
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|numeric|min:1',
            'nombre_solicitante' => 'required|string',
            'numero_solicitante' => 'required|string',
        ]);

        $pedidos = [];
        foreach ($data['productos'] as $item) {
            $pedidos[] = Pedido::create([
                'id_sucursal' => $data['sucursal_id'],
                'id_producto' => $item['producto_id'],
                'cantidad' => $item['cantidad'],
                'nombre_solicitante' => $data['nombre_solicitante'],
                'numero_solicitante' => $data['numero_solicitante'],
                'completado' => false,
            ]);
        }

        return response()->json($pedidos, 201);
    }
}
