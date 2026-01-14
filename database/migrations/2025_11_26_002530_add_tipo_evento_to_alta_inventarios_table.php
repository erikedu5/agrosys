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
        Schema::table('alta_inventarios', function (Blueprint $table) {
            if (!Schema::hasColumn('alta_inventarios', 'tipo_evento')) {
                $table->string('tipo_evento', 50)
                    ->default('alta_inventario')
                    ->after('cantidad_nueva');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alta_inventarios', function (Blueprint $table) {
            if (Schema::hasColumn('alta_inventarios', 'tipo_evento')) {
                $table->dropColumn('tipo_evento');
            }
        });
    }
};
