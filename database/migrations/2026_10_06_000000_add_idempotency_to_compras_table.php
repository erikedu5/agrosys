<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            // Existing purchases and internal transfers do not have request keys.
            $table->foreignId('id_empresa')->nullable()->constrained('empresas');
            $table->uuid('idempotency_key')->nullable();
            $table->char('request_hash', 64)->nullable();
            $table->unique(['id_empresa', 'idempotency_key'], 'compras_empresa_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropForeign(['id_empresa']);
            $table->dropUnique('compras_empresa_idempotency_unique');
            $table->dropColumn(['id_empresa', 'idempotency_key', 'request_hash']);
        });
    }
};
