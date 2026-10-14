<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Sucursales;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionAccessGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_is_redirected_when_trial_is_expired_and_no_subscription_exists(): void
    {
        $empresa = Empresa::factory()->create([
            'plan_code' => 'campo',
            'plan_cycle' => 'monthly',
            'numero_sucursales' => 1,
            'numero_dispositivos_por_sucursal' => 1,
            'trial_ends_at' => now()->subDay(),
        ]);

        $sucursal = Sucursales::factory()->create([
            'id_empresa' => $empresa->id,
            'es_matriz' => true,
        ]);

        $user = User::factory()->create([
            'tipo' => 'vendedor',
            'id_sucursal' => $sucursal->id,
            'id_empresa' => $empresa->id,
        ]);

        $this->actingAs($user);

        $this
            ->get('/dashboard')
            ->assertRedirect(route('subscription.show'))
            ->assertSessionHas('error');
    }
}

