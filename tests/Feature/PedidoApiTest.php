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
        $productos = Producto::factory()->count(2)->create();

        $response = $this->postJson('/api/pedidos', [
            'sucursal_id' => $sucursal->id,
            'productos' => [
                ['producto_id' => $productos[0]->id, 'cantidad' => 3],
                ['producto_id' => $productos[1]->id, 'cantidad' => 2],
            ],
            'nombre_solicitante' => 'Juan',
            'numero_solicitante' => '1234567890',
        ]);

        $response->assertStatus(201)
                 ->assertJsonCount(2)
                 ->assertJsonFragment(['nombre_solicitante' => 'Juan']);

        $this->assertDatabaseCount('pedidos', 2);
    }
}
