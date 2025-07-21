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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_sucursal');
            $table->unsignedBigInteger('id_producto');
            $table->integer('cantidad');
            $table->string('nombre_solicitante');
            $table->string('numero_solicitante');
            $table->boolean('completado')->default(false);
            $table->foreign('id_sucursal')->references('id')->on('sucursales');
            $table->foreign('id_producto')->references('id')->on('productos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
