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
        Schema::create('enfermedades_tipo_flors', function (Blueprint $table) {
            $table->id();  
            $table->unsignedBigInteger('id_tipo_flor');
            $table->unsignedBigInteger('id_enfermedad');
            $table->foreign('id_tipo_flor')->references('id')->on('cat_tipo_flors')->onDelete('cascade');
            $table->foreign('id_enfermedad')->references('id')->on('cat_enfermedades')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enfermedades_tipo_flors');
    }
};
