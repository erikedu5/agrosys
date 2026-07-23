<?php

namespace App\Services;

use App\Models\AbonoCuenta;
use App\Models\AltaInventario;
use App\Models\Clientes;
use App\Models\OfflineOperation;
use App\Models\Producto;
use App\Models\ProductoVenta;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfflineSaleProcessor
{
    public function process(OfflineOperation $operation): array
    {
        if ($operation->status === 'confirmed' && $operation->result) return $operation->result;

        return DB::transaction(function () use ($operation) {
            $operation = OfflineOperation::query()->lockForUpdate()->findOrFail($operation->operation_id);
            if ($operation->status === 'confirmed' && $operation->result) return $operation->result;

            $payload = $operation->payload;
            $items = collect($payload['items'] ?? []);
            if ($items->isEmpty()) throw ValidationException::withMessages(['items' => 'La venta no contiene partidas.']);

            $customer = Clientes::query()
                ->whereKey($payload['customerId'] ?? null)
                ->where('id_sucursal', $operation->branch_id)
                ->where('activo', true)
                ->lockForUpdate()
                ->first();
            if (!$customer) throw ValidationException::withMessages(['customerId' => 'El cliente no está disponible en esta sucursal.']);

            $total = round((float) ($payload['total'] ?? $items->sum(fn ($item) => (float) ($item['total'] ?? 0))), 2);
            $saleType = ($payload['saleType'] ?? 'Contado') === 'Credito' ? 'Credito' : 'Contado';
            $paymentAmount = round((float) ($payload['payments'][0]['amount'] ?? ($saleType === 'Contado' ? $total : 0)), 2);

            $sale = Venta::create([
                'client_sale_id' => $operation->aggregate_id,
                'operation_id' => $operation->operation_id,
                'device_id' => $operation->device_id,
                'occurred_at' => $operation->occurred_at,
                'id_cliente' => $customer->id,
                'total' => $total,
                'id_usuario' => $operation->user_id,
                'tipo_venta' => $saleType,
                'venta_pagada' => $saleType === 'Contado',
                'fecha_pago' => $saleType === 'Contado' ? now() : null,
                'id_sucursal' => $operation->branch_id,
            ]);

            foreach ($items as $item) {
                $product = Producto::query()->whereKey($item['productId'] ?? null)->lockForUpdate()->first();
                if (!$product) throw ValidationException::withMessages(['items' => 'Un producto fue desactivado o eliminado.']);
                $quantity = (float) ($item['quantity'] ?? 0);
                if ($quantity <= 0) throw ValidationException::withMessages(['items' => 'La cantidad debe ser mayor a cero.']);
                $expectedPrice = round((float) $product->precio_ieps * (1 - ((float) $customer->porcentaje_descuento / 100)), 2);
                if (abs((float) ($item['unitPrice'] ?? 0) - $expectedPrice) > 0.009) throw ValidationException::withMessages(['items' => "El precio de {$product->nombre} cambió desde la última sincronización."]);

                ProductoVenta::create(['id_producto' => $product->id, 'id_venta' => $sale->id, 'cantidad' => $quantity, 'total_productos' => round((float) ($item['total'] ?? 0), 2)]);
                $lastStock = AltaInventario::query()->where('id_producto', $product->id)->where('id_sucursal', $operation->branch_id)->latest('created_at')->latest('id')->lockForUpdate()->first();
                $before = (float) ($lastStock?->cantidad_nueva ?? 0);
                if (!config('offline.allow_negative_stock') && $before < $quantity) throw ValidationException::withMessages(['items' => "Existencia insuficiente para {$product->nombre}."]);
                AltaInventario::create(['cantidad_actual' => $before, 'cantidad_nueva' => $before - $quantity, 'id_usuario' => $operation->user_id, 'id_producto' => $product->id, 'id_sucursal' => $operation->branch_id, 'tipo_evento' => AltaInventario::EVENTO_VENTA]);
            }

            AbonoCuenta::create(['cantidad_abonada' => $paymentAmount, 'cuenta_pagada' => $saleType === 'Contado', 'id_cliente' => $customer->id, 'id_usuario' => $operation->user_id, 'is_active' => $saleType !== 'Contado', 'id_sucursal' => $operation->branch_id]);
            $customer->adeudo_total += $total;
            $customer->abono_total += $paymentAmount;
            $customer->balance = $customer->adeudo_total - $customer->abono_total;
            $customer->save();

            $result = ['saleId' => $operation->aggregate_id, 'operationId' => $operation->operation_id, 'status' => 'confirmed', 'localFolio' => $payload['localFolio'] ?? $operation->aggregate_id, 'serverFolio' => (string) $sale->id, 'serverSaleId' => $sale->id];
            $operation->update(['status' => 'confirmed', 'result' => $result, 'error_code' => null, 'error_message' => null]);
            return $result;
        }, 3);
    }
}
