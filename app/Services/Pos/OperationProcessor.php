<?php

namespace App\Services\Pos;

use App\Models\OfflineDevice;
use App\Models\OfflineOperation;
use App\Models\User;
use App\Models\Venta;
use App\Services\OfflineSaleProcessor;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OperationProcessor
{
    public function fingerprint(array $input, OfflineDevice $device): string
    {
        return hash('sha256', json_encode($this->canonical(['deviceId' => $device->id, 'branchId' => (string) $device->branch_id, 'operation' => $input]), JSON_THROW_ON_ERROR));
    }

    private function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(fn ($item) => $this->canonical($item), $value);
    }

    public function process(User $user, OfflineDevice $device, array $input): array
    {
        $hash = $this->fingerprint($input, $device);
        try {
            return DB::transaction(function () use ($user, $device, $input, $hash) {
                $lockedDevice = OfflineDevice::lockForUpdate()->findOrFail($device->id);
                abort_unless($lockedDevice->authorized && ! $lockedDevice->revoked_at, 403, 'Dispositivo revocado.');
                $existing = OfflineOperation::lockForUpdate()->find($input['operation_id']);
                if ($existing) {
                    $duplicate = $this->existing($existing, $device, $user, $hash);
                    if ($duplicate) {
                        return $duplicate;
                    }
                }
                $lease = DB::table('pos_offline_leases')->where('id', $input['offline_lease_id'])->where('device_id', $device->id)->where('user_id', $user->id)->where('branch_id', $device->branch_id)->first();
                $time = Carbon::parse($input['occurred_at']);
                if (! $lease || $time->lt(Carbon::parse($lease->issued_at)->subMinutes(5)) || $time->gt($lease->expires_at) || $time->gt(now()->addMinutes(5)) || now()->gt(Carbon::parse($lease->expires_at)->addHours(24))) {
                    return $this->error($input, 'rejected', 'OFFLINE_LEASE_EXPIRED', 'Autorización offline inválida o fuera de la ventana de recepción; requiere revisión central.');
                }
                $claims = json_decode($lease->claims, true, flags: JSON_THROW_ON_ERROR);
                $permission = ($input['payload']['saleType'] ?? '') === 'Credito' ? 'sale.credit' : 'sale.create';
                if (! in_array($permission, $claims['permissions'], true)) {
                    return $this->error($input, 'rejected', 'SALE_NOT_AUTHORIZED', 'La autorización no permite esta venta.');
                }
                if (! $existing && OfflineOperation::where('device_id', $device->id)->where('sequence', $input['sequence'])->exists()) {
                    return $this->error($input, 'rejected', 'SEQUENCE_REUSED', 'La secuencia ya pertenece a otra operación.');
                }
                if (! $existing && (OfflineOperation::where('aggregate_id', $input['aggregate_id'])->exists() || Venta::where('client_sale_id', $input['aggregate_id'])->exists())) {
                    return $this->error($input, 'conflict', 'SALE_ID_REUSED', 'El identificador de venta ya fue utilizado.');
                }
                $operation = $existing ?: OfflineOperation::create([
                    ...$input, 'device_id' => $device->id, 'branch_id' => $device->branch_id, 'user_id' => $user->id,
                    'pos_lease_id' => $input['offline_lease_id'], 'request_hash' => $hash, 'status' => 'processing',
                ]);
                try {
                    $result = app(OfflineSaleProcessor::class)->process($operation);
                    $lockedDevice->last_sequence = max($lockedDevice->last_sequence, $input['sequence']);
                    $lockedDevice->last_seen_at = now();
                    $lockedDevice->save();

                    return $result;
                } catch (ValidationException $e) {
                    $result = $this->error($input, 'conflict', 'BUSINESS_CONFLICT', $e->validator->errors()->first());
                    $operation->update(['status' => 'conflict', 'result' => $result, 'error_code' => $result['errorCode'], 'error_message' => $result['message']]);

                    return $result;
                }
            }, 3);
        } catch (UniqueConstraintViolationException $e) {
            $existing = OfflineOperation::find($input['operation_id']);
            if ($existing) {
                return $this->existing($existing, $device, $user, $hash) ?? $this->error($input, 'processing', 'PROCESSING', 'La operación está siendo procesada.');
            }

            return $this->error($input, 'conflict', 'IDENTITY_REUSED', 'El identificador o secuencia ya fue utilizado.');
        }
    }

    private function existing(OfflineOperation $operation, OfflineDevice $device, User $user, string $hash): ?array
    {
        abort_unless((int) $operation->user_id === (int) $user->id && $operation->device_id === $device->id && (int) $operation->branch_id === (int) $device->branch_id, 403, 'Operación de otro contexto.');
        if (! $operation->request_hash || ! hash_equals($operation->request_hash, $hash)) {
            return ['operationId' => $operation->operation_id, 'status' => 'conflict', 'originalStatus' => $operation->status, 'errorCode' => 'IDEMPOTENCY_PAYLOAD_MISMATCH', 'message' => 'El identificador ya fue enviado con datos diferentes.'];
        }
        if ($operation->result) {
            return [...$operation->result, 'status' => 'duplicate', 'originalStatus' => $operation->result['originalStatus'] ?? $operation->status];
        }

        return null;
    }

    private function error(array $input, string $status, string $code, string $message): array
    {
        return ['operationId' => $input['operation_id'], 'status' => $status, 'originalStatus' => $status, 'errorCode' => $code, 'message' => $message];
    }
}
