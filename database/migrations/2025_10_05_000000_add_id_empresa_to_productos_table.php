<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (!Schema::hasColumn('productos', 'id_empresa')) {
                $table->unsignedBigInteger('id_empresa')->nullable()->after('id_marca');
            }

            $table->dropUnique('productos_nombre_tamano_unique');
            $table->foreign('id_empresa')->references('id')->on('empresas');
            $table->unique(['id_empresa', 'nombre', 'tamano'], 'productos_empresa_nombre_tamano_unique');
        });

        // Intentar rellenar la empresa de productos existentes con base en su última alta de inventario
        $productosSinEmpresa = DB::table('productos')
            ->whereNull('id_empresa')
            ->pluck('id');

        foreach ($productosSinEmpresa as $productoId) {
            $sucursalId = DB::table('alta_inventarios')
                ->where('id_producto', $productoId)
                ->orderByDesc('id')
                ->value('id_sucursal');

            if (!$sucursalId) {
                continue;
            }

            $empresaId = DB::table('sucursales')
                ->where('id', $sucursalId)
                ->value('id_empresa');

            if ($empresaId) {
                DB::table('productos')
                    ->where('id', $productoId)
                    ->update(['id_empresa' => $empresaId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropUnique('productos_empresa_nombre_tamano_unique');
            $table->dropForeign(['id_empresa']);
            $table->dropColumn('id_empresa');
            $table->unique(['nombre', 'tamano'], 'productos_nombre_tamano_unique');
        });
    }
};
