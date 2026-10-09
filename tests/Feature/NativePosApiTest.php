<?php

namespace Tests\Feature;

use App\Models\AbonoCuenta;
use App\Models\AltaInventario;
use App\Models\CatClasificacion;
use App\Models\CatMarca;
use App\Models\Clientes;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Tests\TestCase;

class NativePosApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Sucursales $branch;

    private Clientes $customer;

    private Producto $product;

    private string $device;

    private string $token;

    private array $lease;

    protected function setUp(): void
    {
        parent::setUp();
        config(['pos.enabled' => true, 'offline.require_activation' => false]);
        $company = Empresa::factory()->create();
        $this->branch = Sucursales::factory()->create(['id_empresa' => $company->id]);
        $this->user = User::factory()->create(['tipo' => 'vendedor', 'id_empresa' => $company->id, 'id_sucursal' => $this->branch->id, 'password' => Hash::make('test-password')]);
        $this->customer = Clientes::factory()->create(['id_sucursal' => $this->branch->id, 'activo' => true, 'porcentaje_descuento' => 0]);
        $this->branch->update(['id_cliente_publico' => $this->customer->id]);
        $this->product = Producto::factory()->create(['id_empresa' => $company->id, 'id_usuario' => $this->user->id, 'id_marca' => CatMarca::factory()->create()->id, 'id_clasificacion' => CatClasificacion::factory()->create()->id, 'tamano' => '1L', 'precio_unitario' => 50, 'precio_ieps' => 50]);
        AltaInventario::create(['id_producto' => $this->product->id, 'id_sucursal' => $this->branch->id, 'id_usuario' => $this->user->id, 'cantidad_actual' => 10, 'cantidad_nueva' => 10]);
        $this->device = (string) Str::uuid();
    }

    private function credentials(): array
    {
        return ['email' => $this->user->email, 'password' => 'test-password', 'device_id' => $this->device, 'device_name' => 'Test POS'];
    }

    private function login(): void
    {
        Auth::forgetGuards();
        $this->token = $this->postJson('/api/v1/pos/auth/login', $this->credentials())->assertOk()->json('token');
    }

    private function api(string $method, string $path, array $data = [])
    {
        Auth::forgetGuards();

        return $this->json($method, '/api/v1/pos/'.$path, $data, ['Authorization' => 'Bearer '.$this->token]);
    }

    private function context(): array
    {
        return ['device_id' => $this->device, 'branch_id' => (string) $this->branch->id];
    }

    private function activate(): void
    {
        $this->login();
        $this->lease = $this->api('POST', 'devices/activate', $this->context())->assertOk()->json('offlineLease');
    }

    private function operation(string $type = 'Contado', string $payment = '100.00', int $sequence = 1): array
    {
        return ['operation_id' => (string) Str::uuid(), 'aggregate_id' => (string) Str::uuid(), 'aggregate_type' => 'sale', 'event_type' => 'SALE_COMPLETED', 'sequence' => $sequence, 'occurred_at' => now()->toIso8601String(), 'offline_lease_id' => $this->lease['claims']['leaseId'], 'payload' => ['customerId' => (string) $this->customer->id, 'saleType' => $type, 'total' => '100.00', 'localFolio' => 'LOCAL-'.$sequence, 'items' => [['productId' => (string) $this->product->id, 'quantity' => '2.00', 'unitPrice' => '50.00', 'total' => '100.00']], 'payments' => [['method' => 'cash', 'amount' => $payment]]]];
    }

    private function push(array $operations)
    {
        return $this->api('POST', 'sync/push', [...$this->context(), 'schemaVersion' => 2, 'operations' => $operations]);
    }

    public function test_login_token_logout_and_json_errors_without_accept_header(): void
    {
        $this->post('/api/v1/pos/auth/login', [...$this->credentials(), 'password' => 'bad'])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->login();
        $this->api('GET', 'branches')->assertOk()->assertJsonPath('branches.0.id', (string) $this->branch->id);
        $this->api('POST', 'auth/logout')->assertOk();
        $this->api('GET', 'branches')->assertUnauthorized();
        Auth::forgetGuards();
        $this->get('/api/v1/pos/branches')->assertUnauthorized()->assertJsonStructure(['message']);
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/pos/auth/login', [...$this->credentials(), 'password' => 'bad'])->assertUnprocessable();
        }
        $this->postJson('/api/v1/pos/auth/login', $this->credentials())->assertStatus(429);
    }

    public function test_second_factor_challenge_cannot_be_reused(): void
    {
        $this->user->forceFill(['two_factor_secret' => Crypt::encrypt('secret'), 'two_factor_confirmed_at' => now()])->save();
        $provider = \Mockery::mock(TwoFactorAuthenticationProvider::class);
        $provider->shouldReceive('verify')->once()->with('secret', '123456')->andReturn(true);
        $this->app->instance(TwoFactorAuthenticationProvider::class, $provider);
        $challenge = $this->postJson('/api/v1/pos/auth/login', $this->credentials())->assertOk()->assertJsonPath('requiresTwoFactor', true)->json('challenge');
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->postJson('/api/v1/pos/auth/verify-2fa', ['challenge' => $challenge, 'code' => '123456'])->assertOk()->assertJsonStructure(['token']);
        $this->postJson('/api/v1/pos/auth/verify-2fa', ['challenge' => $challenge, 'code' => '123456'])->assertUnprocessable();
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_failed_second_factor_attempts_persist_and_recovery_code_is_consumed(): void
    {
        $this->user->forceFill(['two_factor_secret' => Crypt::encrypt('secret'), 'two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => Crypt::encrypt(json_encode(['recovery-one']))])->save();
        $provider = \Mockery::mock(TwoFactorAuthenticationProvider::class);
        $provider->shouldReceive('verify')->once()->andReturn(false);
        $this->app->instance(TwoFactorAuthenticationProvider::class, $provider);
        $challenge = $this->postJson('/api/v1/pos/auth/login', $this->credentials())->json('challenge');
        $this->postJson('/api/v1/pos/auth/verify-2fa', ['challenge' => $challenge, 'code' => '000000'])->assertUnprocessable();
        $this->assertSame(1, DB::table('pos_login_challenges')->first()->attempts);
        $this->postJson('/api/v1/pos/auth/verify-2fa', ['challenge' => $challenge, 'recovery_code' => 'recovery-one'])->assertOk();
        $this->assertNotContains('recovery-one', $this->user->fresh()->recoveryCodes());
    }

    public function test_blocked_subscription_and_inactive_branch_are_denied(): void
    {
        $this->branch->empresa->update(['manual_subscription_blocked' => true]);
        $this->postJson('/api/v1/pos/auth/login', $this->credentials())->assertStatus(402);
        $this->branch->empresa->update(['manual_subscription_blocked' => false]);
        $this->branch->delete();
        $this->postJson('/api/v1/pos/auth/login', $this->credentials())->assertForbidden();
    }

    public function test_generic_api_token_and_wrong_device_are_denied(): void
    {
        $this->token = $this->user->createToken('other')->plainTextToken;
        $this->api('GET', 'branches')->assertForbidden();
        $this->login();
        $this->api('POST', 'devices/activate', [...$this->context(), 'device_id' => (string) Str::uuid()])->assertForbidden();
    }

    public function test_signed_lease_and_revocation_cannot_be_undone_by_activation_or_renewal(): void
    {
        $this->activate();
        $this->assertTrue(sodium_crypto_sign_verify_detached(base64_decode($this->lease['signature']), $this->lease['payload'], base64_decode($this->lease['publicKey'])));
        $this->assertSame($this->lease['claims'], json_decode($this->lease['payload'], true));
        $this->api('POST', 'devices/revoke', $this->context())->assertOk();
        $this->api('POST', 'devices/renew', $this->context())->assertForbidden();
        $this->api('POST', 'devices/activate', $this->context())->assertForbidden();
        $this->api('POST', 'bootstrap', $this->context())->assertForbidden();
    }

    public function test_inventory_permissions_follow_roles_and_movements_are_scoped(): void
    {
        foreach (['vendedor', 'inventario', 'admin', 'adminEmpresa', 'superAdmin'] as $role) {
            $this->user->update(['tipo' => $role]);
            $permissions = app(\App\Services\Pos\PosAccess::class)->permissions($this->user, $this->branch);
            $this->assertSame($role !== 'vendedor', in_array('inventory.read', $permissions, true));
            $this->assertSame($role !== 'inventario', in_array('sale.create', $permissions, true));
            $this->assertSame($role !== 'inventario', in_array('customer.read', $permissions, true));
            $this->assertSame($role !== 'inventario', in_array('sale.history', $permissions, true));
        }
        $this->user->update(['tipo' => 'inventario']);
        $this->activate();
        $this->api('POST', "inventory/{$this->product->id}/movements", $this->context())->assertOk()
            ->assertJsonCount(1, 'movements')->assertJsonPath('movements.0.after', '10.00');
        $otherCompany = Empresa::factory()->create();
        $otherProduct = $this->product->replicate();
        $otherProduct->id_empresa = $otherCompany->id;
        $otherProduct->save();
        $this->api('POST', "inventory/{$otherProduct->id}/movements", $this->context())->assertNotFound();
        $otherBranch = Sucursales::factory()->create(['id_empresa' => $this->branch->id_empresa]);
        $this->api('POST', "inventory/{$this->product->id}/movements", [...$this->context(), 'branch_id' => $otherBranch->id])->assertForbidden();
        $this->user->update(['tipo' => 'vendedor']);
        $this->api('POST', "inventory/{$this->product->id}/movements", $this->context())->assertForbidden();
    }

    public function test_heartbeat_refreshes_signed_permissions_after_role_change(): void
    {
        $this->activate();
        $context = [...$this->context(), 'lease_id' => $this->lease['claims']['leaseId']];
        $this->api('POST', 'device/heartbeat', $context)->assertOk()->assertJsonMissingPath('offlineLease');
        $this->user->update(['tipo' => 'inventario']);
        $response = $this->api('POST', 'device/heartbeat', $context)->assertOk();
        $lease = $response->json('offlineLease');
        $this->assertContains('inventory.read', $lease['claims']['permissions']);
        $this->assertNotContains('sale.create', $lease['claims']['permissions']);
        $this->assertTrue(sodium_crypto_sign_verify_detached(base64_decode($lease['signature']), $lease['payload'], base64_decode($lease['publicKey'])));
        $this->api('POST', 'device/heartbeat', [...$this->context(), 'lease_id' => $lease['claims']['leaseId']])->assertOk()->assertJsonMissingPath('offlineLease');
    }

    private function inventoryProductInput(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'nombre' => 'Nuevo insumo', 'tamano' => '2L',
            'id_clasificacion' => (string) $this->product->id_clasificacion, 'id_marca' => (string) $this->product->id_marca,
            'precio_ieps' => '60.00', 'precio_unitario' => '40.00', 'ieps' => '50.25', 'barcode' => 'NEW-001', 'ingrediente_activo' => 'Prueba'];
    }

    public function test_native_inventory_create_edit_prices_and_retries_are_atomic(): void
    {
        $this->user->update(['tipo' => 'inventario']);
        $this->activate();
        $this->api('POST', 'inventory/options', $this->context())->assertOk()->assertJsonPath('manageCosts', true);
        $input = $this->inventoryProductInput();
        $first = $this->api('POST', 'inventory/products', [...$this->context(), ...$input])->assertOk();
        $id = $first->json('productId');
        $this->api('POST', 'inventory/products', [...$this->context(), ...$input])->assertOk()->assertExactJson($first->json());
        $this->assertSame(1, Producto::where('nombre', 'Nuevo insumo')->count());
        $this->assertSame(1, AltaInventario::where('id_producto', $id)->count());
        $this->assertSame('50.25', (string) Producto::findOrFail($id)->ieps);
        $this->api('POST', 'inventory/products', [...$this->context(), ...$input, 'nombre' => 'Otro'])->assertConflict();
        $detail = $this->api('POST', "inventory/{$id}/detail", $this->context())->assertOk()->json('product');
        $edit = [...$input, 'operation_id' => (string) Str::uuid(), 'nombre' => 'Insumo editado', 'version' => $detail['version']];
        $this->api('POST', "inventory/{$id}/update", [...$this->context(), ...$edit])->assertOk();
        $this->api('POST', "inventory/{$id}/update", [...$this->context(), ...$edit])->assertOk();
        $this->api('POST', "inventory/{$id}/update", [...$this->context(), ...$edit, 'operation_id' => (string) Str::uuid(), 'nombre' => 'Obsoleto'])->assertConflict();
        $this->assertSame('Insumo editado', Producto::findOrFail($id)->nombre);
        $version = $this->api('POST', "inventory/{$id}/detail", $this->context())->json('product.version');
        $prices = ['operation_id' => (string) Str::uuid(), 'version' => $version, 'precio_ieps' => '75.00'];
        $this->api('POST', "inventory/{$id}/prices", [...$this->context(), ...$prices])->assertOk();
        $this->api('POST', "inventory/{$id}/prices", [...$this->context(), ...$prices])->assertOk();
        $this->assertEquals(75, Producto::findOrFail($id)->precio_ieps);
        $this->api('POST', 'inventory/products', [...$this->context(), ...$input, 'operation_id' => (string) Str::uuid(), 'id_marca' => '999999'])->assertUnprocessable();
        $this->api('POST', 'inventory/products', [...$this->context(), ...$input, 'operation_id' => (string) Str::uuid(), 'precio_ieps' => '2.001'])->assertUnprocessable();
        $this->api('POST', 'bootstrap', $this->context())->assertOk()->assertJsonCount(2, 'products');
    }

    public function test_native_stock_add_replay_does_not_duplicate_and_matches_web_stock(): void
    {
        $this->user->update(['tipo' => 'inventario']);
        $this->activate();
        $id = $this->product->id;
        $input = ['operation_id' => (string) Str::uuid(), 'cantidad' => '1.25'];
        $this->api('POST', "inventory/{$id}/add", [...$this->context(), ...$input])->assertOk()->assertJsonPath('quantity', '11.25');
        $this->api('POST', "inventory/{$id}/add", [...$this->context(), ...$input])->assertOk()->assertJsonPath('quantity', '11.25');
        $this->assertSame(2, AltaInventario::where('id_producto', $id)->count());
        $this->api('POST', "inventory/{$id}/add", [...$this->context(), ...$input, 'cantidad' => '2.00'])->assertConflict();
        foreach (['0', '-1', '1.001', '1000000'] as $quantity) {
            $this->api('POST', "inventory/{$id}/add", [...$this->context(), 'operation_id' => (string) Str::uuid(), 'cantidad' => $quantity])->assertUnprocessable();
        }
        Auth::forgetGuards();
        $this->actingAs($this->user)->post('/inventario/addInventario', ['id' => $id, 'cantidad' => '1.75'])->assertRedirect();
        $this->assertEquals(13, AltaInventario::where('id_producto', $id)->orderByDesc('id')->first()->cantidad_nueva);
        $this->api('POST', 'bootstrap', $this->context())->assertOk()->assertJsonPath('products.0.serverQuantity', '13.00');
    }

    public function test_inventory_writes_and_costs_follow_current_role_company_and_block(): void
    {
        $this->activate();
        $input = $this->inventoryProductInput();
        $this->api('POST', 'inventory/products', [...$this->context(), ...$input])->assertForbidden();
        $this->user->update(['tipo' => 'admin']);
        $this->branch->empresa->update(['mostrar_campos_precio' => false]);
        $id = $this->product->id;
        $detail = $this->api('POST', "inventory/{$id}/detail", $this->context())->assertOk()->assertJsonMissingPath('product.precio_unitario')->json('product');
        $this->api('POST', 'bootstrap', $this->context())->assertOk()->assertJsonMissingPath('products.0.unitPrice');
        $before = $this->product->ieps;
        $this->api('POST', "inventory/{$id}/prices", [...$this->context(), 'operation_id' => (string) Str::uuid(), 'version' => $detail['version'],
            'precio_ieps' => '70.00', 'precio_unitario' => '1.00', 'ieps' => '90.00'])->assertOk();
        $this->assertEquals(50, $this->product->fresh()->precio_unitario);
        $this->assertEquals($before, $this->product->fresh()->ieps);
        $otherCompany = Empresa::factory()->create();
        $other = $this->product->replicate(); $other->id_empresa = $otherCompany->id; $other->save();
        $this->api('POST', "inventory/{$other->id}/add", [...$this->context(), 'operation_id' => (string) Str::uuid(), 'cantidad' => '1.00'])->assertNotFound();
        $this->branch->empresa->update(['ventas_bloqueadas' => true]);
        $this->api('POST', "inventory/{$id}/add", [...$this->context(), 'operation_id' => (string) Str::uuid(), 'cantidad' => '1.00'])->assertForbidden();
        $this->branch->empresa->update(['ventas_bloqueadas' => false]);
        $this->user->update(['tipo' => 'adminEmpresa']);
        $this->api('POST', 'inventory/options', $this->context())->assertOk()->assertJsonPath('manageCosts', true);
        $this->api('POST', "inventory/{$id}/detail", $this->context())->assertOk()->assertJsonPath('product.precio_unitario', '50.00');
    }

    public function test_manual_activation_is_persisted_without_issuing_lease(): void
    {
        config(['offline.require_activation' => true]);
        $this->login();
        $this->api('POST', 'devices/activate', $this->context())->assertForbidden();
        $this->assertDatabaseHas('offline_devices', ['id' => $this->device, 'authorized' => false]);
        $this->assertDatabaseCount('pos_offline_leases', 0);
    }

    public function test_device_context_is_immutable_and_other_company_catalog_is_hidden(): void
    {
        $this->activate();
        $otherCompany = Empresa::factory()->create();
        $otherBranch = Sucursales::factory()->create(['id_empresa' => $otherCompany->id]);
        $otherProduct = $this->product->replicate();
        $otherProduct->id_empresa = $otherCompany->id;
        $otherProduct->save();
        $result = $this->api('POST', 'bootstrap', $this->context())->assertOk();
        $this->assertCount(1, $result->json('products'));
        $this->api('POST', 'bootstrap', [...$this->context(), 'branch_id' => $otherBranch->id])->assertForbidden();
        $this->assertDatabaseHas('offline_devices', ['id' => $this->device, 'branch_id' => $this->branch->id]);
    }

    public function test_admin_company_uses_explicit_branch_without_session(): void
    {
        $otherBranch = Sucursales::factory()->create(['id_empresa' => $this->branch->id_empresa]);
        $this->user->update(['tipo' => 'adminEmpresa', 'id_sucursal' => null]);
        $this->login();
        $this->api('POST', 'devices/activate', [...$this->context(), 'branch_id' => $otherBranch->id])->assertOk();
        $this->api('POST', 'bootstrap', [...$this->context(), 'branch_id' => $otherBranch->id])->assertOk()->assertJsonPath('branch.id', (string) $otherBranch->id);
        $this->api('POST', 'devices/activate', $this->context())->assertForbidden();
    }

    public function test_credit_replay_creates_one_sale_payment_and_net_debt(): void
    {
        $this->activate();
        $operation = $this->operation('Credito', '30.00');
        $first = $this->push([$operation])->assertOk()->assertJsonPath('results.0.status', 'confirmed');
        $this->push([$operation])->assertOk()->assertJsonPath('results.0.status', 'duplicate')->assertJsonPath('results.0.originalStatus', 'confirmed')->assertJsonPath('results.0.serverFolio', $first->json('results.0.serverFolio'));
        $this->assertDatabaseCount('ventas', 1);
        $this->assertDatabaseCount('abono_cuentas', 1);
        $this->assertEquals(70, $this->customer->fresh()->balance);
        $this->assertEquals(8, AltaInventario::latest('id')->first()->cantidad_nueva);
    }

    public function test_credit_zero_and_full_initial_payments(): void
    {
        $this->activate();
        $this->push([$this->operation('Credito', '0.00')])->assertJsonPath('results.0.status', 'confirmed');
        $this->assertFalse((bool) Venta::first()->venta_pagada);
        $this->push([$this->operation('Credito', '100.00', 2)])->assertJsonPath('results.0.status', 'confirmed');
        $this->assertTrue((bool) Venta::latest('id')->first()->venta_pagada);
        $this->assertEquals(100, $this->customer->fresh()->balance);
    }

    public function test_changed_payload_and_reused_sequence_do_not_create_another_sale(): void
    {
        $this->activate();
        $operation = $this->operation();
        $push = $this->push([$operation])->assertJsonPath('results.0.status', 'confirmed');
        $operation['payload']['total'] = '101.00';
        $this->push([$operation])->assertJsonPath('results.0.errorCode', 'IDEMPOTENCY_PAYLOAD_MISMATCH');
        $this->push([$this->operation()])->assertJsonPath('results.0.errorCode', 'SEQUENCE_REUSED');
        $this->assertDatabaseCount('ventas', 1);
    }

    public function test_invalid_amounts_rollback_and_conflict_duplicates_keep_original_status(): void
    {
        $this->activate();
        foreach (['-1.00', '101.00', '1e309', '0.001'] as $index => $payment) {
            $operation = $this->operation('Credito', $payment, $index + 1);
            $this->push([$operation])->assertJsonPath('results.0.status', 'conflict');
            $this->push([$operation])->assertJsonPath('results.0.status', 'duplicate')->assertJsonPath('results.0.originalStatus', 'conflict');
        }
        $operation = $this->operation('Contado', '100.00', 5);
        $operation['payload']['items'][0]['total'] = '1.00';
        $this->push([$operation])->assertJsonPath('results.0.status', 'conflict');
        $this->assertDatabaseCount('ventas', 0);
        $this->assertDatabaseCount('abono_cuentas', 0);
        $this->assertEquals(0, $this->customer->fresh()->balance);
        $this->assertDatabaseCount('alta_inventarios', 1);
    }

    public function test_mixed_batch_retries_temporary_failure_without_partial_writes(): void
    {
        $this->activate();
        $invalid = $this->operation('Credito', '101.00');
        $valid = $this->operation('Credito', '30.00', 2);
        $this->push([$invalid, $valid])->assertJsonPath('results.0.status', 'conflict')->assertJsonPath('results.1.status', 'confirmed');
        $retry = $this->operation('Credito', '30.00', 3);
        AbonoCuenta::creating(fn () => throw new \RuntimeException('Simulated storage failure'));
        try {
            $this->push([$retry])->assertJsonPath('results.0.status', 'retry');
        } finally {
            AbonoCuenta::flushEventListeners();
        }
        $this->assertDatabaseCount('ventas', 1);
        $this->push([$retry])->assertJsonPath('results.0.status', 'confirmed');
        $this->assertEquals(140, $this->customer->fresh()->balance);
    }

    public function test_late_operations_have_bounded_server_acceptance_window(): void
    {
        $this->activate();
        $operation = $this->operation();
        $this->travel(7)->days();
        $this->travel(1)->hours();
        $this->push([$operation])->assertJsonPath('results.0.status', 'confirmed');
        $this->travel(24)->hours();
        $this->push([$operation])->assertJsonPath('results.0.status', 'duplicate');
        $operation['operation_id'] = (string) Str::uuid();
        $operation['aggregate_id'] = (string) Str::uuid();
        $operation['sequence'] = 2;
        $this->push([$operation])->assertJsonPath('results.0.errorCode', 'OFFLINE_LEASE_EXPIRED');
    }

    public function test_paginated_snapshot_is_immutable_during_catalog_change(): void
    {
        $this->activate();
        $context = [...$this->context(), 'page_size' => 1];
        $first = $this->api('POST', 'bootstrap', $context)->assertOk()->assertJsonPath('hasMore', true);
        $originalName = $this->customer->nombre;
        $this->customer->update(['nombre' => 'Changed after snapshot']);
        $second = $this->api('POST', 'bootstrap', [...$context, 'page_token' => $first->json('pageToken')])->assertOk()->assertJsonPath('hasMore', false);
        $this->assertSame($first->json('snapshotRevision'), $second->json('snapshotRevision'));
        $this->assertSame($originalName, $second->json('customers.0.name'));
        $this->api('POST', 'sync/pull', [...$this->context(), 'cursor' => $second->json('nextCursor')])->assertOk()->assertJsonPath('customers.0.name', 'Changed after snapshot');
    }

    public function test_snapshots_include_credit_receipt_stock_account_and_web_changes(): void
    {
        $this->activate();
        $base = $this->api('POST', 'bootstrap', $this->context())->assertOk()
            ->assertJsonPath('products.0.classification', CatClasificacion::findOrFail($this->product->id_clasificacion)->nombre);
        $operation = $this->operation('Credito', '30.00');
        $push = $this->push([$operation])->assertJsonPath('results.0.status', 'confirmed');
        $pull = $this->api('POST', 'sync/pull', [...$this->context(), 'cursor' => $base->json('nextCursor'), 'known_operation_ids' => [$operation['operation_id']]])->assertOk();
        $pull->assertJsonPath('products.0.serverQuantity', '8.00')->assertJsonPath('customers.0.balance', '70.00')->assertJsonPath('operationReceipts.0.includedInSnapshot', true);
        if ($output = getenv('POS_CONTRACT_OUTPUT')) {
            file_put_contents($output, json_encode(['PushResults' => $push->json(), 'SnapshotPage' => $pull->json(), 'Activation' => ['authorized' => true, 'offlineLease' => $this->lease]], JSON_THROW_ON_ERROR));
        }
        // Changes from any central writer are detected without timestamp windows.
        $this->customer->update(['abono_total' => 40, 'balance' => 60]);
        $this->api('POST', 'sync/pull', [...$this->context(), 'cursor' => $pull->json('nextCursor')])->assertJsonPath('customers.0.balance', '60.00');
        $this->product->delete();
        $this->api('POST', 'sync/pull', [...$this->context(), 'cursor' => $pull->json('nextCursor')])->assertJsonPath('products.0.active', false);
    }

    public function test_cursor_expiry_and_unknown_operation_receipts_preserve_recovery_path(): void
    {
        $this->activate();
        $id = (string) Str::uuid();
        $snapshot = $this->api('POST', 'bootstrap', [...$this->context(), 'known_operation_ids' => [$id]])->assertJsonPath('operationReceipts.0.originalStatus', 'unknown')->assertJsonPath('operationReceipts.0.includedInSnapshot', false);
        $this->travel(25)->hours();
        $this->api('POST', 'sync/pull', [...$this->context(), 'cursor' => $snapshot->json('nextCursor')])->assertStatus(410);
        $this->api('POST', 'bootstrap', [...$this->context(), 'known_operation_ids' => [$id]])->assertOk();
    }

    public function test_web_sale_rejects_tampered_total_and_excess_credit_payment_before_writes(): void
    {
        $this->actingAs($this->user);
        $body = ['id_cliente' => $this->customer->id, 'tipo_venta' => 'Credito', 'total' => '100.00', 'abono' => '101.00',
            'producto_venta' => [['producto' => ['id' => $this->product->id], 'cantidad' => '2.00', 'precio_unitario' => '50.00', 'importe' => '100.00']]];
        $this->postJson('/venta', $body)->assertUnprocessable();
        $body['abono'] = '30.00';
        $body['total'] = '1.00';
        $this->postJson('/venta', $body)->assertUnprocessable();
        $this->assertSame(0, Venta::count());
        $this->assertSame(0, AbonoCuenta::count());
        $this->assertEquals(10, AltaInventario::latest('id')->first()->cantidad_nueva);
    }

    public function test_native_device_cannot_bypass_lease_using_pwa_endpoints(): void
    {
        $this->activate();
        config(['offline.enabled' => true, 'offline.catalog_enabled' => true, 'offline.sync_enabled' => true]);
        Auth::forgetGuards();
        $this->actingAs($this->user)->getJson('/api/v1/offline/bootstrap', ['X-Device-ID' => $this->device])->assertForbidden();
        $this->postJson('/api/v1/offline/sync/push', [...$this->context(), 'operations' => [$this->operation()]])->assertForbidden();
        $this->assertSame(0, Venta::count());
    }

    public function test_token_expiry_and_two_factor_challenge_expiry(): void
    {
        $this->login();
        $this->travel(31)->days();
        $this->api('GET', 'branches')->assertUnauthorized();
        $this->travelBack();
        $this->user->forceFill(['two_factor_secret' => Crypt::encrypt('secret'), 'two_factor_confirmed_at' => now()])->save();
        Auth::forgetGuards();
        $challenge = $this->postJson('/api/v1/pos/auth/login', $this->credentials())->json('challenge');
        $this->travel(6)->minutes();
        $this->postJson('/api/v1/pos/auth/verify-2fa', ['challenge' => $challenge, 'code' => '123456'])->assertUnprocessable();
    }

    public function test_shared_money_fixtures_match_server_discount_and_credit_rules(): void
    {
        $fixtures = json_decode(file_get_contents(base_path('tests/Fixtures/pos-sale-amounts.json')), true, flags: JSON_THROW_ON_ERROR);
        foreach ($fixtures['cases'] as $fixture) {
            $this->customer->update(['porcentaje_descuento' => $fixture['discount']]);
            $this->product->update(['precio_ieps' => $fixture['price']]);
            $amounts = app(\App\Services\Pos\SaleAmounts::class)->validate([
                'saleType' => $fixture['saleType'], 'total' => $fixture['total'],
                'items' => [['productId' => $this->product->id, 'quantity' => $fixture['quantity'], 'unitPrice' => $fixture['unitPrice'], 'total' => $fixture['total']]],
                'payments' => [['method' => 'cash', 'amount' => $fixture['payment']]],
            ], $this->customer, $this->branch, false);
            $this->assertSame($fixture['balance'], \App\Services\Pos\Decimal::format($amounts['total'] - $amounts['payment']), $fixture['name']);
            $this->assertSame($fixture['paid'], $amounts['paid'], $fixture['name']);
        }
    }

    public function test_openapi_routes_match_registered_native_endpoints(): void
    {
        $contract = json_decode(file_get_contents(base_path('openspec/changes/flutter-pos-mvp/openapi.json')), true, flags: JSON_THROW_ON_ERROR);
        $routes = collect(\Illuminate\Support\Facades\Route::getRoutes())->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1/pos/'));
        $documented = [];
        foreach ($contract['paths'] as $path => $methods) {
            foreach ($methods as $method => $operation) {
                $this->assertTrue($routes->contains(fn ($route) => '/'.$route->uri() === '/api/v1/pos'.$path && in_array(strtoupper($method), $route->methods(), true)), $method.' '.$path);
                $documented[] = $operation['operationId'];
            }
        }
        $this->assertCount($routes->count(), $documented);
        $this->assertCount(count(array_unique($documented)), $documented);
    }

    public function test_valid_web_credit_marks_full_payment_without_settling_previous_debt(): void
    {
        $this->customer->update(['adeudo_total' => 100, 'abono_total' => 0, 'balance' => 100]);
        $this->actingAs($this->user)->postJson('/venta', ['id_cliente' => $this->customer->id, 'tipo_venta' => 'Credito', 'total' => '100.00', 'abono' => '100.00',
            'producto_venta' => [['producto' => ['id' => $this->product->id], 'cantidad' => '2.00', 'precio_unitario' => '50.00', 'importe' => '100.00']]])->assertSuccessful();
        $this->assertTrue((bool) Venta::first()->venta_pagada);
        $this->assertTrue((bool) AbonoCuenta::first()->cuenta_pagada);
        $this->assertEquals(100, $this->customer->fresh()->balance);
    }

    public function test_real_totp_is_verified_using_existing_fortify_encryption(): void
    {
        $engine = new \PragmaRX\Google2FA\Google2FA;
        $secret = $engine->generateSecretKey();
        $this->user->forceFill(['two_factor_secret' => Crypt::encrypt($secret), 'two_factor_confirmed_at' => now()])->save();
        $challenge = $this->postJson('/api/v1/pos/auth/login', $this->credentials())->json('challenge');
        $this->postJson('/api/v1/pos/auth/verify-2fa', ['challenge' => $challenge, 'code' => $engine->getCurrentOtp($secret)])->assertOk()->assertJsonPath('requiresTwoFactor', false)->assertJsonStructure(['token']);
    }

    public function test_inventory_role_can_read_catalog_but_cannot_sell(): void
    {
        $this->user->update(['tipo' => 'inventario']);
        $this->activate();
        $permissions = $this->api('POST', 'bootstrap', $this->context())->assertOk()->json('user.permissions');
        $this->assertNotContains('sale.create', $permissions);
        $this->push([$this->operation()])->assertForbidden();
    }

    public function test_pull_observes_central_stock_writers_and_customer_deactivation(): void
    {
        $this->activate();
        $cursor = $this->api('POST', 'bootstrap', $this->context())->assertOk()->json('nextCursor');
        $quantity = 10;
        foreach ([AltaInventario::EVENTO_ALTA => 20, AltaInventario::EVENTO_TRANSFERENCIA_SALIDA => 15, AltaInventario::EVENTO_TRANSFERENCIA_ENTRADA => 18] as $event => $next) {
            AltaInventario::create(['id_producto' => $this->product->id, 'id_sucursal' => $this->branch->id, 'id_usuario' => $this->user->id, 'cantidad_actual' => $quantity, 'cantidad_nueva' => $next, 'tipo_evento' => $event]);
            $quantity = $next;
            $response = $this->api('POST', 'sync/pull', [...$this->context(), 'cursor' => $cursor])->assertOk()->assertJsonPath('products.0.serverQuantity', $next.'.00');
            $cursor = $response->json('nextCursor');
        }
        $this->customer->update(['activo' => false]);
        $this->api('POST', 'sync/pull', [...$this->context(), 'cursor' => $cursor])->assertOk()->assertJsonPath('customers.0.active', false);
    }

    public function test_context_exchange_preserves_identity_and_revokes_discovery_token(): void
    {
        $this->activate();
        $old = $this->token;
        $response = $this->api('POST', 'auth/context', $this->context())->assertOk();
        $this->token = $response->json('token');
        $this->api('POST', 'bootstrap', $this->context())->assertOk();
        $this->token = $old;
        $this->api('GET', 'branches')->assertUnauthorized();
        $this->login();
        $other = Sucursales::factory()->create(['id_empresa' => $this->branch->id_empresa]);
        $this->api('POST', 'auth/context', ['device_id' => $this->device, 'branch_id' => $other->id])->assertForbidden();
    }
    private function destructiveInput(string $action): array
    {
        $preview = $this->api('POST', 'inventory/'.$this->product->id.'/preview', [...$this->context(), 'action' => $action])->assertOk()->json();

        return [...$this->context(), 'operation_id' => (string) Str::uuid(), 'version' => $preview['version'],
            'stock_revision' => $preview['stockRevision'], 'confirmed' => true];
    }

    public function test_destructive_inventory_permissions_are_restricted_to_company_admin_and_super_admin(): void
    {
        $this->activate();
        foreach (['vendedor', 'inventario', 'admin', 'adminEmpresa', 'superAdmin'] as $role) {
            $this->user->update(['tipo' => $role]);
            $permissions = $this->api('POST', 'bootstrap', $this->context())->assertOk()->json('user.permissions');
            $allowed = in_array($role, ['adminEmpresa', 'superAdmin'], true);
            $this->assertEquals($allowed, in_array('product.delete', $permissions, true));
            $this->assertEquals($allowed, in_array('inventory.reset', $permissions, true));
            foreach (['reset', 'delete'] as $action) {
                $response = $this->api('POST', 'inventory/'.$this->product->id.'/preview', [...$this->context(), 'action' => $action]);
                $allowed ? $response->assertOk() : $response->assertForbidden();
                if (!$allowed) {
                    $this->api('POST', 'inventory/'.$this->product->id.'/'.$action, [...$this->context(), 'operation_id' => (string) Str::uuid()])->assertForbidden();
                }
            }
        }
        $this->branch->empresa->update(['ventas_bloqueadas' => true]);
        $this->user->update(['tipo' => 'adminEmpresa']);
        $this->api('POST', 'inventory/'.$this->product->id.'/preview', [...$this->context(), 'action' => 'reset'])->assertForbidden();
        $this->user->update(['tipo' => 'superAdmin']);
        $this->api('POST', 'inventory/'.$this->product->id.'/preview', [...$this->context(), 'action' => 'reset'])->assertOk();
    }

    public function test_reset_records_actor_and_branch_and_replay_does_not_erase_new_stock(): void
    {
        $this->user->update(['tipo' => 'adminEmpresa']);
        $this->activate();
        $other = Sucursales::factory()->create(['id_empresa' => $this->branch->id_empresa]);
        AltaInventario::create(['id_producto' => $this->product->id, 'id_sucursal' => $other->id, 'id_usuario' => $this->user->id, 'cantidad_actual' => 5, 'cantidad_nueva' => 5]);
        $input = $this->destructiveInput('reset');
        $path = 'inventory/'.$this->product->id.'/reset';
        $this->api('POST', $path, [...$input, 'confirmed' => false])->assertUnprocessable();
        $result = $this->api('POST', $path, $input)->assertOk()->assertJsonPath('quantity', '0.00')->json();
        $movement = AltaInventario::findOrFail($result['movementId']);
        $this->assertEquals(10, $movement->cantidad_actual);
        $this->assertEquals($this->user->id, $movement->id_usuario);
        $this->assertEquals($this->branch->id, $movement->id_sucursal);
        $this->assertEquals(AltaInventario::EVENTO_RESETEO, $movement->tipo_evento);
        app(\App\Services\InventoryManagement::class)->addStock($this->user, $this->branch, $this->product->id, '2.50');
        $this->api('POST', $path, $input)->assertOk()->assertExactJson($result);
        $this->assertEquals(2.5, AltaInventario::where('id_sucursal', $this->branch->id)->latest('id')->first()->cantidad_nueva);
        $this->assertEquals(5, AltaInventario::where('id_sucursal', $other->id)->latest('id')->first()->cantidad_nueva);
        $this->assertEquals(1, AltaInventario::where('tipo_evento', AltaInventario::EVENTO_RESETEO)->count());
        $this->api('POST', $path, [...$input, 'stock_revision' => str_repeat('b', 64)])->assertConflict();
    }

    public function test_destructive_actions_reject_stale_product_or_stock_and_foreign_company(): void
    {
        $this->user->update(['tipo' => 'adminEmpresa']);
        $this->activate();
        $input = $this->destructiveInput('reset');
        app(\App\Services\InventoryManagement::class)->addStock($this->user, $this->branch, $this->product->id, '1.00');
        $this->api('POST', 'inventory/'.$this->product->id.'/reset', $input)->assertConflict();
        $input = $this->destructiveInput('reset');
        $this->product->update(['nombre' => 'Nombre actualizado']);
        $this->api('POST', 'inventory/'.$this->product->id.'/reset', $input)->assertConflict();
        $this->product->update(['id_empresa' => Empresa::factory()->create()->id]);
        $this->api('POST', 'inventory/'.$this->product->id.'/preview', [...$this->context(), 'action' => 'delete'])->assertNotFound();
        $this->api('POST', 'inventory/'.$this->product->id.'/delete', $input)->assertNotFound();
        $this->assertEquals(0, DB::table('pos_inventory_requests')->count());
    }

    public function test_delete_checks_all_branches_soft_deletes_preserves_history_and_replays(): void
    {
        $this->activate();
        $this->push([$this->operation()])->assertOk();
        $sales = Venta::count();
        $this->user->update(['tipo' => 'adminEmpresa']);
        $management = app(\App\Services\InventoryManagement::class);
        $management->destructive($this->user, $this->branch, $this->product->id, 'reset');
        // A deactivated branch still owns stock and must prevent deletion.
        $other = Sucursales::factory()->create(['id_empresa' => $this->branch->id_empresa]);
        AltaInventario::create(['id_producto' => $this->product->id, 'id_sucursal' => $other->id, 'id_usuario' => $this->user->id, 'cantidad_actual' => 3, 'cantidad_nueva' => 3]);
        $other->delete();
        $input = $this->destructiveInput('delete');
        $this->api('POST', 'inventory/'.$this->product->id.'/delete', $input)->assertUnprocessable()->assertJsonValidationErrors('producto');
        $management->destructive($this->user, $other, $this->product->id, 'reset');
        $this->api('POST', 'inventory/'.$this->product->id.'/delete', $input)->assertConflict();
        $input = $this->destructiveInput('delete');
        $count = AltaInventario::count();
        $result = $this->api('POST', 'inventory/'.$this->product->id.'/delete', $input)->assertOk()->json();
        $this->assertSoftDeleted('productos', ['id' => $this->product->id]);
        $this->assertEquals($sales, Venta::count());
        $this->assertEquals($count, AltaInventario::count());
        $this->api('POST', 'inventory/'.$this->product->id.'/delete', $input)->assertOk()->assertExactJson($result);
        $this->api('POST', 'inventory/'.$this->product->id.'/detail', $this->context())->assertNotFound();
        $this->api('POST', 'bootstrap', $this->context())->assertOk()->assertJsonPath('products.0.active', false);
        $this->api('POST', 'inventory/'.$this->product->id.'/add', [...$this->context(), 'operation_id' => (string) Str::uuid(), 'cantidad' => '1.00'])->assertNotFound();
    }

    public function test_super_admin_can_reset_from_web_and_inventory_role_cannot(): void
    {
        $this->user->update(['tipo' => 'superAdmin']);
        $this->actingAs($this->user)->withSession(['sucursal_activa' => $this->branch->id])->post('/inventario/'.$this->product->id.'/reset')->assertRedirect();
        $this->assertEquals(0, AltaInventario::latest('id')->first()->cantidad_nueva);
        $this->user->update(['tipo' => 'inventario']);
        $this->actingAs($this->user)->post('/inventario/'.$this->product->id.'/reset')->assertForbidden();
    }

    private function purchaseInput(): array
    {
        return [...$this->context(), 'operation_id' => (string) Str::uuid(), 'supplier' => 'Proveedor de prueba',
            'date' => '2026-10-08', 'payment' => '5.00', 'items' => [['productId' => (string) $this->product->id, 'quantity' => '1.25', 'cost' => '20.10']]];
    }

    public function test_stock_workflow_permissions_follow_web_roles_and_live_revocation(): void
    {
        $this->activate();
        foreach (['vendedor', 'inventario', 'admin', 'adminEmpresa', 'superAdmin'] as $role) {
            $this->user->update(['tipo' => $role]);
            $purchase = $this->api('POST', 'purchases/list', $this->context());
            $role === 'inventario' ? $purchase->assertForbidden() : $purchase->assertOk();
            $transfer = $this->api('POST', 'transfers/list', $this->context());
            $role === 'vendedor' ? $transfer->assertForbidden() : $transfer->assertOk();
        }
        $this->user->update(['tipo' => 'inventario']);
        $this->api('POST', 'purchases/create', $this->purchaseInput())->assertForbidden();
        $this->branch->empresa->update(['ventas_bloqueadas' => true]);
        $this->api('POST', 'transfers/list', $this->context())->assertForbidden();
    }

    public function test_native_purchase_totals_fractional_stock_and_exact_replay_are_atomic(): void
    {
        $this->activate();
        $input = $this->purchaseInput();
        $result = $this->api('POST', 'purchases/create', $input)->assertOk()->json();
        $purchase = \App\Models\Compras::findOrFail($result['purchaseId']);
        $this->assertEquals(25.13, $purchase->total_compra);
        $this->assertEquals(20.13, $purchase->total_credito);
        $this->assertEquals('adeudo', $purchase->status);
        $this->assertEquals(11.25, AltaInventario::latest('id')->first()->cantidad_nueva);
        $this->assertEquals(1.25, \App\Models\ComprasProductos::first()->cantidad);
        $this->assertEquals(1.25, \App\Models\ComprasProductos::first()->cantidad_disponible);
        $this->product->delete();
        $this->api('POST', 'purchases/create', $input)->assertOk()->assertExactJson($result);
        $this->assertEquals(1, \App\Models\Compras::count());
        $this->api('POST', 'purchases/'.$purchase->id.'/detail', $this->context())->assertOk()->assertJsonPath('purchase.items.0.quantity', '1.25');
        $this->api('POST', 'purchases/create', [...$input, 'payment' => '4.00'])->assertConflict();
        $this->api('POST', 'purchases/create', [...$input, 'operation_id' => (string) Str::uuid()])->assertUnprocessable();
        $this->assertEquals(1, \App\Models\Compras::count());
    }

    public function test_purchase_payment_prevents_duplicate_overpayment_and_stale_balance(): void
    {
        $this->activate();
        $id = $this->api('POST', 'purchases/create', $this->purchaseInput())->assertOk()->json('purchaseId');
        $input = [...$this->context(), 'operation_id' => (string) Str::uuid(), 'amount' => '10.00', 'expected_debt' => '20.13'];
        $path = 'purchases/'.$id.'/payment';
        $result = $this->api('POST', $path, $input)->assertOk()->json();
        $this->api('POST', $path, $input)->assertOk()->assertExactJson($result);
        $this->api('POST', $path, [...$input, 'operation_id' => (string) Str::uuid()])->assertConflict();
        $this->api('POST', $path, [...$input, 'operation_id' => (string) Str::uuid(), 'expected_debt' => '10.13', 'amount' => '11.00'])->assertUnprocessable();
        $this->api('POST', $path, [...$input, 'operation_id' => (string) Str::uuid(), 'expected_debt' => '10.13', 'amount' => '10.13'])->assertOk();
        $this->api('POST', 'purchases/'.$id.'/detail', $this->context())->assertOk()->assertJsonPath('purchase.debt', '0.00')->assertJsonPath('purchase.status', 'pagado');
        $this->assertEquals(3, \App\Models\ComprasAbonos::count());
        $other = Sucursales::factory()->create(['id_empresa' => $this->branch->id_empresa]);
        \App\Models\Compras::find($id)->update(['id_sucursal' => $other->id]);
        $this->api('POST', 'purchases/'.$id.'/detail', $this->context())->assertNotFound();
        $this->api('POST', $path, [...$input, 'operation_id' => (string) Str::uuid()])->assertNotFound();
    }

    public function test_transfer_creation_and_reception_preserve_fifo_and_are_idempotent(): void
    {
        $this->user->update(['tipo' => 'adminEmpresa']);
        $this->activate();
        $origin = $this->branch;
        $destination = Sucursales::factory()->create(['id_empresa' => $origin->id_empresa]);
        $input = $this->purchaseInput();
        $input['items'] = [['productId' => (string) $this->product->id, 'quantity' => '2.50', 'cost' => '20.00']];
        $this->api('POST', 'purchases/create', $input)->assertOk();
        $input['operation_id'] = (string) Str::uuid();
        $input['date'] = '2026-10-09';
        $input['items'] = [['productId' => (string) $this->product->id, 'quantity' => '3.00', 'cost' => '30.00']];
        $this->api('POST', 'purchases/create', $input)->assertOk();
        $payload = [...$this->context(), 'operation_id' => (string) Str::uuid(), 'destination_id' => (string) $destination->id,
            'notes' => 'Insumos', 'items' => [['productId' => (string) $this->product->id, 'quantity' => '4.00']]];
        $result = $this->api('POST', 'transfers/create', $payload)->assertOk()->json();
        $id = $result['transferId'];
        $this->api('POST', 'transfers/create', $payload)->assertOk()->assertExactJson($result);
        $this->assertEquals(15.5, AltaInventario::latest('id')->first()->cantidad_nueva);
        $this->api('POST', 'transfers/'.$id.'/receive', [...$this->context(), 'operation_id' => (string) Str::uuid(), 'confirmed' => true])->assertForbidden();
        $this->branch = $destination;
        $this->device = (string) Str::uuid();
        $this->activate();
        $receive = [...$this->context(), 'operation_id' => (string) Str::uuid(), 'confirmed' => true];
        $result = $this->api('POST', 'transfers/'.$id.'/receive', $receive)->assertOk()->json();
        $this->api('POST', 'transfers/'.$id.'/receive', $receive)->assertOk()->assertExactJson($result);
        $this->api('POST', 'transfers/'.$id.'/receive', [...$receive, 'operation_id' => (string) Str::uuid()])->assertOk();
        $this->assertEquals(11.5, AltaInventario::where('id_sucursal', $origin->id)->latest('id')->first()->cantidad_nueva);
        $this->assertEquals(4, AltaInventario::where('id_sucursal', $destination->id)->latest('id')->first()->cantidad_nueva);
        $this->assertEquals(2, AltaInventario::whereIn('tipo_evento', [AltaInventario::EVENTO_TRANSFERENCIA_ENTRADA, AltaInventario::EVENTO_TRANSFERENCIA_SALIDA])->count());
        $internal = \App\Models\Compras::where('id_sucursal', $destination->id)->get()->sole();
        $lots = \App\Models\ComprasProductos::where('id_compra', $internal->id)->orderBy('id')->get();
        $this->assertEquals([2.5, 1.5], $lots->pluck('cantidad')->map(fn ($v) => (float) $v)->all());
        $this->assertEquals([20, 30], $lots->pluck('precio')->map(fn ($v) => (float) $v)->all());
        $this->api('POST', 'transfers/'.$id.'/detail', $this->context())->assertOk()->assertJsonPath('transfer.status', 'completado');
        $this->assertEquals($this->user->id, \App\Models\Transferencia::find($id)->id_usuario_recibe);
    }

    public function test_transfer_validation_company_isolation_and_failed_receipt_roll_back(): void
    {
        $this->user->update(['tipo' => 'adminEmpresa']);
        $this->activate();
        $origin = $this->branch;
        $destination = Sucursales::factory()->create(['id_empresa' => $origin->id_empresa]);
        $foreign = Sucursales::factory()->create(['id_empresa' => Empresa::factory()->create()->id]);
        $input = [...$this->context(), 'operation_id' => (string) Str::uuid(), 'destination_id' => (string) $foreign->id,
            'items' => [['productId' => (string) $this->product->id, 'quantity' => '20.00']]];
        $this->api('POST', 'transfers/create', $input)->assertNotFound();
        $this->api('POST', 'transfers/create', [...$input, 'destination_id' => (string) $origin->id])->assertUnprocessable();
        $id = $this->api('POST', 'transfers/create', [...$input, 'destination_id' => (string) $destination->id])->assertOk()->json('transferId');
        $this->branch = $destination;
        $this->device = (string) Str::uuid();
        $this->activate();
        $receive = [...$this->context(), 'operation_id' => (string) Str::uuid(), 'confirmed' => true];
        $this->api('POST', 'transfers/'.$id.'/receive', [...$receive, 'confirmed' => false])->assertUnprocessable();
        $this->api('POST', 'transfers/'.$id.'/receive', $receive)->assertUnprocessable();
        $this->assertEquals('pendiente', \App\Models\Transferencia::find($id)->status);
        $this->assertEquals(0, \App\Models\Compras::count());
        $this->assertEquals(1, AltaInventario::count());
        $this->api('POST', 'transfers/destinations', $this->context())->assertOk()->assertJsonCount(1, 'destinations');
        $unrelated = Sucursales::factory()->create(['id_empresa' => $origin->id_empresa]);
        $this->branch = $unrelated;
        $this->device = (string) Str::uuid();
        $this->activate();
        $this->api('POST', 'transfers/'.$id.'/detail', $this->context())->assertNotFound();
    }

    public function test_web_purchase_form_cannot_overwrite_a_native_payment_with_stale_debt(): void
    {
        $this->activate();
        $id = $this->api('POST', 'purchases/create', $this->purchaseInput())->assertOk()->json('purchaseId');
        $this->api('POST', 'purchases/'.$id.'/payment', [...$this->context(), 'operation_id' => (string) Str::uuid(),
            'amount' => '10.00', 'expected_debt' => '20.13'])->assertOk();
        $form = ['proveedor' => 'Proveedor', 'fecha_compra' => '2026-10-08', 'total_compra' => '25.13', 'status' => 'adeudo',
            'productos' => [['id' => $this->product->id]], 'total_credito' => '15.13', 'expected_debt' => '20.13',
            'abonos' => [['cantidad_abonada' => '5.00']]];
        $this->actingAs($this->user)->withSession(['sucursal_activa' => $this->branch->id])->putJson('/compra/'.$id, $form)->assertConflict();
        $this->assertEquals(10.13, \App\Models\Compras::find($id)->total_credito);
        $this->assertEquals(2, \App\Models\ComprasAbonos::count());
        $this->actingAs($this->user)->putJson('/compra/'.$id, [...$form, 'expected_debt' => '10.13', 'total_credito' => '5.13'])->assertOk();
        $this->assertEquals(5.13, \App\Models\Compras::find($id)->total_credito);
        $this->api('POST', 'purchases/'.$id.'/payment', [...$this->context(), 'operation_id' => (string) Str::uuid(),
            'amount' => '1.00', 'expected_debt' => '10.13'])->assertConflict();
    }

    public function test_transfer_created_in_native_app_can_be_received_on_web_without_duplicate_reception(): void
    {
        $this->user->update(['tipo' => 'adminEmpresa']);
        $this->activate();
        $origin = $this->branch;
        $destination = Sucursales::factory()->create(['id_empresa' => $origin->id_empresa]);
        $id = $this->api('POST', 'transfers/create', [...$this->context(), 'operation_id' => (string) Str::uuid(),
            'destination_id' => (string) $destination->id, 'items' => [['productId' => (string) $this->product->id, 'quantity' => '2.50']]])->assertOk()->json('transferId');
        $this->actingAs($this->user)->withSession(['sucursal_activa' => $destination->id])->post('/transferencias/'.$id.'/recibir')->assertRedirect('/transferencias/'.$id);
        $this->actingAs($this->user)->post('/transferencias/'.$id.'/recibir')->assertRedirect('/transferencias/'.$id);
        $this->assertEquals(2, AltaInventario::whereIn('tipo_evento', [AltaInventario::EVENTO_TRANSFERENCIA_ENTRADA, AltaInventario::EVENTO_TRANSFERENCIA_SALIDA])->count());
        $this->assertEquals(7.5, AltaInventario::where('id_sucursal', $origin->id)->latest('id')->first()->cantidad_nueva);
        $this->assertEquals(2.5, AltaInventario::where('id_sucursal', $destination->id)->latest('id')->first()->cantidad_nueva);
        $this->assertEquals(1, \App\Models\Compras::count());
    }

}
