<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transferencias', function (Blueprint $table) {
            // Eliminar restricción de clave foránea primero si existe
            if (Schema::hasColumn('transferencias', 'id_usuario')) {
                $table->dropForeign(['id_usuario']);
                $table->dropColumn('id_usuario');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transferencias', function (Blueprint $table) {
            // Restaurar columna para rollback
            $table->unsignedBigInteger('id_usuario')->nullable();
        });
    }
};
