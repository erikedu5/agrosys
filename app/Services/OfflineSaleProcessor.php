<?php

namespace App\Services;

use App\Models\AbonoCuenta;
use App\Models\AltaInventario;
use App\Models\Clientes;
use App\Models\OfflineDevice;
use App\Models\OfflineOperation;
use App\Models\ProductoVenta;
use App\Models\Sucursales;
use App\Models\Venta;
use App\Services\Pos\Decimal;
use App\Services\Pos\SaleAmounts;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfflineSaleProcessor
{
    public function process(OfflineOperation $operation): array
    {
        if ($operation->status === 'confirmed' && $operation->result) {
            return $operation->result;
        }

        return DB::transaction(function () use ($operation) {
            $operation = OfflineOperation::query()->lockForUpdate()->findOrFail($operation->operation_id);
            if ($operation->status === 'confirmed' && $operation->result) {
                return $operation->result;
            }

            $device = OfflineDevice::lockForUpdate()->findOrFail($operation->device_id);
            abort_unless($device->authorized && ! $device->revoked_at, 403, 'Dispositivo revocado.');
            $branch = Sucursales::with('empresa')->findOrFail($operation->branch_id);
            abort_unless($branch->empresa && ! $branch->empresa->ventas_bloqueadas, 403, 'Ventas bloqueadas.');
            $payload = $operation->payload;
            $items = collect($payload['items'] ?? []);
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'La venta no contiene partidas.']);
            }

            $customer = Clientes::query()
                ->whereKey($payload['customerId'] ?? null)
                ->where('id_sucursal', $operation->branch_id)
                ->where('activo', true)
                ->lockForUpdate()
                ->first();
            if (! $customer) {
                throw ValidationException::withMessages(['customerId' => 'El cliente no está disponible en esta sucursal.']);
            }

            $amounts = app(SaleAmounts::class)->validate($payload, $customer, $branch);
            $total = Decimal::format($amounts['total']);
            $paymentAmount = Decimal::format($amounts['payment']);
            $saleType = $payload['saleType'];
            $debt = Decimal::units($customer->adeudo_total, 'adeudo_total') + $amounts['total'];
            $payments = Decimal::units($customer->abono_total, 'abono_total') + $amounts['payment'];
            if (max($debt, $payments) > 9999999999) {
                throw ValidationException::withMessages(['balance' => 'El saldo excede el rango permitido.']);
            }

            $sale = Venta::create([
                'client_sale_id' => $operation->aggregate_id,
                'operation_id' => $operation->operation_id,
                'device_id' => $operation->device_id,
                'occurred_at' => $operation->occurred_at,
                'id_cliente' => $customer->id,
                'total' => $total,
                'id_usuario' => $operation->user_id,
                'tipo_venta' => $saleType,
                'venta_pagada' => $amounts['paid'],
                'fecha_pago' => $amounts['paid'] ? now() : null,
                'id_sucursal' => $operation->branch_id,
            ]);

            foreach ($amounts['items'] as $validated) {
                $product = $validated['product'];
                $quantity = $validated['quantity'];
                $item = ['total' => Decimal::format($validated['total'])];
                ProductoVenta::create(['id_producto' => $product->id, 'id_venta' => $sale->id, 'cantidad' => Decimal::format($quantity), 'total_productos' => $item['total']]);
                $lastStock = AltaInventario::query()->where('id_producto', $product->id)->where('id_sucursal', $operation->branch_id)->latest('created_at')->latest('id')->lockForUpdate()->first();
                $before = Decimal::units($lastStock?->cantidad_nueva ?? 0, 'stock', true);
                if (! config('offline.allow_negative_stock') && $before < $quantity) {
                    throw ValidationException::withMessages(['items' => "Existencia insuficiente para {$product->nombre}."]);
                }
                AltaInventario::create(['cantidad_actual' => Decimal::format($before), 'cantidad_nueva' => Decimal::format($before - $quantity), 'id_usuario' => $operation->user_id, 'id_producto' => $product->id, 'id_sucursal' => $operation->branch_id, 'tipo_evento' => AltaInventario::EVENTO_VENTA]);
            }

            AbonoCuenta::create(['cantidad_abonada' => $paymentAmount, 'cuenta_pagada' => $amounts['paid'], 'id_cliente' => $customer->id, 'id_usuario' => $operation->user_id, 'is_active' => ! $amounts['paid'], 'id_sucursal' => $operation->branch_id]);
            $customer->adeudo_total = Decimal::format($debt);
            $customer->abono_total = Decimal::format($payments);
            $customer->balance = Decimal::format($debt - $payments);
            $customer->save();

            $result = ['saleId' => $operation->aggregate_id, 'operationId' => $operation->operation_id, 'status' => 'confirmed', 'originalStatus' => 'confirmed', 'commitRevision' => $operation->operation_id, 'localFolio' => $payload['localFolio'] ?? $operation->aggregate_id, 'serverFolio' => (string) $sale->id, 'serverSaleId' => $sale->id];
            $operation->update(['status' => 'confirmed', 'result' => $result, 'error_code' => null, 'error_message' => null]);

            return $result;
        }, 3);
    }
}
