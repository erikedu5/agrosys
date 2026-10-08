<?php

namespace App\Http\Controllers;

use App\Models\AltaInventario;
use App\Models\Clientes;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\OfflineDevice;
use App\Models\OfflineOperation;
use App\Services\OfflineSaleProcessor;
use App\Services\SucursalService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

class OfflineController extends Controller
{
    public function health(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'authenticated' => $request->user() !== null,
            'serverTime' => now()->toIso8601String(),
        ])->header('Cache-Control', 'no-store');
    }

    public function bootstrap(Request $request): JsonResponse
    {
        abort_unless(config('offline.enabled') && config('offline.catalog_enabled'), 404);

        $branchId = SucursalService::getSucursalActiva();
        $branch = Sucursales::findOrFail($branchId);
        $deviceId = $request->header('X-Device-ID');
        abort_unless(Str::isUuid($deviceId), 422, 'Se requiere un identificador de dispositivo válido.');
        $offlineExpiresAt = now()->addDays(config('offline.valid_days'));
        $device = OfflineDevice::firstOrNew(['id' => $deviceId]);
        if ($device->exists) {
            abort_unless($device->client_kind === 'web' && !$device->revoked_at, 403, 'El dispositivo fue revocado.');
            // El navegador usa un identificador por usuario y sucursal; si llega uno registrado
            // para otro contexto, el cliente genera uno nuevo y reintenta.
            if ((int) $device->user_id !== (int) $request->user()->id || (int) $device->branch_id !== (int) $branchId) {
                throw new HttpResponseException(response()->json([
                    'code' => 'DEVICE_CONTEXT_MISMATCH',
                    'message' => 'El dispositivo está registrado para otro usuario o sucursal.',
                ], 409));
            }
        }
        if (!$device->exists) $device->authorized = !config('offline.require_activation');
        $device->fill(['user_id' => $request->user()->id, 'branch_id' => $branchId, 'last_seen_at' => now(), 'offline_expires_at' => $offlineExpiresAt]);
        $device->save();
        abort_unless($device->authorized, 403, 'El dispositivo no está autorizado.');
        $canSell = in_array($request->user()->tipo, ['vendedor', 'admin', 'superAdmin', 'adminEmpresa'], true);

        $products = Producto::query()
            ->select(['id', 'nombre', 'barcode', 'tamano', 'precio_unitario', 'precio_ieps', 'updated_at'])
            ->orderBy('id')
            ->get()
            ->map(function (Producto $product) use ($branchId) {
                $stock = AltaInventario::query()
                    ->where('id_producto', $product->id)
                    ->where('id_sucursal', $branchId)
                    ->latest('created_at')
                    ->latest('id')
                    ->first(['cantidad_nueva', 'updated_at']);
                $changedAt = collect([$product->updated_at, $stock?->updated_at])->filter()->max();

                return [
                    'id' => (string) $product->id,
                    'serverId' => (string) $product->id,
                    'sku' => (string) $product->id,
                    'barcode' => $product->barcode ?? '',
                    'name' => $product->nombre,
                    'size' => $product->tamano,
                    'unitPrice' => (float) $product->precio_unitario,
                    'price' => (float) $product->precio_ieps,
                    'active' => true,
                    'updatedAtServer' => optional($changedAt)->toIso8601String(),
                    'branchId' => (string) $branchId,
                    'serverQuantity' => (float) ($stock?->cantidad_nueva ?? 0),
                ];
            })->values();

        $customers = Clientes::query()
            ->where('id_sucursal', $branchId)
            ->where('activo', true)
            ->orderBy('id')
            ->get(['id', 'nombre', 'porcentaje_descuento', 'updated_at'])
            ->map(fn (Clientes $customer) => [
                'id' => (string) $customer->id,
                'serverId' => (string) $customer->id,
                'branchId' => (string) $branchId,
                'name' => $customer->nombre,
                'discountPercentage' => (float) $customer->porcentaje_descuento,
                'active' => true,
                'updatedAtServer' => optional($customer->updated_at)->toIso8601String(),
            ])->values();

        return response()->json([
            'schemaVersion' => 1,
            'syncedAt' => now()->toIso8601String(),
            'user' => [
                'id' => (string) $request->user()->id,
                'permissions' => ['catalog.read', 'product.search', 'stock.read_estimated'],
            ],
            'branch' => [
                'id' => (string) $branch->id,
                'name' => $branch->nombre,
            ],
            'offlineSession' => [
                'id' => 'current',
                'deviceId' => $deviceId,
                'userId' => (string) $request->user()->id,
                'branchId' => (string) $branchId,
                'permissions' => array_values(array_filter(['catalog.read', 'product.search', 'stock.read_estimated', config('offline.sales_enabled') && $canSell ? 'sale.create' : null, config('offline.sales_enabled') && $canSell ? 'sale.print_local_ticket' : null, 'sync.view', 'sync.retry'])),
                'offlineExpiresAt' => $offlineExpiresAt->toIso8601String(),
                'credential' => Crypt::encryptString(json_encode(['user_id' => $request->user()->id, 'device_id' => $deviceId, 'branch_id' => $branchId, 'offline_expires_at' => $offlineExpiresAt->timestamp])),
            ],
            'products' => $products,
            'customers' => $customers,
        ])->header('Cache-Control', 'no-store');
    }

    public function push(Request $request, OfflineSaleProcessor $saleProcessor): JsonResponse
    {
        abort_unless(config('offline.enabled') && config('offline.sync_enabled'), 404);
        abort_unless(in_array($request->user()->tipo, ['vendedor', 'admin', 'superAdmin', 'adminEmpresa'], true), 403, 'El usuario no tiene permiso para registrar ventas.');
        $data = $request->validate(['device_id' => ['required', 'uuid'], 'branch_id' => ['required'], 'operations' => ['required', 'array', 'max:50']]);
        $device = OfflineDevice::query()->whereKey($data['device_id'])->where('user_id', $request->user()->id)->where('branch_id', $data['branch_id'])->first();
        abort_unless($device?->authorized && $device->client_kind === 'web' && !$device->revoked_at, 403, 'Dispositivo revocado o no autorizado.');

        $results = [];
        foreach ($data['operations'] as $input) {
            $validator = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'aggregate_type' => ['required', 'in:sale'], 'aggregate_id' => ['required', 'uuid'], 'event_type' => ['required', 'in:SALE_COMPLETED'], 'sequence' => ['required', 'integer', 'min:1'], 'occurred_at' => ['required', 'date'], 'payload' => ['required', 'array']]);
            if ($validator->fails()) {
                $results[] = ['operationId' => $input['operation_id'] ?? null, 'status' => 'rejected', 'errorCode' => 'INVALID_OPERATION', 'message' => $validator->errors()->first()];
                continue;
            }

            $valid = $validator->validated();
            if (count($data['operations']) === 1 && $request->header('Idempotency-Key') && $request->header('Idempotency-Key') !== $valid['operation_id']) {
                $results[] = ['operationId' => $valid['operation_id'], 'status' => 'rejected', 'errorCode' => 'IDEMPOTENCY_KEY_MISMATCH', 'message' => 'Idempotency-Key no coincide con operation_id.'];
                continue;
            }
            $sequenceOwner = OfflineOperation::query()->where('device_id', $device->id)->where('sequence', $valid['sequence'])->where('operation_id', '!=', $valid['operation_id'])->exists();
            if ($sequenceOwner) {
                $results[] = ['operationId' => $valid['operation_id'], 'status' => 'rejected', 'errorCode' => 'SEQUENCE_REUSED', 'message' => 'La secuencia del dispositivo ya fue utilizada.'];
                continue;
            }
            $operation = OfflineOperation::firstOrCreate(['operation_id' => $valid['operation_id']], ['device_id' => $device->id, 'branch_id' => $device->branch_id, 'user_id' => $request->user()->id, 'aggregate_type' => $valid['aggregate_type'], 'aggregate_id' => $valid['aggregate_id'], 'event_type' => $valid['event_type'], 'sequence' => $valid['sequence'], 'status' => 'processing', 'payload' => $valid['payload'], 'occurred_at' => $valid['occurred_at']]);

            abort_unless($operation->user_id === $request->user()->id && $operation->device_id === $device->id, 403, 'La operación pertenece a otro dispositivo.');

            if (!$operation->wasRecentlyCreated && $operation->result) {
                $results[] = [...$operation->result, 'status' => 'duplicate'];
                continue;
            }

            try {
                $results[] = $saleProcessor->process($operation);
                $device->last_sequence = max($device->last_sequence, $valid['sequence']);
                $device->last_seen_at = now();
                $device->save();
            } catch (ValidationException $exception) {
                $message = $exception->validator->errors()->first();
                $operation->update(['status' => 'conflict', 'error_code' => 'BUSINESS_CONFLICT', 'error_message' => $message]);
                $results[] = ['operationId' => $operation->operation_id, 'status' => 'conflict', 'errorCode' => 'BUSINESS_CONFLICT', 'message' => $message];
            } catch (\Throwable $exception) {
                report($exception);
                $operation->update(['status' => 'failed', 'error_code' => 'RETRYABLE_ERROR', 'error_message' => 'No fue posible procesar la operación.']);
                $results[] = ['operationId' => $operation->operation_id, 'status' => 'retry', 'errorCode' => 'RETRYABLE_ERROR', 'message' => 'La operación se conservará para reintento.'];
            }
        }

        return response()->json(['results' => $results, 'serverTime' => now()->toIso8601String()]);
    }

    public function operation(Request $request, string $operationId): JsonResponse
    {
        $operation = OfflineOperation::query()->where('operation_id', $operationId)->where('user_id', $request->user()->id)->firstOrFail();
        abort_unless(!$operation->request_hash, 403, 'Utilice la API nativa para esta operación.');
        return response()->json(['operationId' => $operation->operation_id, 'status' => $operation->status, 'result' => $operation->result, 'errorCode' => $operation->error_code, 'message' => $operation->error_message]);
    }

    public function pull(Request $request): JsonResponse
    {
        $branchId = SucursalService::getSucursalActiva();
        $since = $request->query('cursor') ? rescue(fn () => decrypt($request->query('cursor')), now()->subDays(30)->toIso8601String(), false) : now()->subDays(30)->toIso8601String();
        $request->headers->set('X-Device-ID', $request->header('X-Device-ID'));
        $bootstrap = $this->bootstrap($request)->getData(true);
        $bootstrap['products'] = collect($bootstrap['products'])->filter(fn ($product) => !$product['updatedAtServer'] || $product['updatedAtServer'] > $since)->values();
        $deletedProducts = Producto::withTrashed()->onlyTrashed()->where('updated_at', '>', $since)->get()->map(fn (Producto $product) => [
            'id' => (string) $product->id,
            'serverId' => (string) $product->id,
            'sku' => (string) $product->id,
            'barcode' => $product->barcode ?? '',
            'name' => $product->nombre,
            'size' => $product->tamano,
            'unitPrice' => (float) $product->precio_unitario,
            'price' => (float) $product->precio_ieps,
            'active' => false,
            'updatedAtServer' => optional($product->updated_at)->toIso8601String(),
            'branchId' => (string) $branchId,
            'serverQuantity' => 0,
        ]);
        $bootstrap['products'] = collect($bootstrap['products'])->concat($deletedProducts)->values();
        $bootstrap['customers'] = Clientes::query()->where('id_sucursal', $branchId)->where('updated_at', '>', $since)->get()->map(fn (Clientes $customer) => [
            'id' => (string) $customer->id,
            'serverId' => (string) $customer->id,
            'branchId' => (string) $branchId,
            'name' => $customer->nombre,
            'discountPercentage' => (float) $customer->porcentaje_descuento,
            'active' => (bool) $customer->activo,
            'updatedAtServer' => optional($customer->updated_at)->toIso8601String(),
        ])->values();
        $bootstrap['cursor'] = encrypt(now()->toIso8601String());
        return response()->json($bootstrap)->header('Cache-Control', 'no-store');
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $data = $request->validate(['device_id' => ['required', 'uuid']]);
        $device = OfflineDevice::query()->whereKey($data['device_id'])->where('user_id', $request->user()->id)->firstOrFail();
        abort_unless($device->client_kind === 'web' && !$device->revoked_at, 403);
        $device->update(['last_seen_at' => now()]);
        return response()->json(['authorized' => $device->authorized, 'offlineExpiresAt' => $device->offline_expires_at->toIso8601String()]);
    }
}
