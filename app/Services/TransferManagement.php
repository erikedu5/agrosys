<?php

namespace App\Services;

use App\Models\AltaInventario;
use App\Models\Compras;
use App\Models\ComprasProductos;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\Transferencia;
use App\Models\TransferenciaDetalle;
use App\Models\User;
use App\Services\Pos\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TransferManagement
{
    public function create(User $user, Sucursales $origin, array $data): Transferencia
    {
        Validator::make($data, ['id_sucursal_destino' => 'required|integer', 'observaciones' => 'nullable|string|max:2000',
            'productos' => 'required|array|min:1|max:100', 'productos.*.id' => 'required|integer|distinct',
            'productos.*.cantidad' => 'required|numeric|min:0.01|max:999999.99'])->validate();
        $destination = Sucursales::where('id_empresa', $origin->id_empresa)->findOrFail($data['id_sucursal_destino']);
        abort_if($destination->id === $origin->id, 422, 'Selecciona otra sucursal de la misma empresa.');

        return DB::transaction(function () use ($user, $origin, $destination, $data) {
            $ids = collect($data['productos'])->pluck('id')->sort()->values();
            $products = Producto::withoutGlobalScope('empresa')->where('id_empresa', $origin->id_empresa)
                ->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            abort_unless($products->count() === $ids->count(), 422, 'Un producto ya no está disponible.');
            $transfer = Transferencia::create(['folio' => 'TRANS-'.Str::ulid(), 'id_sucursal_origen' => $origin->id,
                'id_sucursal_destino' => $destination->id, 'id_usuario_envia' => $user->id,
                'fecha_envio' => now(), 'status' => 'pendiente', 'notas' => $data['observaciones'] ?? null]);
            foreach ($data['productos'] as $item) {
                TransferenciaDetalle::create(['id_transferencia' => $transfer->id, 'id_producto' => $item['id'],
                    'cantidad' => Decimal::format(Decimal::units($item['cantidad'], 'quantity')), 'lote_origen_id' => null]);
            }

            return $transfer;
        }, 3);
    }

    public function receive(User $user, Sucursales $destination, int $id): Transferencia
    {
        return DB::transaction(function () use ($user, $destination, $id) {
            $transfer = Transferencia::lockForUpdate()->findOrFail($id);
            abort_unless((int) $transfer->id_sucursal_destino === (int) $destination->id, 403, 'Solo la sucursal destino puede recibir.');
            $origin = Sucursales::where('id_empresa', $destination->id_empresa)->findOrFail($transfer->id_sucursal_origen);
            // A completed transfer is also idempotent across devices and web requests.
            if ($transfer->status === 'completado') {
                return $transfer;
            }
            abort_unless($transfer->status === 'pendiente', 409, 'La transferencia no está pendiente.');
            $details = $transfer->detalles()->orderBy('id_producto')->get();
            $ids = $details->pluck('id_producto')->unique()->sort()->values();
            $products = Producto::withoutGlobalScope('empresa')->where('id_empresa', $destination->id_empresa)
                ->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            abort_unless($products->count() === $ids->count(), 422, 'Un producto ya no está disponible.');
            $internal = Compras::create(['proveedor' => 'TRANSFERENCIA '.$transfer->folio, 'fecha_compra' => now(),
                'fecha_credito' => now(), 'total_compra' => 0, 'total_credito' => 0, 'status' => 'pagado',
                'id_sucursal' => $destination->id, 'id_empresa' => $destination->id_empresa]);
            $management = app(InventoryManagement::class);
            foreach ($details as $detail) {
                $units = Decimal::units($detail->cantidad, 'quantity');
                abort_unless($units > 0, 422, 'Cantidad inválida en la transferencia.');
                $stock = AltaInventario::where('id_producto', $detail->id_producto)->where('id_sucursal', $origin->id)
                    ->orderByDesc('id')->lockForUpdate()->first();
                abort_if(Decimal::units($stock?->cantidad_nueva ?? '0', 'stock', true) < $units, 422, 'Stock insuficiente en origen para '.$products[$detail->id_producto]->nombre.'.');
                $remaining = $units;
                $lots = ComprasProductos::select('compras_productos.*')->join('compras', 'compras.id', '=', 'compras_productos.id_compra')
                    ->where('compras.id_sucursal', $origin->id)->where('compras_productos.id_producto', $detail->id_producto)
                    ->where('cantidad_disponible', '>', 0)->orderBy('compras.fecha_compra')->orderBy('compras_productos.id')->lockForUpdate()->get();
                foreach ($lots as $lot) {
                    if ($remaining === 0) {
                        break;
                    }
                    $available = Decimal::units($lot->cantidad_disponible, 'quantity');
                    $take = min($remaining, $available);
                    $lot->cantidad_disponible = Decimal::format($available - $take);
                    $lot->save();
                    if (! $detail->lote_origen_id) {
                        $detail->lote_origen_id = $lot->id;
                        $detail->save();
                    }
                    $this->destinationLot($internal, $detail, $take, $lot->precio);
                    $remaining -= $take;
                }
                if ($remaining > 0) {
                    $this->destinationLot($internal, $detail, $remaining, $products[$detail->id_producto]->precio_unitario);
                }
                $management->moveStock($user, $origin, $detail->id_producto, -$units, AltaInventario::EVENTO_TRANSFERENCIA_SALIDA);
                $management->moveStock($user, $destination, $detail->id_producto, $units, AltaInventario::EVENTO_TRANSFERENCIA_ENTRADA);
            }
            $transfer->fill(['status' => 'completado', 'id_usuario_recibe' => $user->id, 'fecha_recepcion' => now()])->save();

            return $transfer;
        }, 3);
    }

    private function destinationLot(Compras $purchase, TransferenciaDetalle $detail, int $units, mixed $cost): void
    {
        ComprasProductos::create(['id_compra' => $purchase->id, 'id_producto' => $detail->id_producto,
            'cantidad' => Decimal::format($units), 'cantidad_disponible' => Decimal::format($units),
            'precio' => Decimal::format(Decimal::units($cost, 'cost'))]);
    }
}
