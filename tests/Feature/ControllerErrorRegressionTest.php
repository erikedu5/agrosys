<?php

namespace Tests\Feature;

use App\Models\AltaInventario;
use App\Models\CatClasificacion;
use App\Models\CatMarca;
use App\Models\Clientes;
use App\Models\Compras;
use App\Models\ComprasAbonos;
use App\Models\Empresa;
use App\Models\EventLog;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use App\Services\PurchaseIdempotency;
use Tests\TestCase;

class ControllerErrorRegressionTest extends TestCase
{
    use RefreshDatabase;

    private Sucursales $branch;
    private Producto $product;

    protected function setUp(): void
    {
        parent::setUp();
        $empresa = Empresa::factory()->create();
        $this->branch = Sucursales::factory()->create(['id_empresa' => $empresa->id]);
        $user = User::factory()->create(['tipo' => 'admin', 'id_sucursal' => $this->branch->id, 'id_empresa' => $empresa->id]);
        $this->actingAs($user);
        // Focus on controller behavior independently of subscription and role gates.
        $this->withoutMiddleware([
            \App\Http\Middleware\EnsureEmpresaSubscription::class,
            \App\Http\Middleware\CheckRole::class,
            \App\Http\Middleware\CheckSucursalSelection::class,
        ]);
        $this->product = Producto::factory()->create([
            'id_empresa' => $empresa->id,
            'id_usuario' => $user->id,
            'id_marca' => CatMarca::factory()->create()->id,
            'id_clasificacion' => CatClasificacion::factory()->create()->id,
            'tamano' => '1 L',
        ]);
    }

    private function purchasePayload(): array
    {
        return [
            'idempotency_key' => (string) Str::uuid(),
            'proveedor' => 'Proveedor',
            'fecha_compra' => '2026-10-06',
            'total_compra' => 50,
            'total_credito' => 0,
            'status' => 'pagada',
            'productos' => [['id' => $this->product->id, 'cantidad' => 5, 'precio_compra' => 10]],
        ];
    }

    public function test_customer_delete_targets_only_the_route_customer(): void
    {
        $customer = Clientes::factory()->create(['id_sucursal' => $this->branch->id, 'activo' => true]);
        $other = Clientes::factory()->create(['id_sucursal' => $this->branch->id, 'activo' => true]);

        $this->delete(route('cliente.destroy', $customer))->assertRedirect(route('cliente.index'));

        $this->assertFalse($customer->fresh()->activo);
        $this->assertTrue($other->fresh()->activo);
        $this->delete(route('cliente.destroy', $customer))->assertNotFound();
    }

    public function test_missing_customer_update_returns_404(): void
    {
        $this->putJson(route('cliente.update', 999999), [
            'nombre' => 'Cliente', 'porcentaje_descuento' => 0, 'requiereFactura' => false,
        ])->assertNotFound();
    }

    public function test_brand_update_uses_the_route_id_and_missing_brand_returns_404(): void
    {
        $brand = CatMarca::factory()->create();
        $this->putJson(route('marca.update', $brand), ['nombre' => 'Actualizada'])->assertRedirect();
        $this->assertSame('Actualizada', $brand->fresh()->nombre);
        $this->putJson(route('marca.update', 999999), ['nombre' => 'Marca'])->assertNotFound();
    }

    public function test_purchase_without_previous_stock_or_payments_succeeds(): void
    {
        $this->postJson(route('compra.store'), $this->purchasePayload())->assertOk();

        $this->assertDatabaseCount('compras', 1);
        $stock = \App\Models\AltaInventario::where('id_producto', $this->product->id)
            ->where('id_sucursal', $this->branch->id)->sole();
        $this->assertEquals(0, $stock->cantidad_actual);
        $this->assertEquals(5, $stock->cantidad_nueva);
    }

    public function test_invalid_purchase_inputs_return_validation_errors_without_writes(): void
    {
        foreach ([['productos' => 'invalid'], ['abonos' => null], ['fecha_compra' => 'invalid'], ['productos' => [['id' => $this->product->id]]]] as $invalid) {
            $this->postJson(route('compra.store'), array_replace($this->purchasePayload(), $invalid))->assertUnprocessable();
        }
        $this->assertDatabaseCount('compras', 0);
        $this->assertDatabaseCount('compras_productos', 0);
        $this->assertDatabaseCount('alta_inventarios', 0);
    }

    public function test_missing_purchase_update_and_show_return_404(): void
    {
        $this->putJson(route('compra.update', 999999), $this->purchasePayload())->assertNotFound();
        $this->getJson(route('compra.show', 999999))->assertNotFound();
    }

    public function test_purchase_update_uses_route_id_without_a_body_id(): void
    {
        $purchase = $this->createPurchase();
        $this->putJson(route('compra.update', $purchase), array_replace($this->purchasePayload(), [
            'abonos' => [['cantidad_abonada' => 50]],
        ]))->assertOk();

        $this->assertEquals(0, $purchase->fresh()->total_credito);
        $this->assertDatabaseHas('compras_abonos', ['id_compra' => $purchase->id, 'cantidad_abonada' => 50]);
    }

    public function test_failed_purchase_update_rolls_back_credit_changes(): void
    {
        $purchase = $this->createPurchase();
        ComprasAbonos::creating(function () {
            throw new \RuntimeException('Simulated payment failure');
        });
        try {
            $this->putJson(route('compra.update', $purchase), array_replace($this->purchasePayload(), [
                'abonos' => [['cantidad_abonada' => 50]],
            ]))->assertStatus(500);
        } finally {
            ComprasAbonos::flushEventListeners();
        }

        $this->assertEquals(50, $purchase->fresh()->total_credito);
        $this->assertSame('adeudo', $purchase->fresh()->status);
        $this->assertDatabaseCount('compras_abonos', 0);
    }

    private function createPurchase(): Compras
    {
        return Compras::create([
            'proveedor' => 'Proveedor', 'fecha_compra' => '2026-10-06',
            'fecha_credito' => '2026-11-05', 'total_compra' => 50,
            'total_credito' => 50, 'status' => 'adeudo', 'id_sucursal' => $this->branch->id,
        ]);
    }

    public function test_failure_during_payment_creation_rolls_back_purchase_and_stock(): void
    {
        $payload = array_replace($this->purchasePayload(), ['abonos' => [['cantidad_abonada' => 50]]]);
        ComprasAbonos::creating(function () {
            throw new \RuntimeException('Simulated payment failure');
        });
        try {
            $this->postJson(route('compra.store'), $payload)->assertStatus(500);
        } finally {
            ComprasAbonos::flushEventListeners();
        }

        $this->assertDatabaseCount('compras', 0);
        $this->assertDatabaseCount('compras_productos', 0);
        $this->assertDatabaseCount('alta_inventarios', 0);
        $this->assertDatabaseCount('compras_abonos', 0);

        // Rollback must also release the key so the exact request can succeed later.
        $this->postJson(route('compra.store'), $payload)->assertOk();
        $this->postJson(route('compra.store'), $payload)->assertOk();
        $this->assertDatabaseCount('compras', 1);
        $this->assertDatabaseCount('compras_abonos', 1);
        $this->assertDatabaseCount('alta_inventarios', 1);
    }

    public function test_repeated_purchase_returns_original_id_without_duplicate_stock_or_payments(): void
    {
        $payload = array_replace($this->purchasePayload(), ['abonos' => [['cantidad_abonada' => 50]]]);
        $first = $this->postJson(route('compra.store'), $payload, ['X-Inertia' => 'true'])->assertOk();
        $second = $this->postJson(route('compra.store'), $payload, ['X-Inertia' => 'true'])->assertOk();

        $this->assertSame($first->json('props.compra_registrada'), $second->json('props.compra_registrada'));
        $this->assertNotNull($first->json('props.compra_registrada'));
        $this->assertDatabaseCount('compras', 1);
        $this->assertDatabaseCount('compras_productos', 1);
        $this->assertDatabaseCount('compras_abonos', 1);
        $this->assertDatabaseCount('alta_inventarios', 1);
        $this->assertEquals(5, AltaInventario::first()->cantidad_nueva);
    }

    public function test_reusing_key_with_different_data_returns_409_even_in_production(): void
    {
        config(['app.debug' => false]);
        $payload = $this->purchasePayload();
        $this->postJson(route('compra.store'), $payload)->assertOk();
        $payload['productos'][0]['cantidad'] = 6;

        $this->postJson(route('compra.store'), $payload)->assertStatus(409)->assertJsonStructure(['message']);
        $this->post(route('compra.store'), $payload, ['X-Inertia' => 'true'])
            ->assertStatus(409)->assertJsonPath('props.status', 409);
        $this->post(route('compra.store'), $payload)->assertStatus(409)->assertSee('Revisar compras');
        $this->assertDatabaseCount('compras', 1);
        $this->assertDatabaseCount('alta_inventarios', 1);
    }

    public function test_numeric_strings_and_form_metadata_do_not_change_the_fingerprint(): void
    {
        $payload = $this->purchasePayload();
        $this->postJson(route('compra.store'), $payload)->assertOk();
        $payload['total_compra'] = '50.00';
        $payload['productos'][0]['cantidad'] = '5';
        $payload['productos'][0]['nombre'] = 'Etiqueta modificada';
        $payload['abonos'] = [];
        $payload['idempotency_key'] = strtoupper($payload['idempotency_key']);
        $this->postJson(route('compra.store'), $payload)->assertOk();
        $this->assertDatabaseCount('compras', 1);
    }

    public function test_company_scope_allows_same_uuid_for_another_company(): void
    {
        $payload = $this->purchasePayload();
        $this->postJson(route('compra.store'), $payload)->assertOk();
        $company = Empresa::factory()->create();
        $branch = Sucursales::factory()->create(['id_empresa' => $company->id]);
        $user = User::factory()->create(['tipo' => 'admin', 'id_sucursal' => $branch->id, 'id_empresa' => $company->id]);
        $product = $this->product->replicate();
        $product->id_empresa = $company->id;
        $product->id_usuario = $user->id;
        $product->save();
        $payload['productos'][0]['id'] = $product->id;
        $this->actingAs($user)->postJson(route('compra.store'), $payload)->assertOk();
        $this->assertDatabaseCount('compras', 2);
    }

    public function test_same_company_key_cannot_be_replayed_in_another_branch(): void
    {
        $payload = $this->purchasePayload();
        $this->postJson(route('compra.store'), $payload)->assertOk();
        $branch = Sucursales::factory()->create(['id_empresa' => $this->branch->id_empresa]);
        $user = User::factory()->create(['tipo' => 'admin', 'id_sucursal' => $branch->id, 'id_empresa' => $branch->id_empresa]);
        $this->actingAs($user)->postJson(route('compra.store'), $payload)->assertStatus(409);
        $this->assertDatabaseCount('compras', 1);
        $this->assertDatabaseCount('alta_inventarios', 1);
    }

    public function test_purchase_committed_between_lookup_and_insert_is_reused(): void
    {
        $payload = $this->purchasePayload();
        $winner = null;
        // Simulate a competing transaction committing after the initial lookup.
        EventLog::created(function () use ($payload, &$winner) {
            $winner = Compras::create([
                'proveedor' => $payload['proveedor'], 'fecha_compra' => $payload['fecha_compra'],
                'fecha_credito' => '2026-11-05', 'total_compra' => 50, 'total_credito' => 0,
                'status' => 'pagada', 'id_sucursal' => $this->branch->id,
                'id_empresa' => $this->branch->id_empresa, 'idempotency_key' => $payload['idempotency_key'],
                'request_hash' => PurchaseIdempotency::fingerprint($payload, $this->branch->id),
            ]);
        });
        try {
            $response = $this->postJson(route('compra.store'), $payload, ['X-Inertia' => 'true'])->assertOk();
        } finally {
            EventLog::flushEventListeners();
        }
        $this->assertSame($winner->id, $response->json('props.compra_registrada'));
        $this->assertDatabaseCount('compras', 1);
        // The losing request must never get past its INSERT to touch stock.
        $this->assertDatabaseCount('compras_productos', 0);
        $this->assertDatabaseCount('alta_inventarios', 0);
    }

    public function test_purchase_creation_requires_a_uuid(): void
    {
        $payload = $this->purchasePayload();
        unset($payload['idempotency_key']);
        $this->postJson(route('compra.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');
        $payload['idempotency_key'] = 'invalid';
        $this->postJson(route('compra.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');
        $this->assertDatabaseCount('compras', 0);
    }
}
