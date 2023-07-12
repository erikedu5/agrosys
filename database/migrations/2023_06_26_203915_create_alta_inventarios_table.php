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
        Schema::create('alta_inventarios', function (Blueprint $table) {
            $table->id();
            $table->string('cantidad_actual');
            $table->string('cantidad_nueva');
            $table->string('id_usuario');
            $table->string('id_producto');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alta_inventarios');
    }
};
