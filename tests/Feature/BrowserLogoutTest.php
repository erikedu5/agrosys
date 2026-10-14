<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Empresa;
use App\Models\Sucursales;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrowserLogoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Exercise actual CSRF verification instead of Laravel's test bypass.
        $this->app->bind(VerifyCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends VerifyCsrfToken
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
    }

    public function test_stale_csrf_cannot_log_out_but_a_refreshed_cookie_can(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web')->withSession(['_token' => 'current-session-token']);
        $this->postJson('/logout', [], ['X-CSRF-TOKEN' => 'stale-token'])->assertStatus(419);
        $this->assertAuthenticatedAs($user, 'web');

        $refresh = $this->getJson('/sanctum/csrf-cookie')->assertNoContent()->assertCookie('XSRF-TOKEN');
        $cookie = $refresh->getCookie('XSRF-TOKEN', false)->getValue();
        $this->postJson('/logout', [], ['X-XSRF-TOKEN' => $cookie])->assertNoContent();
        $this->assertGuest('web');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_expired_subscription_still_allows_refresh_and_logout(): void
    {
        $company = Empresa::factory()->create(['plan_code' => 'campo', 'trial_ends_at' => now()->subDay()]);
        $branch = Sucursales::factory()->create(['id_empresa' => $company->id]);
        $user = User::factory()->create(['id_empresa' => $company->id, 'id_sucursal' => $branch->id]);
        $this->actingAs($user, 'web');
        $this->getJson('/dashboard')->assertStatus(402);
        $refresh = $this->getJson('/sanctum/csrf-cookie')->assertNoContent();
        $this->postJson('/logout', [], ['X-XSRF-TOKEN' => $refresh->getCookie('XSRF-TOKEN', false)->getValue()])->assertNoContent();
        $this->assertGuest('web');
    }
}
