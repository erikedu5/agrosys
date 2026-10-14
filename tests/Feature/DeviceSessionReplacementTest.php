<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\OfflineDevice;
use App\Models\Sucursales;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeviceSessionReplacementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Sucursales $branch;

    protected function setUp(): void
    {
        parent::setUp();
        config(['pos.enabled' => true, 'offline.require_activation' => false]);
        $company = Empresa::factory()->create(['plan_code' => null, 'numero_dispositivos_por_sucursal' => 1]);
        $this->branch = Sucursales::factory()->create(['id_empresa' => $company->id]);
        $this->user = User::factory()->create(['tipo' => 'vendedor', 'id_empresa' => $company->id, 'id_sucursal' => $this->branch->id, 'password' => Hash::make('test-password')]);
    }

    private function device(User $user, string $kind = 'pos'): OfflineDevice
    {
        return OfflineDevice::create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'branch_id' => $this->branch->id, 'client_kind' => $kind, 'authorized' => true, 'offline_expires_at' => now()->addDays(7)]);
    }

    private function token(User $user, string $device): string
    {
        $token = $user->createToken('pos:test', ['pos:access'], now()->addDays(30));
        $token->accessToken->forceFill(['pos_device_id' => $device])->save();
        return $token->plainTextToken;
    }

    private function activate(string $id, string $token, bool $confirmed = false)
    {
        Auth::forgetGuards();
        return $this->postJson('/api/v1/pos/devices/activate', ['device_id' => $id, 'branch_id' => $this->branch->id, 'close_other_sessions' => $confirmed], ['Authorization' => 'Bearer '.$token]);
    }

    public function test_confirmation_closes_only_own_sessions_preserves_current_and_is_idempotent(): void
    {
        $old = $this->device($this->user);
        $old->update(['last_sequence' => 9]);
        $web = $this->device($this->user, 'web');
        $oldToken = $this->token($this->user, $old->id);
        $other = User::factory()->create(['tipo' => 'vendedor', 'id_sucursal' => $this->branch->id, 'id_empresa' => $this->branch->id_empresa]);
        $otherDevice = $this->device($other);
        $this->token($other, $otherDevice->id);
        $new = (string) Str::uuid();
        $token = $this->token($this->user, $new);

        $this->activate($new, $token)->assertStatus(409)->assertJsonPath('code', 'DEVICE_SESSION_CONFIRMATION_REQUIRED');
        $this->assertNull(OfflineDevice::find($new));
        $this->assertTrue($old->fresh()->authorized);
        $this->assertSame(0, (int) $this->user->fresh()->device_session_version);

        $this->activate($new, $token, true)->assertOk()->assertJsonPath('authorized', true);
        $this->assertFalse($old->fresh()->authorized);
        $this->assertFalse($web->fresh()->authorized);
        $this->assertNotNull($old->fresh()->session_closed_at);
        $this->assertNull($old->fresh()->revoked_at);
        $this->assertTrue($otherDevice->fresh()->authorized);
        $this->assertSame(1, $this->user->tokens()->count());
        $this->assertSame(1, $other->tokens()->count());
        $this->assertSame(1, (int) $this->user->fresh()->device_session_version);
        // Even with other users occupying the cupo, access is allowed after confirmation.
        $this->assertSame(2, OfflineDevice::where('authorized', true)->count());
        $this->activate($new, $token, true)->assertOk();
        $this->assertSame(1, (int) $this->user->fresh()->device_session_version);
        $this->activate($old->id, $oldToken)->assertUnauthorized();

        // A fresh login can recover the same device ID, sequence and local operations.
        $freshToken = $this->token($this->user, $old->id);
        $this->activate($old->id, $freshToken, true)->assertOk();
        $this->assertTrue($old->fresh()->authorized);
        $this->assertFalse(OfflineDevice::find($new)->authorized);
        $this->assertSame(9, (int) $old->fresh()->last_sequence);
    }

    public function test_browser_replacement_invalidates_old_browser_session_and_pos_token(): void
    {
        $old = $this->device($this->user);
        $token = $this->token($this->user, $old->id);
        $new = (string) Str::uuid();
        $this->actingAs($this->user, 'web')->withSession(['device_session_version' => 0]);
        $this->postJson('/device-session/activate', ['device_id' => $new])->assertStatus(409);
        $this->postJson('/caja/status')->assertStatus(409)->assertJsonPath('code', 'DEVICE_SESSION_CONFIRMATION_REQUIRED');
        $this->postJson('/device-session/activate', ['device_id' => $new, 'close_other_sessions' => true])->assertOk()->assertSessionHas('device_session_version', 1);
        $this->getJson('/api/v1/offline/health')->assertOk()->assertJsonPath('authenticated', true);
        $this->activate($old->id, $token)->assertUnauthorized();

        $this->actingAs($this->user->fresh(), 'web')->withSession(['device_session_version' => 0]);
        $this->getJson('/api/v1/offline/health')->assertUnauthorized();
        $this->assertGuest('web');
        $this->actingAs($this->user->fresh(), 'web')->withSession(['device_session_version' => 0]);
        $this->get('/dashboard')->assertRedirect('/login');
        Auth::forgetGuards();
        $this->postJson('/login', ['email' => $this->user->email, 'password' => 'test-password'])
            ->assertOk()->assertSessionHas('device_session_version', 1);
        $this->getJson('/api/v1/offline/health')->assertOk()->assertJsonPath('authenticated', true);
    }

    public function test_confirmation_never_undoes_admin_revocation_or_manual_authorization(): void
    {
        $old = $this->device($this->user);
        $revoked = $this->device($this->user);
        $revoked->update(['authorized' => false, 'revoked_at' => now()]);
        $token = $this->token($this->user, $revoked->id);
        $this->activate($revoked->id, $token, true)->assertForbidden();
        $this->assertTrue($old->fresh()->authorized);

        config(['offline.require_activation' => true]);
        $id = (string) Str::uuid();
        $this->activate($id, $this->token($this->user, $id), true)->assertForbidden();
        $this->assertFalse(OfflineDevice::findOrFail($id)->authorized);
        $this->assertTrue($old->fresh()->authorized);
        $this->assertSame(0, (int) $this->user->fresh()->device_session_version);
    }

    public function test_browser_cannot_replace_device_of_another_user(): void
    {
        $other = User::factory()->create(['id_sucursal' => $this->branch->id]);
        $device = $this->device($other, 'web');
        $this->actingAs($this->user)->postJson('/device-session/activate', ['device_id' => $device->id, 'close_other_sessions' => true])
            ->assertStatus(409)->assertJsonPath('code', 'DEVICE_CONTEXT_MISMATCH');
        $this->assertTrue($device->fresh()->authorized);
        $this->assertSame(0, (int) $this->user->fresh()->device_session_version);
    }
}
