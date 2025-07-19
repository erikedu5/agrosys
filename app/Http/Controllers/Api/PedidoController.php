<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Events\PedidoCreado;
use Illuminate\Http\Request;

class PedidoController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'sucursal_id' => 'required|exists:sucursales,id',
            'producto_id' => 'required|exists:productos,id',
            'cantidad' => 'required|numeric',
            'nombre_solicitante' => 'required|string',
            'numero_solicitante' => 'required|string',
        ]);

        $pedido = Pedido::create([
            'id_sucursal' => $data['sucursal_id'],
            'id_producto' => $data['producto_id'],
            'cantidad' => $data['cantidad'],
            'nombre_solicitante' => $data['nombre_solicitante'],
            'numero_solicitante' => $data['numero_solicitante'],
        ]);

        event(new PedidoCreado($pedido));

        return response()->json($pedido, 201);
    }
}
