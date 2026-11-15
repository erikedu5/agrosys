<?php

namespace Tests\Feature;

use App\Models\AltaInventario;
use App\Models\CatClasificacion;
use App\Models\CatMarca;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class InventarioFiltrosTest extends TestCase
{
    use RefreshDatabase;

    private Sucursales $branch;
    private User $user;
    private CatMarca $marcaA;
    private CatMarca $marcaB;

    protected function setUp(): void
    {
        parent::setUp();
        $empresa = Empresa::factory()->create();
        $this->branch = Sucursales::factory()->create(['id_empresa' => $empresa->id]);
        $this->user = User::factory()->create(['tipo' => 'admin', 'id_sucursal' => $this->branch->id, 'id_empresa' => $empresa->id]);
        $this->actingAs($this->user);
        $this->withoutMiddleware([
            \App\Http\Middleware\EnsureEmpresaSubscription::class,
            \App\Http\Middleware\CheckRole::class,
            \App\Http\Middleware\CheckSucursalSelection::class,
        ]);
        $this->marcaA = CatMarca::factory()->create(['nombre' => 'Marca A']);
        $this->marcaB = CatMarca::factory()->create(['nombre' => 'Marca B']);
    }

    private function producto(string $nombre, CatMarca $marca, array $movimientos): Producto
    {
        $producto = Producto::factory()->create([
            'nombre' => $nombre,
            'id_empresa' => $this->user->id_empresa,
            'id_usuario' => $this->user->id,
            'id_marca' => $marca->id,
            'id_clasificacion' => CatClasificacion::factory()->create()->id,
            'tamano' => '1 L',
        ]);
        foreach ($movimientos as $indice => $cantidad) {
            $alta = AltaInventario::create([
                'cantidad_actual' => 0,
                'cantidad_nueva' => $cantidad,
                'id_usuario' => $this->user->id,
                'id_producto' => $producto->id,
                'id_sucursal' => $this->branch->id,
            ]);
            // El último movimiento es el que define la existencia.
            $alta->forceFill(['created_at' => now()->subMinutes(count($movimientos) - $indice)])->save();
        }

        return $producto;
    }

    private function nombres(array $params): array
    {
        $nombres = [];
        $this->get(route('inventario.index', $params))
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page) use (&$nombres) {
                $nombres = collect($page->toArray()['props']['productos']['data'])->pluck('nombre')->sort()->values()->all();
            });

        return $nombres;
    }

    public function test_filters_by_stock_level_and_brand(): void
    {
        $this->producto('Sin movimientos', $this->marcaA, []);
        $this->producto('Agotado', $this->marcaA, [20, 0]);
        $this->producto('Baja', $this->marcaA, [20, 5]);
        $this->producto('Normal A', $this->marcaA, [0, 12.5]);
        $this->producto('Normal B', $this->marcaB, [100]);

        $this->assertSame(['Agotado', 'Sin movimientos'], $this->nombres(['existencia' => 'agotado']));
        $this->assertSame(['Baja'], $this->nombres(['existencia' => 'baja']));
        $this->assertSame(['Normal A', 'Normal B'], $this->nombres(['existencia' => 'normal']));
        $this->assertSame(['Normal B'], $this->nombres(['marca' => $this->marcaB->id]));
        $this->assertSame(['Normal A'], $this->nombres(['marca' => $this->marcaA->id, 'existencia' => 'normal']));
        $this->assertSame(['Normal A', 'Normal B'], $this->nombres(['q' => 'Normal', 'existencia' => 'normal']));
        $this->assertCount(5, $this->nombres(['existencia' => 'otra']));
    }

    public function test_sends_brands_and_applied_filters_to_the_page(): void
    {
        $this->get(route('inventario.index', ['marca' => $this->marcaB->id, 'existencia' => 'baja']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filtros.marca', $this->marcaB->id)
                ->where('filtros.existencia', 'baja')
                ->has('marcas', 2));
    }
}
