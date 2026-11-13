<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // null = sin límite / sin plazo.
            $table->decimal('limite_credito', 12, 2)->nullable();
            $table->unsignedInteger('plazo_credito_dias')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['limite_credito', 'plazo_credito_dias']);
        });
    }
};
