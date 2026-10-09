<?php

namespace App\Services;

use App\Models\Compras;
use App\Models\ComprasAbonos;
use App\Models\ComprasProductos;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\User;
use App\Services\Pos\Decimal;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class PurchaseManagement
{
    public function record(User $user, Sucursales $branch, array $data): Compras
    {
        $key = strtolower($data['idempotency_key']);
        $hash = PurchaseIdempotency::fingerprint($data, $branch->id);
        if ($existing = PurchaseIdempotency::find($branch->id_empresa, $key, $hash)) {
            return $existing;
        }
        try {
            return DB::transaction(function () use ($user, $branch, $data, $key, $hash) {
                $ids = collect($data['productos'])->pluck('id')->unique()->sort()->values();
                $products = Producto::withoutGlobalScope('empresa')->where('id_empresa', $branch->id_empresa)
                    ->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
                abort_unless($products->count() === $ids->count(), 422, 'Un producto ya no está disponible.');
                $date = Carbon::parse($data['fecha_compra']);
                $purchase = Compras::create(['proveedor' => $data['proveedor'], 'fecha_compra' => $date,
                    'fecha_credito' => $date->copy()->addDays(30), 'total_compra' => $data['total_compra'],
                    'total_credito' => $data['total_credito'], 'status' => $data['status'],
                    'id_sucursal' => $branch->id, 'id_empresa' => $branch->id_empresa,
                    'idempotency_key' => $key, 'request_hash' => $hash]);
                foreach ($data['productos'] as $item) {
                    ComprasProductos::create(['id_compra' => $purchase->id, 'id_producto' => $item['id'],
                        'cantidad' => $item['cantidad'], 'cantidad_disponible' => $item['cantidad'], 'precio' => $item['precio_compra']]);
                    app(InventoryManagement::class)->addStock($user, $branch, (int) $item['id'], $item['cantidad']);
                }
                foreach ($data['abonos'] ?? [] as $payment) {
                    ComprasAbonos::create(['id_compra' => $purchase->id, 'cantidad_abonada' => $payment['cantidad_abonada']]);
                }

                return $purchase;
            }, 3);
        } catch (UniqueConstraintViolationException $e) {
            return PurchaseIdempotency::find($branch->id_empresa, $key, $hash) ?? throw $e;
        }
    }

    public function webPayments(Sucursales $branch, int $id, array $data): Compras
    {
        return DB::transaction(function () use ($branch, $id, $data) {
            $purchase = Compras::where('id_sucursal', $branch->id)->lockForUpdate()->findOrFail($id);
            $payments = array_filter($data['abonos'] ?? [], fn ($row) => ! isset($row['id']));
            $units = array_sum(array_map(fn ($row) => Decimal::units($row['cantidad_abonada'], 'abonos'), $payments));
            $submitted = Decimal::units($data['total_credito'], 'total_credito');
            $expected = isset($data['expected_debt']) ? Decimal::units($data['expected_debt'], 'expected_debt') : $submitted + $units;
            $debt = Decimal::units($purchase->total_credito, 'debt');
            abort_unless($expected === $debt, 409, 'El saldo cambió. Recarga la compra antes de abonar.');
            abort_if($units > $debt || $submitted !== $debt - $units, 422, 'El saldo debe corresponder a los abonos registrados.');
            foreach ($payments as $row) {
                ComprasAbonos::create(['id_compra' => $id, 'cantidad_abonada' => Decimal::format(Decimal::units($row['cantidad_abonada'], 'abonos'))]);
            }
            $purchase->total_credito = Decimal::format($debt - $units);
            if ($debt === $units) {
                $purchase->status = 'pagado';
            }
            $purchase->save();

            return $purchase;
        }, 3);
    }

    public function payment(Sucursales $branch, int $id, string $amount, string $expectedDebt): Compras
    {
        return DB::transaction(function () use ($branch, $id, $amount, $expectedDebt) {
            $purchase = Compras::where('id_sucursal', $branch->id)->lockForUpdate()->findOrFail($id);
            $debt = Decimal::units($purchase->total_credito, 'debt');
            abort_unless($debt === Decimal::units($expectedDebt, 'expected_debt'), 409, 'El saldo cambió. Recarga la compra antes de abonar.');
            $units = Decimal::units($amount, 'amount');
            abort_unless($units > 0 && $units <= $debt, 422, 'El abono debe ser positivo y no superar el saldo.');
            ComprasAbonos::create(['id_compra' => $id, 'cantidad_abonada' => Decimal::format($units)]);
            $purchase->total_credito = Decimal::format($debt - $units);
            if ($debt === $units) {
                $purchase->status = 'pagado';
            }
            $purchase->save();

            return $purchase;
        }, 3);
    }
}
