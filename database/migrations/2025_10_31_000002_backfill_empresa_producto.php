<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Vincular por historial de inventario (alta_inventarios -> sucursales -> empresas)
        DB::statement(<<<'SQL'
            INSERT INTO empresa_producto (id_empresa, id_producto, created_at, updated_at)
            SELECT s.id_empresa, ai.id_producto, NOW(), NOW()
            FROM alta_inventarios ai
            INNER JOIN sucursales s ON s.id = ai.id_sucursal
            LEFT JOIN empresa_producto ep
                ON ep.id_empresa = s.id_empresa
               AND ep.id_producto = ai.id_producto
            WHERE ep.id IS NULL
            GROUP BY s.id_empresa, ai.id_producto
        SQL);

        // 2) Vincular por propietario del producto (users.id_empresa)
        DB::statement(<<<'SQL'
            INSERT INTO empresa_producto (id_empresa, id_producto, created_at, updated_at)
            SELECT u.id_empresa, p.id, NOW(), NOW()
            FROM productos p
            INNER JOIN users u ON u.id = p.id_usuario
            LEFT JOIN empresa_producto ep
                ON ep.id_empresa = u.id_empresa
               AND ep.id_producto = p.id
            WHERE u.id_empresa IS NOT NULL
              AND ep.id IS NULL
        SQL);

        // 3) Vincular por sucursal del usuario del producto (users.id_sucursal -> sucursales.id_empresa)
        DB::statement(<<<'SQL'
            INSERT INTO empresa_producto (id_empresa, id_producto, created_at, updated_at)
            SELECT s.id_empresa, p.id, NOW(), NOW()
            FROM productos p
            INNER JOIN users u ON u.id = p.id_usuario
            INNER JOIN sucursales s ON s.id = u.id_sucursal
            LEFT JOIN empresa_producto ep
                ON ep.id_empresa = s.id_empresa
               AND ep.id_producto = p.id
            WHERE ep.id IS NULL
        SQL);

        // 4) Si solo hay 1 empresa, asegurar que todos los productos queden vinculados a esa empresa
        $empresaCount = (int) DB::table('empresas')->count();
        if ($empresaCount === 1) {
            $empresaId = (int) DB::table('empresas')->value('id');
            DB::statement(<<<SQL
                INSERT INTO empresa_producto (id_empresa, id_producto, created_at, updated_at)
                SELECT {$empresaId} AS id_empresa, p.id, NOW(), NOW()
                FROM productos p
                LEFT JOIN empresa_producto ep
                  ON ep.id_empresa = {$empresaId}
                 AND ep.id_producto = p.id
                WHERE ep.id IS NULL
            SQL);
        }
    }

    public function down(): void
    {
        // No se deshace el backfill automáticamente.
    }
};

