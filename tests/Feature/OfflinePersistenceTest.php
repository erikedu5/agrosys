<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OfflinePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_offline_operation_schema_and_sale_idempotency_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('ventas', ['client_sale_id', 'operation_id', 'device_id', 'occurred_at']));
        $this->assertTrue(Schema::hasColumns('offline_devices', ['id', 'authorized', 'last_sequence', 'offline_expires_at']));
        $this->assertTrue(Schema::hasColumns('offline_operations', ['operation_id', 'device_id', 'aggregate_id', 'sequence', 'status', 'payload', 'result']));
    }
}
