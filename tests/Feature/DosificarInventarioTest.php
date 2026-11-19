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
use Tests\TestCase;

class DosificarInventarioTest extends TestCase
{
    use RefreshDatabase;

    private Sucursales $branch;
    private User $user;
    private Producto $producto;

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
        $this->producto = Producto::factory()->create([
            'nombre' => 'Herbicida',
            'tamano' => '1000ml',
            'id_empresa' => $empresa->id,
            'id_usuario' => $this->user->id,
            'id_marca' => CatMarca::factory()->create()->id,
            'id_clasificacion' => CatClasificacion::factory()->create()->id,
            'ingrediente_activo' => 'Glifosato',
            'barcode' => '750100',
            'precio_unitario' => 100,
            'precio_publico' => 200,
            'precio_preferencial_1' => 180,
            'precio_preferencial_2' => 160,
        ]);
        $this->stock($this->producto, 2);
    }

    private function stock(Producto $producto, float $cantidad): void
    {
        AltaInventario::create(['cantidad_actual' => 0, 'cantidad_nueva' => $cantidad, 'id_usuario' => $this->user->id,
            'id_producto' => $producto->id, 'id_sucursal' => $this->branch->id])->forceFill(['created_at' => now()->subMinute()])->save();
    }

    private function existencia(Producto $producto): float
    {
        return (float) AltaInventario::where('id_producto', $producto->id)->where('id_sucursal', $this->branch->id)
            ->orderByDesc('id')->value('cantidad_nueva');
    }

    private function dosificar(array $input = [])
    {
        return $this->post(route('inventario.dosificar', $this->producto->id), [
            'contenido' => '1000', 'medida' => '250', 'unidad' => 'ml',
            'precio_publico' => '55.00', 'precio_preferencial_1' => '50.00', 'precio_preferencial_2' => '45.00', 'precio_preferencial_3' => '40.00', ...$input,
        ]);
    }

    public function test_exact_dose_removes_one_unit_and_adds_the_measures(): void
    {
        $this->dosificar(['precio_unitario' => '1.00'])->assertRedirect(route('inventario.index'))->assertSessionHasNoErrors()->assertSessionHas('success');

        $medida = Producto::where('nombre', 'Medida Herbicida')->firstOrFail();
        $this->assertSame('250 ml', $medida->tamano);
        $this->assertSame([$this->producto->id_clasificacion, $this->producto->id_marca, 'Glifosato', ''],
            [$medida->id_clasificacion, $medida->id_marca, $medida->ingrediente_activo, $medida->barcode]);
        $this->assertEquals([55, 50, 45, 40], array_map('floatval', array_values($medida->only(array_values(Producto::PRECIOS)))));
        // El costo se deriva del envase (100 / 4), no del valor enviado.
        $this->assertEquals(25, $medida->precio_unitario);
        $this->assertEquals(1, $this->existencia($this->producto));
        $this->assertEquals(4, $this->existencia($medida));
        $this->assertSame(1, AltaInventario::where('id_producto', $medida->id)->count());
        $this->assertSame(AltaInventario::EVENTO_DOSIFICACION_SALIDA, AltaInventario::where('id_producto', $this->producto->id)->orderByDesc('id')->value('tipo_evento'));
        $this->assertSame(AltaInventario::EVENTO_DOSIFICACION_ENTRADA, AltaInventario::where('id_producto', $medida->id)->orderByDesc('id')->value('tipo_evento'));

        $this->getJson(route('inventario.cardex', $this->producto->id))->assertOk()->assertJsonPath('movimientos.0.tipo', 'Salida por dosificación')
            ->assertJsonPath('movimientos.0.cantidad_movida', -1);
        $this->getJson(route('inventario.cardex', $medida->id))->assertOk()->assertJsonPath('movimientos.0.tipo', 'Entrada por dosificación')
            ->assertJsonPath('movimientos.0.cantidad_movida', 4);
    }

    public function test_leftover_requires_accepting_the_loss(): void
    {
        $this->dosificar(['medida' => '300'])->assertSessionHasErrors('acepta_perdida');
        $this->assertSame(0, Producto::where('nombre', 'Medida Herbicida')->count());
        $this->assertEquals(2, $this->existencia($this->producto));

        $this->dosificar(['medida' => '300', 'acepta_perdida' => true])->assertSessionHasNoErrors();
        $medida = Producto::where('nombre', 'Medida Herbicida')->firstOrFail();
        $this->assertSame('300 ml', $medida->tamano);
        $this->assertEquals(3, $this->existencia($medida));
        $this->assertEquals(33.33, $medida->precio_unitario);
        $this->assertEquals(1, $this->existencia($this->producto));
    }

    public function test_dosing_again_reuses_the_measure_and_updates_prices(): void
    {
        $this->dosificar()->assertSessionHasNoErrors();
        $this->dosificar(['precio_publico' => '60.00', 'precio_preferencial_1' => '', 'precio_preferencial_2' => '', 'precio_preferencial_3' => ''])->assertSessionHasNoErrors();

        $this->assertSame(1, Producto::where('nombre', 'Medida Herbicida')->count());
        $medida = Producto::where('nombre', 'Medida Herbicida')->firstOrFail();
        $this->assertEquals([60, 50, 45, 40], array_map('floatval', array_values($medida->only(array_values(Producto::PRECIOS)))));
        $this->assertEquals(8, $this->existencia($medida));
        $this->assertEquals(0, $this->existencia($this->producto));

        // Otro tamaño es otro producto; una medida eliminada se recupera.
        $this->stock($this->producto, 2);
        $this->dosificar(['medida' => '500'])->assertSessionHasNoErrors();
        $this->assertSame(2, Producto::where('nombre', 'Medida Herbicida')->count());
        AltaInventario::create(['cantidad_actual' => 8, 'cantidad_nueva' => 0, 'id_usuario' => $this->user->id,
            'id_producto' => $medida->id, 'id_sucursal' => $this->branch->id]);
        $medida->delete();
        $this->dosificar()->assertSessionHasNoErrors();
        $this->assertFalse($medida->fresh()->trashed());
        $this->assertEquals(4, $this->existencia($medida));
    }

    public function test_rejects_dose_without_stock_or_with_invalid_sizes(): void
    {
        $this->dosificar(['medida' => '2000'])->assertSessionHasErrors('medida');
        $this->dosificar(['medida' => '0'])->assertSessionHasErrors('medida');
        $this->dosificar(['medida' => '0.001'])->assertSessionHasErrors('medida');
        $this->dosificar(['unidad' => ''])->assertSessionHasErrors('unidad');
        $this->dosificar(['precio_publico' => ''])->assertSessionHasErrors('precio_publico');
        $this->stock($this->producto, 0.5);
        $this->dosificar()->assertSessionHasErrors('producto');

        $this->assertSame(0, Producto::where('nombre', 'Medida Herbicida')->count());
        $this->assertEquals(0.5, $this->existencia($this->producto));
    }
}
