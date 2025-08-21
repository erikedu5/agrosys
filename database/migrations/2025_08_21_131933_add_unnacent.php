<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE productos
            MODIFY nombre VARCHAR(255) COLLATE utf8mb4_0900_ai_ci,
            MODIFY ingrediente_activo VARCHAR(255) COLLATE utf8mb4_0900_ai_ci;

            ALTER TABLE cat_enfermedades
            MODIFY nombre VARCHAR(255) COLLATE utf8mb4_0900_ai_ci;

            ALTER TABLE cat_tipo_flors
            MODIFY nombre VARCHAR(255) COLLATE utf8mb4_0900_ai_ci;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
