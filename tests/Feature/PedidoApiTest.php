<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Sucursales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedidoApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_pedido(): void
    {
        $sucursal = Sucursales::factory()->create();
        $producto = Producto::factory()->create();

        $response = $this->postJson('/api/pedidos', [
            'sucursal_id' => $sucursal->id,
            'producto_id' => $producto->id,
            'cantidad' => 3,
            'nombre_solicitante' => 'Juan',
            'numero_solicitante' => '1234567890',
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['nombre_solicitante' => 'Juan']);

        $this->assertDatabaseCount('pedidos', 1);
    }
}
