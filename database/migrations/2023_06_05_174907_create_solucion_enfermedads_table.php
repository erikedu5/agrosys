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
        Schema::create('solucion_enfermedads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_producto');
            $table->unsignedBigInteger('id_enfermedad_tipo_flor');
            $table->foreign('id_producto')->references('id')->on('productos')->onDelete('cascade');
            $table->foreign('id_enfermedad_tipo_flor')->references('id')->on('enfermedades_tipo_flors')->onDelete('cascade');
            $table->string('dosis_bomba_ml');
            $table->string('dosis_tambo_ml');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solucion_enfermedads');
    }
};
