<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transferencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_sucursal_origen');
            $table->unsignedBigInteger('id_sucursal_destino');
            $table->unsignedBigInteger('id_usuario');
            $table->enum('status', ['pendiente', 'completado', 'cancelado'])->default('pendiente');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->foreign('id_sucursal_origen')->references('id')->on('sucursales');
            $table->foreign('id_sucursal_destino')->references('id')->on('sucursales');
            $table->foreign('id_usuario')->references('id')->on('users');
        });

        Schema::create('transferencia_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_transferencia');
            $table->unsignedBigInteger('id_producto');
            $table->decimal('cantidad', 10, 2);
            $table->unsignedBigInteger('lote_origen_id')->nullable();
            $table->timestamps();

            $table->foreign('id_transferencia')->references('id')->on('transferencias')->onDelete('cascade');
            $table->foreign('id_producto')->references('id')->on('productos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferencia_detalles');
        Schema::dropIfExists('transferencias');
    }
};
