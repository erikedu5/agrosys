<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ventas a clientes que requieren factura que quedaron sin registro de factura
        // (antes solo se registraban con la facturación automática activa).
        DB::table('ventas')
            ->join('clientes', 'clientes.id', '=', 'ventas.id_cliente')
            ->leftJoin('facturas', 'facturas.id_venta', '=', 'ventas.id')
            ->where('clientes.requiereFactura', true)
            ->whereNull('facturas.id')
            ->orderBy('ventas.id')
            ->get(['ventas.id', 'ventas.id_cliente', 'ventas.created_at'])
            ->each(fn ($venta) => DB::table('facturas')->insert([
                'id_venta' => $venta->id,
                'id_cliente' => $venta->id_cliente,
                'facturaCompleta' => false,
                'factura_status' => 'pendiente',
                'created_at' => $venta->created_at,
                'updated_at' => now(),
            ]));
    }

    public function down(): void
    {
        // Sin reversa: no se distinguen de las facturas pendientes creadas después.
    }
};
