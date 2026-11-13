<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->decimal('precio_publico', 10, 2)->default(0);
            $table->decimal('precio_preferencial_1', 10, 2)->default(0);
            $table->decimal('precio_preferencial_2', 10, 2)->default(0);
        });

        // Los tres precios inician con el precio de venta existente.
        DB::table('productos')->update([
            'precio_publico' => DB::raw('precio_ieps'),
            'precio_preferencial_1' => DB::raw('precio_ieps'),
            'precio_preferencial_2' => DB::raw('precio_ieps'),
        ]);

        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['precio_ieps', 'ieps']);
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->string('tipo_precio', 20)->default('publico');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('tipo_precio');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->decimal('precio_ieps', 10, 2)->default(0);
            $table->decimal('ieps', 10, 2)->default(0);
        });

        DB::table('productos')->update(['precio_ieps' => DB::raw('precio_publico')]);

        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['precio_publico', 'precio_preferencial_1', 'precio_preferencial_2']);
        });
    }
};
