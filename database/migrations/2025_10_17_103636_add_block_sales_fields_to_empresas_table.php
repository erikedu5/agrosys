<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->boolean('ventas_bloqueadas')
                ->default(false)
                ->after('mostrar_campos_precio');
            $table->text('motivo_bloqueo')
                ->nullable()
                ->after('ventas_bloqueadas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['ventas_bloqueadas', 'motivo_bloqueo']);
        });
    }
};
