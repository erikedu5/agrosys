<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AltaInventario;
use App\Models\CatClasificacion;
use App\Models\CatMarca;
use App\Models\OfflineDevice;
use App\Models\Producto;
use App\Services\InventoryManagement;
use App\Services\Pos\Decimal;
use App\Services\Pos\PosAccess;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosInventoryController extends Controller
{
    private function authorizeInventory(Request $request, PosAccess $access, string $permission): array
    {
        $data = $request->validate(['device_id' => 'required|uuid', 'branch_id' => 'required|integer|min:1']);
        $device = $access->device($request, strtolower($data['device_id']), $data['branch_id']);
        $branch = $access->branch($request->user(), $data['branch_id']);
        abort_unless(in_array($permission, $access->permissions($request->user(), $branch), true), 403, 'Operación de inventario no autorizada.');
        abort_if($branch->empresa->ventas_bloqueadas && $request->user()->tipo !== 'superAdmin', 403, 'Inventario bloqueado.');

        return [$device, $branch];
    }

    public function options(Request $request, PosAccess $access)
    {
        [, $branch] = $this->authorizeInventory($request, $access, 'inventory.read');

        return response()->json(['categories' => CatClasificacion::orderBy('nombre')->get(['id', 'nombre'])->map(fn ($row) => ['id' => (string) $row->id, 'name' => $row->nombre]),
            'brands' => CatMarca::orderBy('nombre')->get(['id', 'nombre'])->map(fn ($row) => ['id' => (string) $row->id, 'name' => $row->nombre]),
            'manageCosts' => app(InventoryManagement::class)->canManageCosts($request->user(), $branch)]);
    }

    public function detail(Request $request, string $productId, PosAccess $access)
    {
        [, $branch] = $this->authorizeInventory($request, $access, 'inventory.read');
        $product = Producto::withoutGlobalScope('empresa')->where('id_empresa', $branch->id_empresa)->findOrFail($productId);
        $management = app(InventoryManagement::class);
        $result = $product->only(['nombre', 'tamano', 'id_clasificacion', 'id_marca', 'ingrediente_activo', 'barcode']);
        $result['id'] = (string) $product->id;
        $result['id_clasificacion'] = (string) $product->id_clasificacion;
        $result['id_marca'] = (string) $product->id_marca;
        $result['precio_ieps'] = Decimal::format(Decimal::units($product->precio_ieps, 'price'));
        $result['version'] = $management->version($product);
        if ($management->canManageCosts($request->user(), $branch)) {
            $result['precio_unitario'] = Decimal::format(Decimal::units($product->precio_unitario, 'price'));
            $result['ieps'] = (string) $product->ieps;
        }

        return response()->json(['product' => $result]);
    }

    public function create(Request $request, PosAccess $access)
    {
        return $this->mutate($request, $access, 'create');
    }

    public function update(Request $request, string $productId, PosAccess $access)
    {
        return $this->mutate($request, $access, 'update', (int) $productId);
    }

    public function prices(Request $request, string $productId, PosAccess $access)
    {
        return $this->mutate($request, $access, 'prices', (int) $productId);
    }

    public function add(Request $request, string $productId, PosAccess $access)
    {
        return $this->mutate($request, $access, 'add', (int) $productId);
    }

    public function preview(Request $request, string $productId, PosAccess $access)
    {
        $data = $request->validate(['action' => 'required|in:reset,delete']);
        [, $branch] = $this->authorizeInventory($request, $access, $data['action'] === 'delete' ? 'product.delete' : 'inventory.reset');

        return response()->json(app(InventoryManagement::class)->destructivePreview($request->user(), $branch, (int) $productId, $data['action']));
    }

    public function reset(Request $request, string $productId, PosAccess $access)
    {
        return $this->mutate($request, $access, 'reset', (int) $productId);
    }

    public function delete(Request $request, string $productId, PosAccess $access)
    {
        return $this->mutate($request, $access, 'delete', (int) $productId);
    }

    private function mutate(Request $request, PosAccess $access, string $action, ?int $productId = null)
    {
        $permission = ['create' => 'product.create', 'update' => 'product.update', 'prices' => 'product.prices.update', 'add' => 'inventory.add', 'reset' => 'inventory.reset', 'delete' => 'product.delete'][$action];
        [$device, $branch] = $this->authorizeInventory($request, $access, $permission);
        $rules = ['operation_id' => 'required|uuid'];
        if (in_array($action, ['update', 'prices', 'reset', 'delete'], true)) {
            $rules['version'] = 'required|string|size:64';
        }
        if (in_array($action, ['reset', 'delete'], true)) {
            $rules['stock_revision'] = 'required|string|size:64';
            $rules['confirmed'] = 'required|accepted';
        }
        if ($action === 'add') {
            $rules['cantidad'] = ['required', 'string', 'regex:/^\d{1,6}(?:\.\d{1,2})?$/'];
        } elseif (! in_array($action, ['reset', 'delete'], true)) {
            $rules['precio_ieps'] = ['required', 'string', 'regex:/^\d{1,8}(?:\.\d{1,2})?$/'];
        }
        $request->validate($rules);
        $id = strtolower($request->input('operation_id'));
        $payload = $request->except(['operation_id', 'device_id', 'branch_id']);
        $canonical = function ($value) use (&$canonical) {
            if (! is_array($value)) {
                return $value;
            }
            if (! array_is_list($value)) {
                ksort($value);
            }

            return array_map($canonical, $value);
        };
        $hash = hash('sha256', json_encode($canonical([$action, $productId, $payload]), JSON_THROW_ON_ERROR));
        $existingResult = function ($stored) use ($device, $request, $branch, $hash) {
            abort_unless($stored->device_id === $device->id && (int) $stored->user_id === (int) $request->user()->id && (int) $stored->branch_id === (int) $branch->id, 403, 'Solicitud de otro contexto.');
            abort_unless(hash_equals($stored->request_hash, $hash), 409, 'El identificador ya fue enviado con datos diferentes.');

            return json_decode($stored->result, true, flags: JSON_THROW_ON_ERROR);
        };
        try {
            $result = DB::transaction(function () use ($request, $device, $branch, $action, $productId, $payload, $id, $hash, $existingResult) {
                $locked = OfflineDevice::lockForUpdate()->findOrFail($device->id);
                abort_unless($locked->authorized && ! $locked->revoked_at, 403, 'Dispositivo revocado.');
                $stored = DB::table('pos_inventory_requests')->where('id', $id)->first();
                if ($stored) {
                    return $existingResult($stored);
                }
                $management = app(InventoryManagement::class);
                $result = ['operationId' => $id, 'status' => 'confirmed'];
                if (in_array($action, ['reset', 'delete'], true)) {
                    $movement = $management->destructive($request->user(), $branch, $productId, $action, $payload['version'], $payload['stock_revision']);
                    $result['productId'] = (string) $productId;
                    if ($movement) {
                        $result += ['movementId' => (string) $movement->id, 'quantity' => '0.00'];
                    }
                } elseif ($action === 'add') {
                    $movement = $management->addStock($request->user(), $branch, $productId, $payload['cantidad']);
                    $result += ['productId' => (string) $productId, 'movementId' => (string) $movement->id,
                        'quantity' => Decimal::format(Decimal::units($movement->cantidad_nueva, 'quantity', true))];
                } else {
                    $product = $action === 'prices'
                        ? $management->updatePrices($request->user(), $branch, $productId, $payload, $payload['version'])
                        : $management->product($request->user(), $branch, $payload, $productId, $payload['version'] ?? null);
                    $result['productId'] = (string) $product->id;
                }
                DB::table('pos_inventory_requests')->insert(['id' => $id, 'device_id' => $device->id, 'user_id' => $request->user()->id,
                    'branch_id' => $branch->id, 'action' => $action, 'request_hash' => $hash, 'result' => json_encode($result, JSON_THROW_ON_ERROR), 'created_at' => now()]);

                return $result;
            }, 3);
        } catch (UniqueConstraintViolationException $e) {
            $stored = DB::table('pos_inventory_requests')->where('id', $id)->first();
            if (! $stored) {
                throw ValidationException::withMessages(['nombre' => 'Ya existe un producto con ese nombre y tamaño.']);
            }
            $result = $existingResult($stored);
        }

        return response()->json($result);
    }

    public function movements(Request $request, string $productId, PosAccess $access)
    {
        $data = $request->validate(['device_id' => 'required|uuid', 'branch_id' => 'required|integer|min:1', 'page' => 'sometimes|integer|min:1']);
        $access->device($request, strtolower($data['device_id']), $data['branch_id']);
        $branch = $access->branch($request->user(), $data['branch_id']);
        abort_unless(in_array('inventory.movements.read', $access->permissions($request->user(), $branch), true), 403, 'Inventario no autorizado.');
        abort_if($branch->empresa->ventas_bloqueadas && $request->user()->tipo !== 'superAdmin', 403, 'Inventario bloqueado.');
        $product = Producto::withoutGlobalScope('empresa')->where('id_empresa', $branch->id_empresa)->findOrFail($productId);
        $rows = AltaInventario::with('usuario:id,name')->where('id_producto', $product->id)
            ->where('id_sucursal', $branch->id)->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(50, ['*'], 'page', $data['page'] ?? 1);
        $labels = [AltaInventario::EVENTO_ALTA => 'Alta de inventario', AltaInventario::EVENTO_RESETEO => 'Reseteo a cero',
            AltaInventario::EVENTO_VENTA => 'Venta', AltaInventario::EVENTO_TRANSFERENCIA_ENTRADA => 'Entrada por transferencia',
            AltaInventario::EVENTO_TRANSFERENCIA_SALIDA => 'Salida por transferencia'];

        return response()->json(['movements' => $rows->getCollection()->map(function ($row) use ($labels) {
            $before = Decimal::units($row->cantidad_actual, 'quantity', true);
            $after = Decimal::units($row->cantidad_nueva, 'quantity', true);

            return ['id' => (string) $row->id, 'type' => $labels[$row->tipo_evento] ?? ($after > $before ? 'Entrada' : ($after < $before ? 'Salida' : 'Ajuste')),
                'before' => Decimal::format($before), 'change' => Decimal::format($after - $before), 'after' => Decimal::format($after),
                'user' => $row->usuario?->name ?? 'Desconocido', 'date' => $row->created_at];
        })->values(), 'hasMore' => $rows->hasMorePages(), 'page' => $rows->currentPage()]);
    }
}
