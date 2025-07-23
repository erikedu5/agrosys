<?php

namespace App\Events;

use App\Models\Pedido;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PedidoCreado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $pedido;

    public function __construct(Pedido $pedido)
    {
        $this->pedido = $pedido->load('sucursal', 'producto');
    }

    public function broadcastOn(): Channel
    {
        return new Channel('pedidos.sucursal.' . $this->pedido->id_sucursal);
    }

    public function broadcastAs(): string
    {
        return 'PedidoCreado';
    }
}
