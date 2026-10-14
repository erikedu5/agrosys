<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class OfflineBootstrapTest extends TestCase
{
    public function test_health_check_is_available_without_authentication(): void
    {
        $this->getJson('/api/v1/offline/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('authenticated', false)
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_bootstrap_route_requires_authentication_and_branch_selection(): void
    {
        $middleware = Route::getRoutes()->getByName('offline.bootstrap')->gatherMiddleware();

        $this->assertContains('auth:sanctum', $middleware);
        $this->assertContains('sucursal.selection', $middleware);
    }

    public function test_sync_routes_are_authenticated(): void
    {
        foreach (['offline.push', 'offline.pull', 'offline.operation', 'offline.heartbeat'] as $name) {
            $this->assertContains('auth:sanctum', Route::getRoutes()->getByName($name)->gatherMiddleware());
        }
    }

    public function test_disabled_bootstrap_is_not_exposed(): void
    {
        config()->set('offline.enabled', false);
        $user = new User();
        $user->id = 1;
        $user->exists = true;

        $this->actingAs($user)->getJson('/api/v1/offline/bootstrap')->assertNotFound();
    }
}
