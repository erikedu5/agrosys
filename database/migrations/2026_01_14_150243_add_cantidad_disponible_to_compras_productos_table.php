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
        Schema::table('compras_productos', function (Blueprint $table) {
            $table->decimal('cantidad_disponible', 10, 2)->default(0)->after('cantidad');
        });

        // Initialize cantidad_disponible with the original cantidad for existing records
        // Assumes that current stock logic didn't deplete these specific records, but gives a starting point for FIFO.
        // In a real scenario, we might want to set this to 0 if we can't guarantee existence, 
        // but for enabling FIFO moving forward on "new" old stock, this is a reasonable approximation or the user can adjust.
        \Illuminate\Support\Facades\DB::statement("UPDATE compras_productos SET cantidad_disponible = cantidad");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compras_productos', function (Blueprint $table) {
            $table->dropColumn('cantidad_disponible');
        });
    }
};
