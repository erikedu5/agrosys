<?php

namespace Tests\Feature;

use App\Models\Clientes;
use App\Models\Empresa;
use App\Models\Sucursales;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCreditRestrictionTest extends TestCase
{
    use RefreshDatabase;

    private Clientes $customer;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $empresa = Empresa::factory()->create();
        $branch = Sucursales::factory()->create(['id_empresa' => $empresa->id]);
        $this->user = User::factory()->create(['tipo' => 'admin', 'id_sucursal' => $branch->id, 'id_empresa' => $empresa->id]);
        $this->customer = Clientes::factory()->create(['id_sucursal' => $branch->id, 'activo' => true,
            'adeudo_total' => 300, 'abono_total' => 0, 'balance' => 300]);
    }

    private function creditSale(int $daysAgo, bool $paid = false): void
    {
        $sale = Venta::create(['total' => '300.00', 'id_usuario' => $this->user->id, 'id_cliente' => $this->customer->id,
            'tipo_venta' => 'Credito', 'venta_pagada' => $paid, 'id_sucursal' => $this->customer->id_sucursal]);
        Venta::whereKey($sale->id)->update(['created_at' => now()->subDays($daysAgo)]);
    }

    public function test_customer_without_limits_is_not_restricted(): void
    {
        $this->creditSale(400);
        $this->assertNull($this->customer->restriccionCredito(999999));
    }

    public function test_credit_limit_restricts_once_debt_would_exceed_it(): void
    {
        $this->customer->update(['limite_credito' => 500]);
        $this->assertNull($this->customer->restriccionCredito(200));
        $this->assertNotNull($this->customer->restriccionCredito(200.01));
        $this->customer->update(['balance' => 500]);
        $this->assertNotNull($this->customer->restriccionCredito());
    }

    public function test_credit_term_restricts_when_oldest_unpaid_credit_sale_is_overdue(): void
    {
        $this->customer->update(['plazo_credito_dias' => 30]);
        $this->creditSale(60, paid: true);
        $this->creditSale(29);
        $this->assertNull($this->customer->restriccionCredito(100));
        $this->creditSale(31);
        $this->assertStringContainsString('plazo', $this->customer->restriccionCredito(100));
    }
}
