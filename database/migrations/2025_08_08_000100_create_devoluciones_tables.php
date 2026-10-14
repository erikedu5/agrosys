<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devoluciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_venta');
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_sucursal');
            $table->decimal('total_devuelto', 12, 2)->default(0);
            $table->string('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('devolucion_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_devolucion');
            $table->unsignedBigInteger('id_producto');
            $table->decimal('cantidad', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devolucion_detalles');
        Schema::dropIfExists('devoluciones');
    }
};

