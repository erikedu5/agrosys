<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
            return;
        }

        Schema::table('productos', function (Blueprint $table) {
            $table->string('nombre', 255)->collation('utf8mb4_0900_ai_ci')->change();
            $table->string('ingrediente_activo', 255)->collation('utf8mb4_0900_ai_ci')->change();
        });

        Schema::table('cat_enfermedades', function (Blueprint $table) {
            $table->string('nombre', 255)->collation('utf8mb4_0900_ai_ci')->change();
        });

        Schema::table('cat_tipo_flors', function (Blueprint $table) {
            $table->string('nombre', 255)->collation('utf8mb4_0900_ai_ci')->change();
        });
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
            return;
        }

        // Ajusta la collation de "regreso" a la que usabas antes si es distinta.
        // Aquí uso utf8mb4_unicode_ci como ejemplo común.
        Schema::table('productos', function (Blueprint $table) {
            $table->string('nombre', 255)->collation('utf8mb4_unicode_ci')->change();
            $table->string('ingrediente_activo', 255)->collation('utf8mb4_unicode_ci')->change();
        });

        Schema::table('cat_enfermedades', function (Blueprint $table) {
            $table->string('nombre', 255)->collation('utf8mb4_unicode_ci')->change();
        });

        Schema::table('cat_tipo_flors', function (Blueprint $table) {
            $table->string('nombre', 255)->collation('utf8mb4_unicode_ci')->change();
        });
    }
};
