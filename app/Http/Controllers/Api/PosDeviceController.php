<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OfflineDevice;
use App\Models\Sucursales;
use App\Services\Pos\OfflineLease;
use App\Services\Pos\PosAccess;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosDeviceController extends Controller
{
    public function branches(Request $request, PosAccess $access)
    {
        return response()->json(['branches' => $access->branches($request->user())->map(fn ($branch) => ['id' => (string) $branch->id, 'companyId' => (string) $branch->id_empresa, 'name' => $branch->nombre])]);
    }

    public function activate(Request $request, PosAccess $access, OfflineLease $leases)
    {
        $data = $request->validate(['device_id' => 'required|uuid', 'branch_id' => 'required|integer|min:1']);
        $id = strtolower($data['device_id']);
        abort_unless($request->user()->currentAccessToken()->pos_device_id === $id, 403, 'Token de otro dispositivo.');
        $branch = $access->branch($request->user(), $data['branch_id']);
        try {
            $result = DB::transaction(function () use ($request, $access, $leases, $branch, $id) {
                Sucursales::whereKey($branch->id)->lockForUpdate()->firstOrFail();
                $device = OfflineDevice::lockForUpdate()->find($id);
                if ($device) {
                    abort_unless((int) $device->user_id === (int) $request->user()->id && (int) $device->branch_id === (int) $branch->id && $device->client_kind === 'pos', 403, 'Dispositivo de otro contexto.');
                    abort_if($device->revoked_at, 403, 'Dispositivo revocado.');
                } else {
                    $limit = $branch->empresa->dispositivosPorSucursalPermitidos();
                    abort_if($limit !== null && OfflineDevice::where('branch_id', $branch->id)->where('authorized', true)->count() >= $limit, 403, 'Límite de dispositivos alcanzado.');
                    $device = OfflineDevice::create(['id' => $id, 'user_id' => $request->user()->id, 'branch_id' => $branch->id, 'client_kind' => 'pos', 'authorized' => ! config('offline.require_activation'), 'offline_expires_at' => now()]);
                }
                if (! $device->authorized) {
                    return null;
                }

                return ['authorized' => true, 'offlineLease' => $leases->issue($request->user(), $branch, $device, $access)];
            }, 3);
        } catch (UniqueConstraintViolationException $e) {
            abort(409, 'El dispositivo fue registrado simultáneamente. Reintenta la activación con el mismo identificador.');
        }
        abort_unless($result, 403, 'El dispositivo requiere autorización manual.');

        return response()->json($result);
    }

    public function renew(Request $request, PosAccess $access, OfflineLease $leases)
    {
        $data = $request->validate(['device_id' => 'required|uuid', 'branch_id' => 'required|integer|min:1']);
        $result = DB::transaction(function () use ($request, $data, $access, $leases) {
            $device = $access->device($request, strtolower($data['device_id']), $data['branch_id']);
            $device = OfflineDevice::lockForUpdate()->findOrFail($device->id);
            abort_unless($device->authorized && ! $device->revoked_at, 403, 'Dispositivo revocado.');

            return $leases->issue($request->user(), $access->branch($request->user(), $data['branch_id']), $device, $access);
        });

        return response()->json(['authorized' => true, 'offlineLease' => $result]);
    }

    public function revoke(Request $request, PosAccess $access)
    {
        $data = $request->validate(['device_id' => 'required|uuid', 'branch_id' => 'required|integer|min:1']);
        $access->branch($request->user(), $data['branch_id']);
        $device = OfflineDevice::whereKey(strtolower($data['device_id']))->where('branch_id', $data['branch_id'])->where('client_kind', 'pos')->firstOrFail();
        abort_unless((int) $device->user_id === (int) $request->user()->id || in_array($request->user()->tipo, ['admin', 'adminEmpresa', 'superAdmin'], true), 403);
        DB::transaction(function () use ($device) {
            $device = OfflineDevice::lockForUpdate()->findOrFail($device->id);
            $device->forceFill(['authorized' => false, 'revoked_at' => now()])->save();
        });

        return response()->json(['authorized' => false]);
    }

    public function heartbeat(Request $request, PosAccess $access, OfflineLease $leases)
    {
        $data = $request->validate(['device_id' => 'required|uuid', 'branch_id' => 'required|integer|min:1', 'lease_id' => 'nullable|uuid']);
        $device = $access->device($request, strtolower($data['device_id']), $data['branch_id']);
        $branch = $access->branch($request->user(), $data['branch_id']);
        $device->last_seen_at = now();
        $device->save();
        $stored = DB::table('pos_offline_leases')->where('id', $data['lease_id'] ?? '')
            ->where('device_id', $device->id)->where('user_id', $request->user()->id)
            ->where('branch_id', $branch->id)->first();
        $claims = $stored ? json_decode($stored->claims, true, flags: JSON_THROW_ON_ERROR) : null;
        $result = ['authorized' => true, 'offlineExpiresAt' => $device->offline_expires_at->toIso8601String()];
        if ($claims && ($claims['permissions'] ?? []) !== $access->permissions($request->user(), $branch)) {
            $result['offlineLease'] = $leases->issue($request->user(), $branch, $device, $access);
        }

        return response()->json($result);
    }
}
