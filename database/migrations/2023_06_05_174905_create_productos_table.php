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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->unsignedBigInteger('id_clasificacion');
            $table->unsignedBigInteger('id_marca');
            $table->foreign('id_clasificacion')->references('id')->on('cat_clasificacions');
            $table->foreign('id_marca')->references('id')->on('cat_marcas');
            $table->string('cantidad');
            $table->string('precio_unitario');
            $table->string('precio_ieps');
            $table->string('ieps');
            $table->string('tamano');
            $table->unsignedBigInteger('id_usuario');
            $table->foreign('id_usuario')->references('id')->on('users');
            $table->string('ingrediente_activo');
            $table->unsignedBigInteger('id_sucursal');
            $table->foreign('id_sucursal')->references('id')->on('sucursales');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
