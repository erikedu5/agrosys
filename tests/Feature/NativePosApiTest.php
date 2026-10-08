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
        $base = $this->api('POST', 'bootstrap', $this->context())->assertOk();
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
}
