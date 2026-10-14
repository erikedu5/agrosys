<?php

namespace Tests\Feature;

use App\Models\Clientes;
use App\Models\Empresa;
use App\Models\Sucursales;
use App\Models\User;
use App\Services\CashRegister;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CashRegisterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Sucursales $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $company = Empresa::factory()->create();
        $this->branch = Sucursales::factory()->create(['id_empresa' => $company->id]);
        $this->user = User::factory()->create(['tipo' => 'vendedor', 'id_empresa' => $company->id, 'id_sucursal' => $this->branch->id]);
        $this->actingAs($this->user);
    }

    private function open(string $amount = '200.00'): string
    {
        return $this->postJson('/caja/open', ['operation_id' => (string) Str::uuid(), 'opening' => $amount])->assertOk()->json('sessionId');
    }

    public function test_open_retry_cannot_duplicate_and_changed_payload_is_rejected(): void
    {
        $input = ['operation_id' => (string) Str::uuid(), 'opening' => '200.10'];
        $result = $this->postJson('/caja/open', $input)->assertOk()->json();
        $this->postJson('/caja/open', $input)->assertOk()->assertExactJson($result);
        $this->postJson('/caja/open', [...$input, 'opening' => '300.00'])->assertStatus(409);
        $this->postJson('/caja/open', [...$input, 'operation_id' => (string) Str::uuid()])->assertStatus(409);
        $this->assertDatabaseCount('cash_sessions', 1);
        $this->postJson('/caja/status')->assertOk()->assertJsonPath('current.expected', '200.10');
    }

    public function test_movement_retry_exact_totals_cut_difference_and_immutable_history(): void
    {
        $id = $this->open('200.10');
        $cash = app(CashRegister::class);
        DB::transaction(function () use ($cash) {
            $s = $cash->saleSession($this->user->id, $this->branch->id);
            $cash->record($s, 10000, 'sale', 'sale:1', 'Venta #1');
            $cash->record($s, 3025, 'credit_initial', 'sale:2', 'Venta #2');
            $cash->record($s, -1010, 'refund', 'refund:1', 'Devolución #1');
        });
        $input = ['operation_id' => (string) Str::uuid(), 'session_id' => $id, 'kind' => 'out', 'amount' => '50.05', 'description' => 'Retiro'];
        $this->postJson('/caja/movement', $input)->assertOk();
        $this->postJson('/caja/movement', $input)->assertOk();
        $this->postJson('/caja/movement', [...$input, 'operation_id' => (string) Str::uuid(), 'kind' => 'in', 'amount' => '20.00'])->assertOk();
        $this->postJson('/caja/status')->assertOk()->assertJsonPath('current.expected', '290.20');
        $close = ['operation_id' => (string) Str::uuid(), 'session_id' => $id, 'expected' => '290.20', 'counted' => '289.20', 'notes' => 'Faltante de un peso', 'confirmed' => true];
        $cut = $this->postJson('/caja/close', $close)->assertOk()->assertJsonPath('session.difference', '-1.00')->json();
        $this->postJson('/caja/close', $close)->assertOk()->assertExactJson($cut);
        $this->postJson('/caja/movement', [...$input, 'operation_id' => (string) Str::uuid()])->assertStatus(409);
        $this->postJson('/caja/status')->assertOk()->assertJsonPath('current', null)->assertJsonPath('history.0.expected', '290.20');
        $new = $this->open('0.00');
        $this->assertNotEquals($id, $new);
        $this->assertDatabaseCount('cash_movements', 5);
    }

    public function test_invalid_amounts_missing_explanation_and_stale_cut_are_rejected(): void
    {
        $this->postJson('/caja/open', ['operation_id' => (string) Str::uuid(), 'opening' => '-1'])->assertUnprocessable();
        $this->postJson('/caja/open', ['operation_id' => (string) Str::uuid(), 'opening' => '1.001'])->assertUnprocessable();
        $id = $this->open();
        $close = ['operation_id' => (string) Str::uuid(), 'session_id' => $id, 'expected' => '100.00', 'counted' => '200.00', 'confirmed' => true];
        $this->postJson('/caja/close', $close)->assertStatus(409);
        $this->postJson('/caja/close', [...$close, 'expected' => '200.00', 'counted' => '199.00'])->assertUnprocessable();
        $this->postJson('/caja/close', [...$close, 'expected' => '200.00', 'confirmed' => false])->assertUnprocessable();
        $this->postJson('/caja/movement', ['operation_id' => (string) Str::uuid(), 'session_id' => $id, 'kind' => 'out', 'amount' => '201.00', 'description' => 'Retiro'])->assertUnprocessable();
        $this->assertDatabaseCount('cash_requests', 1);
        $this->assertNotNull(app(CashRegister::class)->current($this->user->id, $this->branch->id));
    }

    public function test_cash_is_scoped_to_cashier_branch_and_role(): void
    {
        $id = $this->open();
        $other = User::factory()->create(['tipo' => 'vendedor', 'id_empresa' => $this->branch->id_empresa, 'id_sucursal' => $this->branch->id]);
        $this->actingAs($other)->postJson('/caja/status')->assertOk()->assertJsonPath('current', null);
        $this->postJson('/caja/close', ['operation_id' => (string) Str::uuid(), 'session_id' => $id, 'expected' => '200.00', 'counted' => '200.00', 'confirmed' => true])->assertStatus(409);
        $other->update(['tipo' => 'inventario']);
        $this->postJson('/caja/status')->assertForbidden();
    }

    public function test_web_account_payment_is_cash_and_previous_payments_are_excluded(): void
    {
        $customer = Clientes::factory()->create(['id_sucursal' => $this->branch->id, 'adeudo_total' => 500, 'abono_total' => 0, 'balance' => 500]);
        $this->put('/venta/'.$customer->id, ['id' => $customer->id, 'abono' => '10.00'])->assertRedirect();
        $this->open('100.00');
        $this->put('/venta/'.$customer->id, ['id' => $customer->id, 'abono' => '25.10'])->assertRedirect();
        $this->postJson('/caja/status')->assertOk()->assertJsonPath('current.expected', '125.10')->assertJsonPath('current.totals.account_payment', '25.10');
        $this->assertEquals(464.90, $customer->fresh()->balance);
    }
}
