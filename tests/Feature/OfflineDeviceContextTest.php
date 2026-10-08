<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\OfflineDevice;
use App\Models\Sucursales;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OfflineDeviceContextTest extends TestCase
{
    use RefreshDatabase;

    private Sucursales $centro;
    private Sucursales $norte;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('offline.enabled', true);
        config()->set('offline.catalog_enabled', true);
        config()->set('offline.require_activation', false);

        $empresa = Empresa::factory()->create();
        $this->centro = Sucursales::factory()->create(['id_empresa' => $empresa->id]);
        $this->norte = Sucursales::factory()->create(['id_empresa' => $empresa->id]);
        $this->user = User::factory()->create(['tipo' => 'vendedor', 'id_sucursal' => $this->centro->id, 'id_empresa' => $empresa->id]);
    }

    private function device(array $attributes): string
    {
        $id = (string) Str::uuid();
        OfflineDevice::create(['id' => $id, 'authorized' => true, 'last_sequence' => 0, 'offline_expires_at' => now()->addDays(7), ...$attributes]);

        return $id;
    }

    private function bootstrap(string $deviceId)
    {
        return $this->actingAs($this->user)->getJson('/api/v1/offline/bootstrap', ['X-Device-ID' => $deviceId]);
    }

    public function test_new_device_is_registered_for_the_current_user_and_branch(): void
    {
        $deviceId = (string) Str::uuid();

        $this->bootstrap($deviceId)->assertOk()->assertJsonPath('offlineSession.deviceId', $deviceId);

        $device = OfflineDevice::find($deviceId);
        $this->assertSame($this->user->id, (int) $device->user_id);
        $this->assertSame($this->centro->id, (int) $device->branch_id);
    }

    public function test_device_registered_for_another_branch_asks_the_client_to_rotate(): void
    {
        $deviceId = $this->device(['user_id' => $this->user->id, 'branch_id' => $this->norte->id]);

        $this->bootstrap($deviceId)
            ->assertStatus(409)
            ->assertJsonPath('code', 'DEVICE_CONTEXT_MISMATCH');

        // El registro original no se reasigna.
        $this->assertSame($this->norte->id, (int) OfflineDevice::find($deviceId)->branch_id);
    }

    public function test_device_registered_for_another_user_asks_the_client_to_rotate(): void
    {
        $otro = User::factory()->create(['id_sucursal' => $this->centro->id]);
        $deviceId = $this->device(['user_id' => $otro->id, 'branch_id' => $this->centro->id]);

        $this->bootstrap($deviceId)->assertStatus(409)->assertJsonPath('code', 'DEVICE_CONTEXT_MISMATCH');
    }

    public function test_revoked_device_stays_forbidden(): void
    {
        $deviceId = $this->device(['user_id' => $this->user->id, 'branch_id' => $this->centro->id, 'revoked_at' => now()]);

        $this->bootstrap($deviceId)->assertForbidden();
    }
}
