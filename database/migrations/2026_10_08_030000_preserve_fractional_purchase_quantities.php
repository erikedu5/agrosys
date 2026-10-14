<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras_productos', function (Blueprint $table) {
            $table->decimal('cantidad', 10, 2)->change();
        });
    }

    public function down(): void
    {
        // Keep decimal storage: converting back to integer would lose received quantities.
    }
};
