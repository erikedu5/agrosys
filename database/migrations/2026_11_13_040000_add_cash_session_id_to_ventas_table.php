<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            // Caja en la que se hizo la venta, para desglosar contado/crédito en el corte.
            $table->uuid('cash_session_id')->nullable()->index();
        });

        // Ventas anteriores: solo las que generaron un cobro en caja quedaron ligadas a una sesión.
        DB::table('cash_movements')->where('source', 'like', 'sale:%')->orderBy('id')
            ->each(fn ($movement) => DB::table('ventas')->where('id', (int) substr($movement->source, 5))
                ->update(['cash_session_id' => $movement->session_id]));
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex(['cash_session_id']);
            $table->dropColumn('cash_session_id');
        });
    }
};
