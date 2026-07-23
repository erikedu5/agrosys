<?php

namespace Tests\Feature;

use App\Models\AltaInventario;
use App\Models\CatClasificacion;
use App\Models\CatMarca;
use App\Models\Clientes;
use App\Models\Empresa;
use App\Models\OfflineDevice;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OfflineSaleIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_operation_returns_original_result_without_duplicate_sale(): void
    {
        config()->set('offline.enabled', true);
        config()->set('offline.sync_enabled', true);
        $empresa = Empresa::factory()->create();
        $branch = Sucursales::factory()->create(['id_empresa' => $empresa->id]);
        $user = User::factory()->create(['tipo' => 'vendedor', 'id_sucursal' => $branch->id, 'id_empresa' => $empresa->id]);
        $customer = Clientes::factory()->create(['id_sucursal' => $branch->id, 'activo' => true, 'porcentaje_descuento' => 0]);
        $brand = CatMarca::factory()->create();
        $classification = CatClasificacion::factory()->create();
        $product = Producto::factory()->create(['id_empresa' => $empresa->id, 'id_usuario' => $user->id, 'id_marca' => $brand->id, 'id_clasificacion' => $classification->id, 'tamano' => '1 L', 'precio_ieps' => 125]);
        AltaInventario::create(['cantidad_actual' => 10, 'cantidad_nueva' => 10, 'id_usuario' => $user->id, 'id_producto' => $product->id, 'id_sucursal' => $branch->id]);
        $deviceId = (string) Str::uuid();
        OfflineDevice::create(['id' => $deviceId, 'user_id' => $user->id, 'branch_id' => $branch->id, 'authorized' => true, 'last_sequence' => 0, 'offline_expires_at' => now()->addDays(7)]);
        $operationId = (string) Str::uuid();
        $saleId = (string) Str::uuid();
        $body = ['device_id' => $deviceId, 'branch_id' => (string) $branch->id, 'operations' => [[
            'operation_id' => $operationId, 'aggregate_type' => 'sale', 'aggregate_id' => $saleId, 'event_type' => 'SALE_COMPLETED', 'sequence' => 1, 'occurred_at' => now()->toIso8601String(),
            'payload' => ['customerId' => (string) $customer->id, 'saleType' => 'Contado', 'total' => 250, 'localFolio' => 'LOCAL-1', 'items' => [['productId' => (string) $product->id, 'name' => $product->nombre, 'quantity' => 2, 'unitPrice' => 125, 'total' => 250]], 'payments' => [['method' => 'cash', 'amount' => 250]]],
        ]]];

        $first = $this->actingAs($user)->postJson('/api/v1/offline/sync/push', $body, ['Idempotency-Key' => $operationId])->assertOk()->json('results.0');
        $second = $this->actingAs($user)->postJson('/api/v1/offline/sync/push', $body, ['Idempotency-Key' => $operationId])->assertOk()->json('results.0');

        $this->assertSame('confirmed', $first['status']);
        $this->assertSame('duplicate', $second['status']);
        $this->assertSame($first['serverFolio'], $second['serverFolio']);
        $this->assertSame(1, Venta::where('operation_id', $operationId)->count());
    }
}
