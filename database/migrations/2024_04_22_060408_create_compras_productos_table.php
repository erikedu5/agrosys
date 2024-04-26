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
        Schema::create('compras_productos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_compra');
            $table->foreign('id_compra')->references('id')->on('compras');
            $table->unsignedBigInteger('id_producto');
            $table->foreign('id_producto')->references('id')->on('productos');
            $table->unsignedInteger('cantidad');
            $table->decimal('precio' , 10,2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras_productos');
    }
};
