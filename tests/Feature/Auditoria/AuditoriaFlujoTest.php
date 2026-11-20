<?php

namespace Tests\Feature\Auditoria;

use App\Models\AltaInventario;
use App\Models\Auditoria;
use App\Models\AuditoriaItem;
use App\Models\Empresa;
use App\Models\ProductoVenta;
use App\Models\Sucursales;
use App\Models\Venta;
use App\Services\InventoryManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AuditoriaFlujoTest extends TestCase
{
    use RefreshDatabase;
    use AuditoriaHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->escenario();
    }

    public function test_auditor_de_una_empresa_crea_sin_elegir_empresa_y_queda_como_participante(): void
    {
        $this->actingAs($this->auditor)
            ->post(route('auditorias.store'), ['id_sucursal' => $this->sucursal->id, 'alcance_tipo' => 'total'])
            ->assertRedirect();

        $auditoria = Auditoria::firstOrFail();
        $this->assertSame($this->empresa->id, (int) $auditoria->id_empresa);
        $this->assertSame(Auditoria::STATUS_BORRADOR, $auditoria->status);
        $this->assertMatchesRegularExpression('/^AUD-\d{4}-0001$/', $auditoria->folio);
        $this->assertTrue($auditoria->auditores()->where('users.id', $this->auditor->id)->exists());
    }

    public function test_sucursal_fuera_de_alcance_es_rechazada(): void
    {
        $limitado = $this->crearAuditor([[$this->empresa->id, $this->otraSucursal->id]]);
        $this->expectException(HttpException::class);

        $this->management()->crear($limitado, ['id_sucursal' => $this->sucursal->id, 'alcance_tipo' => 'total']);
    }

    public function test_auditor_multiempresa_debe_elegir_empresa_de_su_alcance(): void
    {
        $otraEmpresa = Empresa::factory()->create();
        $sucursalAjena = Sucursales::factory()->create(['id_empresa' => $otraEmpresa->id]);
        $multi = $this->crearAuditor([[$this->empresa->id, null], [$otraEmpresa->id, null]]);

        try {
            $this->management()->crear($multi, ['id_sucursal' => $this->sucursal->id, 'alcance_tipo' => 'total']);
            $this->fail('Debió pedir empresa.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('id_empresa', $e->errors());
        }

        $auditoria = $this->management()->crear($multi, ['id_empresa' => $otraEmpresa->id, 'id_sucursal' => $sucursalAjena->id, 'alcance_tipo' => 'total']);
        $this->assertSame($otraEmpresa->id, (int) $auditoria->id_empresa);

        $tercera = Empresa::factory()->create();
        $this->expectException(HttpException::class);
        $this->management()->crear($multi, ['id_empresa' => $tercera->id, 'id_sucursal' => $sucursalAjena->id, 'alcance_tipo' => 'total']);
    }

    public function test_solo_una_auditoria_activa_por_sucursal(): void
    {
        $this->management()->crear($this->auditor, ['id_sucursal' => $this->sucursal->id, 'alcance_tipo' => 'total']);

        $this->expectException(ValidationException::class);
        $this->management()->crear($this->auditor, ['id_sucursal' => $this->sucursal->id, 'alcance_tipo' => 'total']);
    }

    public function test_snapshot_al_iniciar_y_venta_durante_conteo_no_genera_diferencia(): void
    {
        $producto = $this->producto('Fungicida', stock: 10);
        $auditoria = $this->auditoriaIniciada();
        $item = $auditoria->items()->where('id_producto', $producto->id)->firstOrFail();
        $this->assertEquals(10, (float) $item->cantidad_sistema_inicio);

        // Venta durante el conteo: el sistema baja a 8 y físicamente quedan 8.
        app(InventoryManagement::class)->moveStock($this->admin, $this->sucursal, $producto->id, -200, AltaInventario::EVENTO_VENTA);
        $this->management()->capturar($this->auditor, $auditoria, ['id_producto' => $producto->id, 'cantidad' => 8]);
        $this->management()->cerrarConteo($this->auditor, $auditoria);

        $item->refresh();
        $this->assertEquals(10, (float) $item->cantidad_sistema_inicio);
        $this->assertEquals(-2, (float) $item->movimientos_durante);
        $this->assertEquals(8, (float) $item->esperado);
        $this->assertEquals(0, (float) $item->diferencia);
        $this->assertSame(['venta' => '-2.00'], $item->desglose_movimientos);
    }

    public function test_capturas_por_ubicacion_suman_y_anulada_no_cuenta(): void
    {
        $producto = $this->producto('Herbicida', stock: 5);
        $auditoria = $this->auditoriaIniciada();

        $this->management()->capturar($this->auditor, $auditoria, ['id_producto' => $producto->id, 'cantidad' => 3, 'ubicacion' => 'A1']);
        $item = $this->management()->capturar($this->auditor, $auditoria, ['id_producto' => $producto->id, 'cantidad' => 2, 'ubicacion' => 'B2']);
        $this->assertEquals(5, (float) $item->cantidad_contada);

        $conteo = $item->conteos()->where('ubicacion', 'B2')->firstOrFail();
        $item = $this->management()->anular($this->auditor, $auditoria, $conteo->id);
        $this->assertEquals(3, (float) $item->cantidad_contada);
        $this->assertNotNull($conteo->fresh()->anulado_at);
        $this->assertSame(2, $item->conteos()->count());
    }

    public function test_cerrar_con_pendientes_requiere_confirmar_cero(): void
    {
        $contado = $this->producto('Contado', stock: 4);
        $sinContar = $this->producto('SinContar', stock: 6);
        $auditoria = $this->auditoriaIniciada();
        $this->management()->capturar($this->auditor, $auditoria, ['id_producto' => $contado->id, 'cantidad' => 4]);

        try {
            $this->management()->cerrarConteo($this->auditor, $auditoria);
            $this->fail('Debió pedir confirmación.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('pendientes', $e->errors());
        }

        $this->management()->cerrarConteo($this->auditor, $auditoria, true);
        $item = $auditoria->items()->where('id_producto', $sinContar->id)->firstOrFail();
        $this->assertEquals(0, (float) $item->cantidad_contada);
        $this->assertEquals(-6, (float) $item->diferencia);
        $this->assertSame(Auditoria::STATUS_EN_REVISION, $auditoria->fresh()->status);
    }

    public function test_venta_offline_anterior_a_la_captura_sincronizada_despues_no_genera_faltante(): void
    {
        $producto = $this->producto('Semilla', stock: 10);
        $this->travelTo(now()->setTime(9, 0));
        $auditoria = $this->auditoriaIniciada();

        // 10:00 el auditor cuenta 9: una unidad se vendió offline a las 9:30 y aún no sincroniza.
        $this->travelTo(now()->setTime(10, 0));
        $this->management()->capturar($this->auditor, $auditoria, ['id_producto' => $producto->id, 'cantidad' => 9]);

        // 10:15 sincroniza la venta offline.
        $this->travelTo(now()->setTime(10, 15));
        $venta = Venta::factory()->create(['id_sucursal' => $this->sucursal->id, 'id_usuario' => $this->admin->id,
            'id_cliente' => \App\Models\Clientes::factory()->create()->id, 'occurred_at' => now()->setTime(9, 30)]);
        ProductoVenta::factory()->create(['id_venta' => $venta->id, 'id_producto' => $producto->id, 'cantidad' => 1]);
        app(InventoryManagement::class)->moveStock($this->admin, $this->sucursal, $producto->id, -100, AltaInventario::EVENTO_VENTA);

        $this->management()->cerrarConteo($this->auditor, $auditoria);
        $item = $auditoria->items()->where('id_producto', $producto->id)->firstOrFail();
        $this->assertEquals(1, (float) $item->ventas_offline_tardias);
        $this->assertEquals(9, (float) $item->esperado);
        $this->assertEquals(0, (float) $item->diferencia);
    }

    public function test_reconteo_reemplaza_ronda_1_y_finalizar_no_toca_inventario(): void
    {
        $producto = $this->producto('Abono', stock: 7);
        $otro = $this->producto('Otro', stock: 2);
        $auditoria = $this->auditoriaIniciada();
        $item = $this->management()->capturar($this->auditor, $auditoria, ['id_producto' => $producto->id, 'cantidad' => 5]);
        $this->management()->capturar($this->auditor, $auditoria, ['id_producto' => $otro->id, 'cantidad' => 1]);
        $this->management()->cerrarConteo($this->auditor, $auditoria);
        $this->assertEquals(-2, (float) $item->fresh()->diferencia);

        $ledger = AltaInventario::count();
        $this->management()->solicitarReconteo($this->auditor, $auditoria, [$item->id, $auditoria->items()->where('id_producto', $otro->id)->value('id')]);
        $item = $this->management()->capturar($this->auditor, $auditoria, ['id_item' => $item->id, 'cantidad' => 7]);
        $this->assertSame(2, $item->ronda);
        $this->assertEquals(7, (float) $item->cantidad_contada);
        $this->assertEquals(5, (float) $item->contado_r1);
        $this->assertEquals(0, (float) $item->diferencia);

        $this->management()->finalizar($this->auditor, $auditoria);
        $this->assertSame(Auditoria::STATUS_FINALIZADA, $auditoria->fresh()->status);
        // El reconteo no realizado conserva la ronda 1.
        $sinReconteo = $auditoria->items()->where('id_producto', $otro->id)->firstOrFail();
        $this->assertSame(1, $sinReconteo->ronda);
        $this->assertEquals(-1, (float) $sinReconteo->diferencia);
        // El módulo sólo reporta.
        $this->assertSame($ledger, AltaInventario::count());
        $this->assertEquals(7, $this->stock($producto));
    }

    public function test_productos_fuera_de_alcance_y_no_registrados(): void
    {
        $marca = \App\Models\CatMarca::factory()->create();
        $dentro = $this->producto('Dentro', $marca, 3);
        $fuera = $this->producto('Fuera', stock: 4);
        $auditoria = $this->auditoriaIniciada(['alcance_tipo' => 'marcas', 'marcas' => [$marca->id]]);
        $this->assertSame(1, $auditoria->items()->count());

        $this->management()->capturar($this->auditor, $auditoria, ['id_producto' => $dentro->id, 'cantidad' => 3]);
        $noEsperado = $this->management()->capturar($this->auditor, $auditoria, ['id_producto' => $fuera->id, 'cantidad' => 4]);
        $libre = $this->management()->capturar($this->auditor, $auditoria, ['descripcion_libre' => 'Caja sin etiqueta', 'cantidad' => 2]);
        $this->assertTrue($noEsperado->no_esperado);
        $this->assertEquals(4, (float) $noEsperado->cantidad_sistema_inicio);
        $this->assertNull($libre->id_producto);

        $this->management()->cerrarConteo($this->auditor, $auditoria);
        $this->assertEquals(0, (float) $noEsperado->fresh()->diferencia);
        $this->assertEquals(2, (float) $libre->fresh()->diferencia);
    }

    public function test_conteo_ciego_no_expone_existencias(): void
    {
        $producto = $this->producto('Insecticida', stock: 12);
        $auditoria = $this->auditoriaIniciada();
        $this->actingAs($this->auditor);

        $json = $this->getJson(route('auditorias.buscarProducto', ['auditoria' => $auditoria->id, 'q' => 'BC-Insecticida']))
            ->assertOk()->json('data.0');
        $this->assertTrue($json['exacto']);
        foreach (['cantidad_sistema_inicio', 'esperado', 'diferencia', 'cantidad', 'stock'] as $campo) {
            $this->assertArrayNotHasKey($campo, $json);
        }

        $this->postJson(route('auditorias.conteos.store', $auditoria->id), ['id_producto' => $producto->id, 'cantidad' => 11])
            ->assertOk()->assertJsonMissingPath('item.esperado')->assertJsonPath('item.contado', '11.00');

        $this->get(route('auditorias.show', $auditoria->id))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Auditoria/Show')->where('reporte', null));
        $this->get(route('auditorias.reporte.xlsx', $auditoria->id))->assertStatus(409);
    }

    public function test_admin_empresa_consulta_y_cancela_pero_no_captura(): void
    {
        $producto = $this->producto('Foliar', stock: 1);
        $auditoria = $this->auditoriaIniciada();
        $this->actingAs($this->admin);

        $this->get(route('auditorias.index'))->assertOk();
        $this->postJson(route('auditorias.conteos.store', $auditoria->id), ['id_producto' => $producto->id, 'cantidad' => 1])->assertForbidden();
        $this->post(route('auditorias.store'), ['id_sucursal' => $this->otraSucursal->id, 'alcance_tipo' => 'total'])->assertForbidden();

        $this->post(route('auditorias.cancelar', $auditoria->id), ['motivo' => 'Auditor no se presentó'])->assertRedirect();
        $this->assertSame(Auditoria::STATUS_CANCELADA, $auditoria->fresh()->status);
    }

    public function test_auditor_solo_ve_auditorias_donde_participa(): void
    {
        $this->producto('Visible', stock: 1);
        $auditoria = $this->auditoriaIniciada();
        $ajeno = $this->crearAuditor([[$this->empresa->id, null]]);

        $this->actingAs($ajeno)->get(route('auditorias.show', $auditoria->id))->assertForbidden();
        $this->actingAs($ajeno)->get(route('auditorias.index'))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('auditorias.total', 0));
    }

    public function test_paginas_principales_renderizan(): void
    {
        $this->producto('Render', stock: 2);
        $auditoria = $this->auditoriaIniciada();
        $this->actingAs($this->auditor);

        $this->get(route('auditorias.create'))->assertOk();
        $this->get(route('auditorias.captura', $auditoria->id))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Auditoria/Captura')->where('progreso.total', 1));
        $this->getJson(route('auditorias.alcance.sucursales', ['empresa' => $this->empresa->id]))->assertOk()
            ->assertJsonFragment(['id' => $this->sucursal->id, 'auditoria_activa' => $auditoria->folio])
            ->assertJsonFragment(['id' => $this->otraSucursal->id, 'auditoria_activa' => null]);
        $this->get(route('auditorias.historico', ['buscar' => 1]))->assertOk();
    }
}
