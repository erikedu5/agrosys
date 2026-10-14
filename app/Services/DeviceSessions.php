<?php

namespace App\Services;

use App\Models\OfflineDevice;
use App\Models\Sucursales;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;

class DeviceSessions
{
    /** Caller holds the user and branch locks until activation and lease issuance finish. */
    public function activate(User $user, Sucursales $branch, string $id, string $kind, bool $confirmed, ?int $keepToken = null): OfflineDevice
    {
        $device = OfflineDevice::lockForUpdate()->find($id);
        if ($device) {
            abort_unless($device->client_kind === $kind, 403, 'Dispositivo de otro contexto.');
            if ((int) $device->user_id !== (int) $user->id || (int) $device->branch_id !== (int) $branch->id) {
                abort_if($kind === 'pos', 403, 'Dispositivo de otro contexto.');
                throw new HttpResponseException(response()->json(['code' => 'DEVICE_CONTEXT_MISMATCH', 'message' => 'El dispositivo está registrado para otro usuario o sucursal.'], 409));
            }
            abort_if($device->revoked_at, 403, 'Dispositivo revocado.');
        }
        $needsActivation = ! $device || ! $device->authorized;
        $limit = $branch->empresa->dispositivosPorSucursalPermitidos();
        $count = OfflineDevice::where('branch_id', $branch->id)->where('authorized', true)->whereNull('revoked_at')->count();
        if ($needsActivation && $limit !== null && $count >= $limit && ! $confirmed) {
            throw new HttpResponseException(response()->json([
                'code' => 'DEVICE_SESSION_CONFIRMATION_REQUIRED',
                'message' => 'Se alcanzó el número de dispositivos de la sucursal. Para continuar, confirma que deseas cerrar todas las demás sesiones de tu usuario.',
                'limit' => $limit,
            ], 409));
        }
        if (! $device) {
            $device = OfflineDevice::create(['id' => $id, 'user_id' => $user->id, 'branch_id' => $branch->id, 'client_kind' => $kind, 'authorized' => ! config('offline.require_activation'), 'offline_expires_at' => now()]);
        } elseif ($device->session_closed_at && ! config('offline.require_activation')) {
            $device->authorized = true;
        }
        // Confirmation replaces sessions; it never grants manual administrator approval.
        if ($needsActivation && $confirmed && $device->authorized) {
            OfflineDevice::where('user_id', $user->id)->where('id', '!=', $id)->whereNull('revoked_at')
                ->update(['authorized' => false, 'session_closed_at' => now(), 'offline_expires_at' => now()]);
            $tokens = $user->tokens()->whereNotNull('pos_device_id');
            if ($keepToken !== null) $tokens->where('id', '!=', $keepToken);
            $tokens->delete();
            $user->increment('device_session_version');
            $user->setRememberToken(\Illuminate\Support\Str::random(60));
            $user->save();
        }
        if ($device->authorized) $device->session_closed_at = null;
        $device->save();
        return $device;
    }
}
