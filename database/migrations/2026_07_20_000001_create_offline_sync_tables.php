<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('ventas', 'client_sale_id')) {
            Schema::table('ventas', fn (Blueprint $table) => $table->uuid('client_sale_id')->nullable());
        }
        if (!Schema::hasColumn('ventas', 'operation_id')) {
            Schema::table('ventas', fn (Blueprint $table) => $table->uuid('operation_id')->nullable());
        }
        if (!Schema::hasColumn('ventas', 'device_id')) {
            Schema::table('ventas', fn (Blueprint $table) => $table->uuid('device_id')->nullable());
        }
        if (!Schema::hasColumn('ventas', 'occurred_at')) {
            Schema::table('ventas', fn (Blueprint $table) => $table->timestampTz('occurred_at')->nullable());
        }

        $this->ensureIndex('ventas', ['client_sale_id'], true, 'ventas_client_sale_id_unique');
        $this->ensureIndex('ventas', ['operation_id'], true, 'ventas_operation_id_unique');
        $this->ensureIndex('ventas', ['device_id'], false, 'ventas_device_id_index');
        $this->ensureIndex('ventas', ['occurred_at'], false, 'ventas_occurred_at_index');

        if (!Schema::hasTable('offline_devices')) {
            Schema::create('offline_devices', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->constrained('users');
                $table->foreignId('branch_id')->constrained('sucursales');
                $table->boolean('authorized')->default(true)->index();
                $table->unsignedBigInteger('last_sequence')->default(0);
                $table->timestampTz('last_seen_at')->nullable();
                $table->timestampTz('offline_expires_at');
                $table->timestampsTz();
            });
        }

        if (!Schema::hasTable('offline_operations')) {
            Schema::create('offline_operations', function (Blueprint $table) {
                $table->uuid('operation_id')->primary();
                $table->uuid('device_id')->index();
                $table->foreignId('branch_id')->constrained('sucursales');
                $table->foreignId('user_id')->constrained('users');
                $table->string('aggregate_type', 40);
                $table->uuid('aggregate_id')->index();
                $table->string('event_type', 80);
                $table->unsignedBigInteger('sequence');
                $table->string('status', 30)->index();
                $table->json('payload');
                $table->json('result')->nullable();
                $table->string('error_code', 80)->nullable();
                $table->text('error_message')->nullable();
                $table->timestampTz('occurred_at');
                $table->timestampsTz();
                $table->unique(['device_id', 'sequence']);
            });
        }
    }

    public function down(): void
    {
        // This migration may adopt structures left by an earlier development.
        // A destructive rollback could remove pre-existing sales data, so rollback
        // is intentionally handled through an explicit, audited migration.
    }

    private function ensureIndex(string $table, array $columns, bool $unique, string $name): void
    {
        $existing = collect(Schema::getIndexes($table))->contains(function (array $index) use ($columns, $unique) {
            $sameColumns = array_values($index['columns'] ?? []) === array_values($columns);
            return $sameColumns && (!$unique || ($index['unique'] ?? false));
        });

        if ($existing) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $unique, $name) {
            $unique ? $blueprint->unique($columns, $name) : $blueprint->index($columns, $name);
        });
    }
};
