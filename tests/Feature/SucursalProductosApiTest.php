<?php

namespace Tests\Feature;

use App\Models\AltaInventario;
use App\Models\Empresa;
use App\Models\CatClasificacion;
use App\Models\CatMarca;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SucursalProductosApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_productos_from_sucursal(): void
    {
        $empresa = Empresa::factory()->create();
        $sucursal = Sucursales::factory()->create(['id_empresa' => $empresa->id]);
        $clasificacion = CatClasificacion::factory()->create();
        $marca = CatMarca::factory()->create();
        $usuario = User::factory()->create(['id_sucursal' => $sucursal->id]);
        $productos = Producto::factory()->count(2)->create([
            'id_clasificacion' => $clasificacion->id,
            'id_marca' => $marca->id,
            'id_usuario' => $usuario->id,
        ]);

        foreach ($productos as $producto) {
            AltaInventario::factory()->create([
                'id_producto' => $producto->id,
                'id_sucursal' => $sucursal->id,
                'cantidad_actual' => 0,
                'cantidad_nueva' => 5,
            ]);
        }

        $response = $this->withHeaders([
            'X-API-KEY' => config('services.external_api.key'),
        ])->getJson("/api/sucursales/{$sucursal->id}/productos");

        $response->assertStatus(200)
                ->assertJsonCount(2);
    }

    public function test_can_filter_productos_by_nombre(): void
    {
        $empresa = Empresa::factory()->create();
        $sucursal = Sucursales::factory()->create(['id_empresa' => $empresa->id]);
        $clasificacion = CatClasificacion::factory()->create();
        $marca = CatMarca::factory()->create();
        $usuario = User::factory()->create(['id_sucursal' => $sucursal->id]);

        $productos = [
            Producto::factory()->create([
                'nombre' => 'Herbicida Especial',
                'id_clasificacion' => $clasificacion->id,
                'id_marca' => $marca->id,
                'id_usuario' => $usuario->id,
            ]),
            Producto::factory()->create([
                'nombre' => 'Insecticida Premium',
                'id_clasificacion' => $clasificacion->id,
                'id_marca' => $marca->id,
                'id_usuario' => $usuario->id,
            ]),
        ];

        foreach ($productos as $producto) {
            AltaInventario::factory()->create([
                'id_producto' => $producto->id,
                'id_sucursal' => $sucursal->id,
                'cantidad_actual' => 0,
                'cantidad_nueva' => 5,
            ]);
        }

        $response = $this->withHeaders([
            'X-API-KEY' => config('services.external_api.key'),
        ])->getJson("/api/sucursales/{$sucursal->id}/productos?busqueda=herbi");

        $response->assertStatus(200)
                 ->assertJsonCount(1)
                 ->assertJsonFragment(['nombre' => 'Herbicida Especial']);
    }
}
