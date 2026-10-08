<?php

namespace App\Services\Pos;

use App\Models\OfflineDevice;
use App\Models\Sucursales;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OfflineLease
{
    private function keyPair(): string
    {
        abort_unless(function_exists('sodium_crypto_sign_seed_keypair'), 503, 'La firma offline requiere sodium.');
        $configured = config('pos.signing_seed');
        if ($configured) {
            abort_unless(is_string($configured) && preg_match('/^[a-f0-9]{64}$/i', $configured), 503, 'Configuración de firma inválida.');
            $seed = hex2bin($configured);
        } else {
            $key = config('app.key');
            abort_unless($key, 503, 'No hay clave de firma disponible.');
            $seed = hash_hkdf('sha256', $key, 32, 'agrosys-pos-offline-lease-v1');
        }

        return sodium_crypto_sign_seed_keypair($seed);
    }

    public function issue(User $user, Sucursales $branch, OfflineDevice $device, PosAccess $access): array
    {
        $pair = $this->keyPair();
        $issued = now()->startOfSecond();
        $expires = $issued->copy()->addDays(config('pos.offline_days'));
        $claims = ['leaseId' => (string) Str::uuid(), 'version' => 1, 'userId' => (string) $user->id, 'companyId' => (string) $branch->id_empresa, 'branchId' => (string) $branch->id, 'deviceId' => $device->id, 'permissions' => $access->permissions($user, $branch), 'issuedAt' => $issued->toIso8601String(), 'expiresAt' => $expires->toIso8601String(), 'lateAcceptanceHours' => 24];
        $payload = json_encode($claims, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        DB::table('pos_offline_leases')->insert(['id' => $claims['leaseId'], 'device_id' => $device->id, 'user_id' => $user->id, 'branch_id' => $branch->id, 'claims' => $payload, 'issued_at' => $issued, 'expires_at' => $expires]);
        $device->offline_expires_at = $expires;
        $device->save();

        return ['claims' => $claims, 'payload' => $payload, 'signature' => base64_encode(sodium_crypto_sign_detached($payload, sodium_crypto_sign_secretkey($pair))), 'publicKey' => base64_encode(sodium_crypto_sign_publickey($pair)), 'algorithm' => 'Ed25519'];
    }
}
