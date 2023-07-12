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
        Schema::create('producto_ventas', function (Blueprint $table) {
            $table->id();   
            $table->unsignedBigInteger('id_producto');
            $table->unsignedBigInteger('id_venta');
            $table->foreign('id_producto')->references('id')->on('productos');
            $table->foreign('id_venta')->references('id')->on('ventas');
            $table->string('cantidad');
            $table->string('total_productos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto_ventas');
    }
};
