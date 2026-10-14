<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OfflineOperation;
use App\Services\Pos\OperationProcessor;
use App\Services\Pos\PosAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PosSyncController extends Controller
{
    public function push(Request $request, PosAccess $access, OperationProcessor $processor)
    {
        $data = $request->validate(['schemaVersion' => 'required|in:2', 'device_id' => 'required|uuid', 'branch_id' => 'required|integer|min:1', 'operations' => 'required|array|min:1|max:50']);
        $device = $access->device($request, strtolower($data['device_id']), $data['branch_id']);
        $permissions = $access->permissions($request->user(), $access->branch($request->user(), $data['branch_id']));
        abort_unless(in_array('sale.create', $permissions, true), 403, 'Ventas no autorizadas.');
        $results = [];
        foreach ($data['operations'] as $input) {
            $validator = Validator::make(is_array($input) ? $input : [], [
                'operation_id' => 'required|uuid', 'aggregate_id' => 'required|uuid', 'aggregate_type' => 'required|in:sale',
                'event_type' => 'required|in:SALE_COMPLETED', 'sequence' => 'required|integer|min:1|max:9007199254740991',
                'occurred_at' => 'required|date', 'offline_lease_id' => 'required|uuid', 'payload' => 'required|array',
            ]);
            if ($validator->fails()) {
                $results[] = ['operationId' => is_array($input) ? ($input['operation_id'] ?? null) : null, 'status' => 'rejected', 'originalStatus' => 'rejected', 'errorCode' => 'INVALID_OPERATION', 'message' => $validator->errors()->first()];

                continue;
            }
            $input = $validator->validated();
            foreach (['operation_id', 'aggregate_id', 'offline_lease_id'] as $field) {
                $input[$field] = strtolower($input[$field]);
            }
            if (count($data['operations']) === 1 && $request->header('Idempotency-Key') && strtolower($request->header('Idempotency-Key')) !== $input['operation_id']) {
                $results[] = ['operationId' => $input['operation_id'], 'status' => 'rejected', 'originalStatus' => 'rejected', 'errorCode' => 'IDEMPOTENCY_KEY_MISMATCH'];

                continue;
            }
            try {
                $results[] = $processor->process($request->user(), $device, $input);
            } catch (\Throwable $e) {
                if ($e instanceof HttpExceptionInterface) {
                    throw $e;
                }
                report($e);
                $results[] = ['operationId' => $input['operation_id'], 'status' => 'retry', 'originalStatus' => 'retry', 'errorCode' => 'RETRYABLE_ERROR', 'message' => 'No se recibió confirmación; conserva la operación para reintento.'];
            }
        }

        return response()->json(['results' => $results, 'serverTime' => now()->toIso8601String()]);
    }

    public function operation(Request $request, string $operationId)
    {
        $operation = OfflineOperation::whereKey(strtolower($operationId))->where('user_id', $request->user()->id)->where('device_id', $request->user()->currentAccessToken()->pos_device_id)->whereNotNull('request_hash')->firstOrFail();
        app(PosAccess::class)->device($request, $operation->device_id, $operation->branch_id);

        return response()->json(['operationId' => $operation->operation_id, 'status' => $operation->status, 'originalStatus' => $operation->status, 'result' => $operation->result, 'errorCode' => $operation->error_code, 'message' => $operation->error_message]);
    }
}
