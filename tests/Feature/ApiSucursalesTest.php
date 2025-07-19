<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Sucursales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiSucursalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_sucursales_list(): void
    {
        $empresa = Empresa::factory()->create();
        Sucursales::factory()->count(2)->create(['id_empresa' => $empresa->id]);

        $response = $this->withHeaders([
            'X-API-KEY' => config('services.external_api.key'),
        ])->getJson('/api/sucursales');

        $response->assertStatus(200)
                 ->assertJsonCount(2);
    }
}
