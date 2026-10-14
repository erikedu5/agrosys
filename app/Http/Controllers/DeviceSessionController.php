<?php

namespace App\Http\Controllers;

use App\Models\Sucursales;
use App\Models\User;
use App\Services\DeviceSessions;
use App\Services\SucursalService;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class DeviceSessionController extends Controller
{
    public function activate(Request $request, DeviceSessions $sessions)
    {
        $data = $request->validate(['device_id' => 'required|uuid', 'close_other_sessions' => 'sometimes|boolean']);
        try {
            $version = DB::transaction(function () use ($request, $data, $sessions) {
                $user = User::lockForUpdate()->findOrFail($request->user()->id);
                abort_unless((int) $request->session()->get('device_session_version', 0) === (int) $user->device_session_version, 401);
                $branch = Sucursales::whereKey(SucursalService::getSucursalActiva())->lockForUpdate()->firstOrFail();
                $device = $sessions->activate($user, $branch, strtolower($data['device_id']), 'web', $data['close_other_sessions'] ?? false);
                abort_unless($device->authorized, 403, 'El dispositivo requiere autorización manual.');
                return (int) $user->device_session_version;
            }, 3);
        } catch (HttpResponseException $e) {
            if (($e->getResponse()->getData(true)['code'] ?? null) === 'DEVICE_SESSION_CONFIRMATION_REQUIRED') {
                $request->session()->put('device_session_confirmation_required', true);
            }
            throw $e;
        }
        $request->session()->put('device_session_version', $version);
        $request->session()->forget('device_session_confirmation_required');
        return response()->json(['authorized' => true]);
    }
}
