<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compras;
use App\Models\ComprasAbonos;
use App\Models\ComprasProductos;
use App\Models\OfflineDevice;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\Transferencia;
use App\Services\Pos\Decimal;
use App\Services\Pos\PosAccess;
use App\Services\PurchaseManagement;
use App\Services\TransferManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosStockWorkflowController extends Controller
{
    private function authorizeWorkflow(Request $request, PosAccess $access, string $permission): array
    {
        $data = $request->validate(['device_id' => 'required|uuid', 'branch_id' => 'required|integer|min:1']);
        $device = $access->device($request, strtolower($data['device_id']), $data['branch_id']);
        $branch = $access->branch($request->user(), $data['branch_id']);
        abort_unless(in_array($permission, $access->permissions($request->user(), $branch), true), 403, 'Operación no autorizada.');
        abort_if($branch->empresa->ventas_bloqueadas && $request->user()->tipo !== 'superAdmin', 403, 'Inventario bloqueado.');

        return [$device, $branch];
    }

    public function purchases(Request $request, PosAccess $access)
    {
        [, $branch] = $this->authorizeWorkflow($request, $access, 'purchase.read');
        $data = $request->validate(['q' => 'nullable|string|max:200', 'page' => 'sometimes|integer|min:1']);
        $rows = Compras::where('id_sucursal', $branch->id)->where('proveedor', 'like', '%'.($data['q'] ?? '').'%')
            ->orderByDesc('id')->paginate(20, ['*'], 'page', $data['page'] ?? 1);

        return response()->json(['items' => $rows->getCollection()->map(fn ($row) => $this->purchaseRow($row))->values(),
            'page' => $rows->currentPage(), 'hasMore' => $rows->hasMorePages()]);
    }

    private function purchaseRow(Compras $row): array
    {
        return ['id' => (string) $row->id, 'supplier' => $row->proveedor, 'date' => $row->fecha_compra,
            'dueDate' => $row->fecha_credito, 'status' => $row->status,
            'total' => Decimal::format(Decimal::units($row->total_compra, 'total')),
            'debt' => Decimal::format(Decimal::units($row->total_credito, 'debt'))];
    }

    public function purchaseDetail(Request $request, string $id, PosAccess $access)
    {
        [, $branch] = $this->authorizeWorkflow($request, $access, 'purchase.read');
        $purchase = Compras::where('id_sucursal', $branch->id)->findOrFail($id);
        $items = ComprasProductos::where('id_compra', $id)->get();
        $products = Producto::withoutGlobalScope('empresa')->withTrashed()->where('id_empresa', $branch->id_empresa)
            ->whereIn('id', $items->pluck('id_producto'))->get()->keyBy('id');

        return response()->json(['purchase' => [...$this->purchaseRow($purchase),
            'items' => $items->map(fn ($item) => ['productId' => (string) $item->id_producto,
                'name' => ($products->get($item->id_producto)?->nombre ?? 'Producto eliminado'),
                'size' => $products->get($item->id_producto)?->tamano ?? '',
                'quantity' => Decimal::format(Decimal::units($item->cantidad, 'quantity')),
                'cost' => Decimal::format(Decimal::units($item->precio, 'cost'))])->values(),
            'payments' => ComprasAbonos::where('id_compra', $id)->orderBy('id')->get()->map(fn ($row) => ['id' => (string) $row->id, 'amount' => Decimal::format(Decimal::units($row->cantidad_abonada, 'amount')), 'date' => $row->created_at])->values()]]);
    }

    public function purchaseCreate(Request $request, PosAccess $access)
    {
        [$device, $branch] = $this->authorizeWorkflow($request, $access, 'purchase.create');
        $data = $request->validate(['supplier' => 'required|string|max:255', 'date' => 'required|date_format:Y-m-d',
            'payment' => ['required', 'string', 'regex:/^\d{1,8}(?:\.\d{1,2})?$/'],
            'items' => 'required|array|min:1|max:100', 'items.*.productId' => 'required|integer|distinct',
            'items.*.quantity' => ['required', 'string', 'regex:/^\d{1,6}(?:\.\d{1,2})?$/'],
            'items.*.cost' => ['required', 'string', 'regex:/^\d{1,8}(?:\.\d{1,2})?$/']]);

        return $this->write($request, $device, $branch, 'purchase.create', null, function ($operation) use ($request, $branch, $data) {
            $total = 0;
            $items = [];
            foreach ($data['items'] as $item) {
                $units = Decimal::units($item['quantity'], 'quantity');
                abort_unless($units > 0, 422, 'La cantidad debe ser positiva.');
                $total += Decimal::roundRatio($units * Decimal::units($item['cost'], 'cost'), 100);
                abort_if($total > 9999999999, 422, 'El total supera el máximo permitido.');
                $items[] = ['id' => $item['productId'], 'cantidad' => $item['quantity'], 'precio_compra' => $item['cost']];
            }
            $payment = Decimal::units($data['payment'], 'payment');
            abort_if($payment > $total, 422, 'El pago no puede superar el total.');
            $purchase = app(PurchaseManagement::class)->record($request->user(), $branch,
                ['idempotency_key' => $operation, 'proveedor' => trim($data['supplier']), 'fecha_compra' => $data['date'],
                    'total_compra' => Decimal::format($total), 'total_credito' => Decimal::format($total - $payment),
                    'status' => $total === $payment ? 'pagado' : 'adeudo', 'productos' => $items,
                    'abonos' => $payment > 0 ? [['cantidad_abonada' => Decimal::format($payment)]] : []]);

            return ['purchaseId' => (string) $purchase->id];
        });
    }

    public function purchasePayment(Request $request, string $id, PosAccess $access)
    {
        [$device, $branch] = $this->authorizeWorkflow($request, $access, 'purchase.payment');
        $data = $request->validate(['amount' => ['required', 'string', 'regex:/^\d{1,8}(?:\.\d{1,2})?$/'],
            'expected_debt' => ['required', 'string', 'regex:/^\d{1,8}(?:\.\d{1,2})?$/']]);

        return $this->write($request, $device, $branch, 'purchase.payment', $id, function () use ($branch, $id, $data) {
            $purchase = app(PurchaseManagement::class)->payment($branch, (int) $id, $data['amount'], $data['expected_debt']);

            return ['purchaseId' => (string) $purchase->id];
        });
    }

    public function destinations(Request $request, PosAccess $access)
    {
        [, $branch] = $this->authorizeWorkflow($request, $access, 'transfer.create');

        return response()->json(['destinations' => Sucursales::where('id_empresa', $branch->id_empresa)->where('id', '!=', $branch->id)
            ->orderBy('nombre')->get()->map(fn ($row) => ['id' => (string) $row->id, 'name' => $row->nombre])->values()]);
    }

    public function transfers(Request $request, PosAccess $access)
    {
        [, $branch] = $this->authorizeWorkflow($request, $access, 'transfer.read');
        $data = $request->validate(['page' => 'sometimes|integer|min:1', 'q' => 'nullable|string|max:200']);
        $rows = $this->transferQuery($branch)->where('folio', 'like', '%'.($data['q'] ?? '').'%')->orderByDesc('id')
            ->paginate(20, ['*'], 'page', $data['page'] ?? 1);

        return response()->json(['items' => $rows->getCollection()->map(fn ($row) => $this->transferRow($row))->values(),
            'page' => $rows->currentPage(), 'hasMore' => $rows->hasMorePages()]);
    }

    private function transferQuery(Sucursales $branch)
    {
        return Transferencia::with(['sucursalOrigen' => fn ($q) => $q->withTrashed(), 'sucursalDestino' => fn ($q) => $q->withTrashed()])
            ->where(fn ($q) => $q->where('id_sucursal_origen', $branch->id)->orWhere('id_sucursal_destino', $branch->id))
            ->whereHas('sucursalOrigen', fn ($q) => $q->withTrashed()->where('id_empresa', $branch->id_empresa))
            ->whereHas('sucursalDestino', fn ($q) => $q->withTrashed()->where('id_empresa', $branch->id_empresa));
    }

    private function transferRow(Transferencia $row): array
    {
        return ['id' => (string) $row->id, 'folio' => $row->folio ?? '#'.$row->id, 'status' => $row->status,
            'originId' => (string) $row->id_sucursal_origen, 'origin' => $row->sucursalOrigen?->nombre ?? 'Sucursal desactivada',
            'destinationId' => (string) $row->id_sucursal_destino, 'destination' => $row->sucursalDestino?->nombre ?? 'Sucursal desactivada',
            'date' => $row->fecha_envio, 'receivedAt' => $row->fecha_recepcion, 'notes' => $row->notas ?? ''];
    }

    public function transferDetail(Request $request, string $id, PosAccess $access)
    {
        [, $branch] = $this->authorizeWorkflow($request, $access, 'transfer.read');
        $transfer = $this->transferQuery($branch)->findOrFail($id);
        $items = $transfer->detalles;
        $products = Producto::withoutGlobalScope('empresa')->withTrashed()->where('id_empresa', $branch->id_empresa)
            ->whereIn('id', $items->pluck('id_producto'))->get()->keyBy('id');

        return response()->json(['transfer' => [...$this->transferRow($transfer),
            'items' => $items->map(fn ($item) => ['productId' => (string) $item->id_producto,
                'name' => ($products->get($item->id_producto)?->nombre ?? 'Producto eliminado'), 'size' => $products->get($item->id_producto)?->tamano ?? '',
                'quantity' => Decimal::format(Decimal::units($item->cantidad, 'quantity'))])->values()]]);
    }

    public function transferCreate(Request $request, PosAccess $access)
    {
        [$device, $branch] = $this->authorizeWorkflow($request, $access, 'transfer.create');
        $data = $request->validate(['destination_id' => 'required|integer', 'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1|max:100', 'items.*.productId' => 'required|integer|distinct',
            'items.*.quantity' => ['required', 'string', 'regex:/^\d{1,6}(?:\.\d{1,2})?$/']]);

        return $this->write($request, $device, $branch, 'transfer.create', null, function () use ($request, $branch, $data) {
            $transfer = app(TransferManagement::class)->create($request->user(), $branch,
                ['id_sucursal_destino' => $data['destination_id'], 'observaciones' => $data['notes'] ?? '',
                    'productos' => array_map(fn ($row) => ['id' => $row['productId'], 'cantidad' => $row['quantity']], $data['items'])]);

            return ['transferId' => (string) $transfer->id];
        });
    }

    public function transferReceive(Request $request, string $id, PosAccess $access)
    {
        [$device, $branch] = $this->authorizeWorkflow($request, $access, 'transfer.receive');
        $request->validate(['confirmed' => 'required|accepted']);

        return $this->write($request, $device, $branch, 'transfer.receive', $id, function () use ($request, $branch, $id) {
            $this->transferQuery($branch)->findOrFail($id);
            $transfer = app(TransferManagement::class)->receive($request->user(), $branch, (int) $id);

            return ['transferId' => (string) $transfer->id];
        });
    }

    private function write(Request $request, OfflineDevice $device, Sucursales $branch, string $action, ?string $resource, callable $execute)
    {
        $request->validate(['operation_id' => 'required|uuid']);
        $id = strtolower($request->input('operation_id'));
        $sort = function ($value) use (&$sort) {
            if (! is_array($value)) {
                return $value;
            }
            if (! array_is_list($value)) {
                ksort($value);
            }

            return array_map($sort, $value);
        };
        $hash = hash('sha256', json_encode($sort([$action, $resource, $request->except(['operation_id', 'device_id', 'branch_id'])]), JSON_THROW_ON_ERROR));
        $result = DB::transaction(function () use ($request, $device, $branch, $action, $id, $hash, $execute) {
            $locked = OfflineDevice::lockForUpdate()->findOrFail($device->id);
            abort_unless($locked->authorized && ! $locked->revoked_at, 403, 'Dispositivo revocado.');
            if ($stored = DB::table('pos_inventory_requests')->where('id', $id)->first()) {
                abort_unless($stored->device_id === $device->id && (int) $stored->user_id === (int) $request->user()->id && (int) $stored->branch_id === (int) $branch->id, 403, 'Solicitud de otro contexto.');
                abort_unless(hash_equals($stored->request_hash, $hash), 409, 'El identificador ya se utilizó con datos diferentes.');

                return json_decode($stored->result, true, flags: JSON_THROW_ON_ERROR);
            }
            $result = ['operationId' => $id, 'status' => 'confirmed', ...$execute($id)];
            DB::table('pos_inventory_requests')->insert(['id' => $id, 'device_id' => $device->id, 'user_id' => $request->user()->id,
                'branch_id' => $branch->id, 'action' => $action, 'request_hash' => $hash,
                'result' => json_encode($result, JSON_THROW_ON_ERROR), 'created_at' => now()]);

            return $result;
        }, 3);

        return response()->json($result);
    }
}
