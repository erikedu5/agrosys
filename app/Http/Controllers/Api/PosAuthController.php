<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Pos\PosAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class PosAuthController extends Controller
{
    public function login(Request $request, PosAccess $access)
    {
        $data = $request->validate(['email' => 'required|email|max:255', 'password' => 'required|string|max:1000', 'device_id' => 'required|uuid', 'device_name' => 'required|string|max:100']);
        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'El correo o la contraseña no son correctos.']);
        }
        $access->checkUser($user);
        if ($user->hasEnabledTwoFactorAuthentication()) {
            $challenge = Str::random(64);
            DB::table('pos_login_challenges')->insert(['id' => hash('sha256', $challenge), 'user_id' => $user->id, 'device_id' => strtolower($data['device_id']), 'device_name' => $data['device_name'], 'expires_at' => now()->addMinutes(config('pos.challenge_minutes'))]);

            return response()->json(['requiresTwoFactor' => true, 'challenge' => $challenge, 'expiresIn' => config('pos.challenge_minutes') * 60]);
        }

        return $this->issue($user, strtolower($data['device_id']), $data['device_name'], $access);
    }

    public function verify(Request $request, PosAccess $access, TwoFactorAuthenticationProvider $provider)
    {
        $data = $request->validate(['challenge' => 'required|string|size:64', 'code' => 'required_without:recovery_code|nullable|string|max:20', 'recovery_code' => 'required_without:code|nullable|string|max:100']);
        $result = DB::transaction(function () use ($data, $access, $provider) {
            $id = hash('sha256', $data['challenge']);
            $challenge = DB::table('pos_login_challenges')->where('id', $id)->lockForUpdate()->first();
            if (! $challenge || now()->gte($challenge->expires_at) || $challenge->attempts >= 5) {
                return null;
            }
            DB::table('pos_login_challenges')->where('id', $id)->increment('attempts');
            $user = User::lockForUpdate()->find($challenge->user_id);
            if (! $user || ! $user->hasEnabledTwoFactorAuthentication()) {
                return null;
            }
            $access->checkUser($user);
            $valid = false;
            if (! empty($data['recovery_code']) && $user->two_factor_recovery_codes) {
                foreach ($user->recoveryCodes() as $code) {
                    if (hash_equals($code, $data['recovery_code'])) {
                        $user->replaceRecoveryCode($code);
                        $valid = true;
                        break;
                    }
                }
            } elseif (! empty($data['code'])) {
                $valid = $provider->verify(Crypt::decrypt($user->two_factor_secret), $data['code']);
            }
            if (! $valid) {
                return null;
            }
            DB::table('pos_login_challenges')->where('id', $id)->delete();

            return $this->issue($user, $challenge->device_id, $challenge->device_name, $access);
        });
        if (! $result) {
            throw ValidationException::withMessages(['code' => 'Código inválido o solicitud expirada.']);
        }

        return $result;
    }

    private function issue(User $user, string $deviceId, string $name, PosAccess $access)
    {
        $token = $user->createToken('pos:'.$name, ['pos:access'], now()->addDays(config('pos.token_days')));
        $token->accessToken->forceFill(['pos_device_id' => $deviceId])->save();

        return response()->json(['requiresTwoFactor' => false, 'token' => $token->plainTextToken, 'expiresAt' => $token->accessToken->expires_at->toIso8601String(), 'user' => ['id' => (string) $user->id, 'name' => $user->name], 'branches' => $access->branches($user)->map(fn ($branch) => ['id' => (string) $branch->id, 'name' => $branch->nombre, 'companyId' => (string) $branch->id_empresa])]);
    }

    public function context(Request $request, PosAccess $access)
    {
        $data = $request->validate(['device_id' => 'required|uuid', 'branch_id' => 'required|integer|min:1']);
        $id = strtolower($data['device_id']);
        $branch = $access->branch($request->user(), $data['branch_id']);

        return DB::transaction(function () use ($request, $access, $branch, $id) {
            $token = \Laravel\Sanctum\PersonalAccessToken::lockForUpdate()->find($request->user()->currentAccessToken()->id);
            abort_unless($token, 401, 'La sesión ya fue intercambiada.');
            $device = \App\Models\OfflineDevice::lockForUpdate()->find($id);
            if ($device) {
                abort_unless((int) $device->user_id === (int) $request->user()->id && (int) $device->branch_id === (int) $branch->id && $device->client_kind === 'pos' && ! $device->revoked_at, 403, 'Dispositivo de otro contexto o revocado.');
            }
            $response = $this->issue($request->user(), $id, 'Agrosys POS', $access);
            $token->delete();

            return $response;
        }, 3);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['revoked' => true]);
    }
}
