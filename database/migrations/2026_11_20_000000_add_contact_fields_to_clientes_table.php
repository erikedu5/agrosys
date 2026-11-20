<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('nombre_contacto')->nullable();
            $table->string('telefono_contacto', 30)->nullable();
            $table->string('direccion', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['nombre_contacto', 'telefono_contacto', 'direccion']);
        });
    }
};
