<?php

namespace App\Services\Pos;

use App\Models\AltaInventario;
use App\Models\Clientes;
use App\Models\OfflineDevice;
use App\Models\OfflineOperation;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogSnapshots
{
    public function page(User $user, OfflineDevice $device, array $data, bool $incremental): array
    {
        if (! empty($data['page_token'])) {
            $token = $this->decode($data['page_token']);
            abort_unless(($token['kind'] ?? null) === ($incremental ? 'pull' : 'bootstrap'), 410, 'Página de otro flujo.');
            $snapshot = $this->owned($token['id'] ?? '', $user, $device);

            return $this->slice($snapshot, $token['offset'], $token['size'], $incremental);
        }
        $base = null;
        if ($incremental && ! empty($data['cursor'])) {
            $base = json_decode($this->owned($this->decode($data['cursor'])['id'] ?? '', $user, $device)->payload, true, flags: JSON_THROW_ON_ERROR)['catalog'];
        }
        // MySQL consistent reads must all use one repeatable-read transaction.
        // SQLite tests use their transaction snapshot. No table is reset or overwritten.
        if (DB::connection()->getDriverName() === 'mysql' && DB::transactionLevel() === 0) {
            DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        }
        $snapshot = DB::transaction(function () use ($user, $device, $data, $base) {
            $branch = Sucursales::findOrFail($device->branch_id);
            $id = (string) Str::uuid();
            $stockIds = AltaInventario::where('id_sucursal', $branch->id)->selectRaw('MAX(id)')->groupBy('id_producto');
            $stocks = AltaInventario::whereIn('id', $stockIds)->get()->keyBy('id_producto');
            $canSeeCosts = in_array('product.costs.manage', app(PosAccess::class)->permissions($user, $branch), true);
            $products = Producto::withoutGlobalScope('empresa')->withTrashed()->leftJoin('cat_clasificacions', 'cat_clasificacions.id', '=', 'productos.id_clasificacion')->select('productos.*', 'cat_clasificacions.nombre as classification')->where('productos.id_empresa', $branch->id_empresa)->orderBy('productos.id')->get()->map(function ($product) use ($stocks, $branch, $canSeeCosts) {
                $stock = $stocks->get($product->id);

                return ['id' => (string) $product->id, 'sku' => (string) $product->id, 'barcode' => $product->barcode ?? '', 'name' => $product->nombre, 'size' => $product->tamano, 'classification' => $product->classification, ...($canSeeCosts ? ['unitPrice' => Decimal::format(Decimal::units($product->precio_unitario, 'price'))] : []), 'price' => Decimal::format(Decimal::units($product->precio_ieps, 'price')), 'active' => ! $product->trashed(), 'branchId' => (string) $branch->id, 'serverQuantity' => Decimal::format(Decimal::units($stock?->cantidad_nueva ?? 0, 'stock', true))];
            })->all();
            $customers = Clientes::where('id_sucursal', $branch->id)->orderBy('id')->get()->map(fn ($customer) => ['id' => (string) $customer->id, 'branchId' => (string) $branch->id, 'name' => $customer->nombre, 'active' => (bool) $customer->activo, 'discountPercentage' => Decimal::format(Decimal::units($customer->porcentaje_descuento, 'discount')), 'debtTotal' => Decimal::format(Decimal::units($customer->adeudo_total, 'debt')), 'paymentTotal' => Decimal::format(Decimal::units($customer->abono_total, 'payment')), 'balance' => Decimal::format(Decimal::units($customer->balance, 'balance', true))])->all();
            $catalog = ['products' => $products, 'customers' => $customers];
            $changes = [];
            foreach ($catalog as $kind => $rows) {
                $old = collect($base[$kind] ?? [])->keyBy('id');
                foreach ($rows as $row) {
                    if ($old->get($row['id']) !== $row) {
                        $changes[] = ['kind' => $kind, 'value' => $row];
                    }
                    $old->forget($row['id']);
                }
                foreach ($old as $row) {
                    $changes[] = ['kind' => $kind, 'value' => [...$row, 'active' => false, 'deleted' => true]];
                }
            }
            $operations = OfflineOperation::where('user_id', $user->id)->where('device_id', $device->id)->where('branch_id', $branch->id)->whereIn('operation_id', $data['known_operation_ids'] ?? [])->get()->keyBy('operation_id');
            $receipts = collect($data['known_operation_ids'] ?? [])->map(function ($operationId) use ($operations) {
                $operation = $operations->get($operationId);

                return ['operationId' => $operationId, 'originalStatus' => $operation?->status ?? 'unknown', 'includedInSnapshot' => $operation?->status === 'confirmed', 'result' => $operation?->result];
            })->all();
            $payload = ['catalog' => $catalog, 'changes' => $changes, 'operationReceipts' => $receipts, 'branch' => ['id' => (string) $branch->id, 'companyId' => (string) $branch->id_empresa, 'name' => $branch->nombre, 'defaultCustomerId' => $branch->id_cliente_publico ? (string) $branch->id_cliente_publico : null], 'user' => ['id' => (string) $user->id, 'permissions' => app(PosAccess::class)->permissions($user, $branch)]];
            $created = now();
            DB::table('pos_snapshots')->insert(['id' => $id, 'device_id' => $device->id, 'user_id' => $user->id, 'branch_id' => $branch->id, 'payload' => json_encode($payload, JSON_THROW_ON_ERROR), 'created_at' => $created, 'expires_at' => $created->copy()->addHours(config('pos.snapshot_hours'))]);

            return DB::table('pos_snapshots')->where('id', $id)->first();
        });

        return $this->slice($snapshot, 0, $data['page_size'] ?? config('pos.page_size'), $incremental);
    }

    private function slice(object $snapshot, int $offset, int $size, bool $incremental): array
    {
        $payload = json_decode($snapshot->payload, true, flags: JSON_THROW_ON_ERROR);
        $changes = array_slice($payload['changes'], $offset, $size);
        $more = $offset + $size < count($payload['changes']);

        return [
            'schemaVersion' => 2, 'snapshotRevision' => $snapshot->id, 'syncedAt' => Carbon::parse($snapshot->created_at)->toIso8601String(),
            'user' => $payload['user'], 'branch' => $payload['branch'],
            'products' => array_values(array_column(array_filter($changes, fn ($change) => $change['kind'] === 'products'), 'value')),
            'customers' => array_values(array_column(array_filter($changes, fn ($change) => $change['kind'] === 'customers'), 'value')),
            // Stage all pages; apply receipts only together with the complete snapshot/delta.
            'operationReceipts' => $more ? [] : $payload['operationReceipts'],
            'hasMore' => $more,
            'pageToken' => $more ? Crypt::encryptString(json_encode(['id' => $snapshot->id, 'offset' => $offset + $size, 'size' => $size, 'kind' => $incremental ? 'pull' : 'bootstrap'])) : null,
            'nextCursor' => $more ? null : Crypt::encryptString(json_encode(['id' => $snapshot->id])),
        ];
    }

    private function owned(string $id, User $user, OfflineDevice $device): object
    {
        $snapshot = DB::table('pos_snapshots')->where('id', $id)->where('user_id', $user->id)->where('device_id', $device->id)->where('branch_id', $device->branch_id)->where('expires_at', '>', now())->first();
        abort_unless($snapshot, 410, 'Snapshot o cursor expirado. Conserva pendientes y ejecuta bootstrap.');

        return $snapshot;
    }

    private function decode(string $token): array
    {
        try {
            $data = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($data) || ! isset($data['id']) || ! Str::isUuid($data['id'])) {
                throw new \RuntimeException;
            }

            return $data;
        } catch (\Throwable $e) {
            abort(410, 'Cursor o página inválidos. Ejecuta bootstrap conservando pendientes.');
        }
    }
}
