<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedidoCompleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_pedido_can_be_completed(): void
    {
        $pedido = Pedido::factory()->create(['completado' => false]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('pedidos.complete', $pedido));

        $response->assertStatus(302);
        $this->assertTrue($pedido->fresh()->completado);
    }
}
