<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El precio de venta ahora depende solo del tipo de precio asignado al cliente.
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('porcentaje_descuento');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('porcentaje_descuento')->default('0');
        });
    }
};
