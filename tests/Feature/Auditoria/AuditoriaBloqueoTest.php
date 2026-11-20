<?php

namespace Tests\Feature\Auditoria;

use App\Models\AltaInventario;
use App\Models\Transferencia;
use App\Models\User;
use App\Services\InventoryManagement;
use App\Services\Pos\PosAccess;
use App\Services\PurchaseManagement;
use App\Services\TransferManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AuditoriaBloqueoTest extends TestCase
{
    use RefreshDatabase;
    use AuditoriaHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->escenario();
    }

    private function assertBloqueado(callable $accion): void
    {
        try {
            $accion();
            $this->fail('La operación debió bloquearse por auditoría.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('auditoria', $e->errors());
            $this->assertStringContainsString('auditoría', $e->errors()['auditoria'][0]);
        }
    }

    public function test_movimientos_no_venta_bloqueados_y_ventas_permitidas(): void
    {
        $producto = $this->producto('Bloqueado', stock: 10);
        $this->producto('Centro', stock: 5, sucursal: $this->otraSucursal);
        $auditoria = $this->auditoriaIniciada();
        $inventario = app(InventoryManagement::class);

        $this->assertBloqueado(fn () => $inventario->addStock($this->admin, $this->sucursal, $producto->id, 1));
        $this->assertBloqueado(fn () => $inventario->destructive($this->admin, $this->sucursal, $producto->id, 'reset'));
        $this->assertBloqueado(fn () => $inventario->dose($this->admin, $this->sucursal, $producto->id, ['contenido' => 1000, 'medida' => 250, 'unidad' => 'ml']));
        $this->assertBloqueado(fn () => app(TransferManagement::class)->create($this->admin, $this->otraSucursal,
            ['id_sucursal_destino' => $this->sucursal->id, 'productos' => [['id' => $producto->id, 'cantidad' => 1]]]));
        $this->assertBloqueado(fn () => app(PurchaseManagement::class)->record($this->admin, $this->sucursal, [
            'idempotency_key' => (string) Str::uuid(), 'proveedor' => 'Prov', 'fecha_compra' => now()->toDateString(),
            'total_compra' => 10, 'total_credito' => 0, 'status' => 'pagado', 'productos' => [['id' => $producto->id, 'cantidad' => 1, 'precio_compra' => 10]]]));
        // Respaldo a nivel ledger para escritores directos (p. ej. devoluciones).
        $this->assertBloqueado(fn () => AltaInventario::create(['cantidad_actual' => 10, 'cantidad_nueva' => 11, 'id_usuario' => $this->admin->id,
            'id_producto' => $producto->id, 'id_sucursal' => $this->sucursal->id, 'tipo_evento' => AltaInventario::EVENTO_ALTA]));

        // Ventas siguen; otra sucursal no se afecta.
        $inventario->moveStock($this->admin, $this->sucursal, $producto->id, -100, AltaInventario::EVENTO_VENTA);
        $this->assertEquals(9, $this->stock($producto));
        $inventario->addStock($this->admin, $this->otraSucursal, $producto->id, 2);
        $this->assertEquals(2, $this->stock($producto, $this->otraSucursal));

        // Al finalizar se libera.
        $this->management()->capturar($this->auditor, $auditoria, ['id_producto' => $producto->id, 'cantidad' => 9]);
        $this->management()->cerrarConteo($this->auditor, $auditoria, true);
        $this->assertBloqueado(fn () => $inventario->addStock($this->admin, $this->sucursal, $producto->id, 1));
        $this->management()->finalizar($this->auditor, $auditoria);
        $inventario->addStock($this->admin, $this->sucursal, $producto->id, 1);
        $this->assertEquals(10, $this->stock($producto));
    }

    public function test_recibir_transferencia_pendiente_bloqueado_y_cancelar_libera(): void
    {
        $producto = $this->producto('Transito', stock: 5, sucursal: $this->otraSucursal);
        $transfer = app(TransferManagement::class)->create($this->admin, $this->otraSucursal,
            ['id_sucursal_destino' => $this->sucursal->id, 'productos' => [['id' => $producto->id, 'cantidad' => 2]]]);
        $auditoria = $this->auditoriaIniciada();

        $this->assertBloqueado(fn () => app(TransferManagement::class)->receive($this->admin, $this->sucursal, $transfer->id));

        $this->management()->cancelar($this->admin, $auditoria, 'Se reprograma el conteo');
        app(TransferManagement::class)->receive($this->admin, $this->sucursal, $transfer->id);
        $this->assertSame('completado', Transferencia::find($transfer->id)->status);
    }

    public function test_iniciar_avisa_transferencias_pendientes_y_reporta_transito(): void
    {
        $producto = $this->producto('EnCamino', stock: 5);
        app(TransferManagement::class)->create($this->admin, $this->sucursal,
            ['id_sucursal_destino' => $this->otraSucursal->id, 'productos' => [['id' => $producto->id, 'cantidad' => 2]]]);
        $auditoria = $this->management()->crear($this->auditor, ['id_sucursal' => $this->sucursal->id, 'alcance_tipo' => 'total']);

        try {
            $this->management()->iniciar($this->auditor, $auditoria);
            $this->fail('Debió avisar de transferencias pendientes.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('transito', $e->errors());
        }

        $this->management()->iniciar($this->auditor, $auditoria, true);
        // Físicamente ya salieron 2 unidades: el reporte lo explica como tránsito.
        $this->management()->capturar($this->auditor, $auditoria, ['id_producto' => $producto->id, 'cantidad' => 3]);
        $this->management()->cerrarConteo($this->auditor, $auditoria);
        $item = $auditoria->items()->where('id_producto', $producto->id)->firstOrFail();
        $this->assertEquals(-2, (float) $item->diferencia);
        $this->assertEquals(2, (float) $item->transito_salida);
    }

    public function test_web_muestra_error_de_bloqueo_y_comparte_aviso(): void
    {
        $producto = $this->producto('Web', stock: 3);
        $auditoria = $this->auditoriaIniciada();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureEmpresaSubscription::class);

        $this->actingAs($this->admin)->from(route('transferencias.create'))
            ->post(route('transferencias.store'), ['id_sucursal_destino' => $this->otraSucursal->id, 'productos' => [['id' => $producto->id, 'cantidad' => 1]]])
            ->assertSessionHasErrors('auditoria');

        $this->actingAs($this->admin)->get(route('auditorias.index'))
            ->assertInertia(fn ($page) => $page->where('sucursalActiva.auditoria_activa', $auditoria->folio));
    }

    public function test_pos_pierde_permisos_de_movimiento_durante_auditoria(): void
    {
        $this->producto('Pos', stock: 1);
        $vendedor = User::factory()->create(['tipo' => 'admin', 'id_sucursal' => $this->sucursal->id, 'id_empresa' => $this->empresa->id]);
        $bloqueables = ['purchase.create', 'inventory.add', 'inventory.dose', 'transfer.create', 'transfer.receive'];
        $antes = app(PosAccess::class)->permissions($vendedor, $this->sucursal);
        foreach ($bloqueables as $permiso) {
            $this->assertContains($permiso, $antes);
        }

        $this->auditoriaIniciada();
        $durante = app(PosAccess::class)->permissions($vendedor, $this->sucursal->fresh());
        foreach ($bloqueables as $permiso) {
            $this->assertNotContains($permiso, $durante);
        }
        $this->assertContains('sale.create', $durante);
        $this->assertContains('transfer.create', app(PosAccess::class)->permissions($vendedor, $this->otraSucursal));
    }
}
