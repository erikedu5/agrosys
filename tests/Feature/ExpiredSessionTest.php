<?php

namespace Tests\Feature;

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ExpiredSessionTest extends TestCase
{
    public function test_guest_inertia_dashboard_redirects_to_login_instead_of_500(): void
    {
        config(['app.debug' => false]);

        $this->get('/dashboard', ['X-Inertia' => 'true'])
            ->assertRedirect('/login');
    }

    public function test_guest_json_dashboard_returns_401_instead_of_500(): void
    {
        config(['app.debug' => false]);

        $this->getJson('/dashboard')->assertUnauthorized();
    }

    public function test_guest_inertia_logout_redirects_to_login_instead_of_500(): void
    {
        config(['app.debug' => false]);

        $this->post('/logout', [], ['X-Inertia' => 'true'])
            ->assertRedirect('/login');
    }

    public function test_expired_csrf_keeps_419_for_inertia(): void
    {
        config(['app.debug' => false]);
        Route::post('/test-expired-session', function () {
            throw new TokenMismatchException('CSRF token mismatch.');
        })->middleware('web');

        $this->post('/test-expired-session', [], ['X-Inertia' => 'true'])
            ->assertStatus(419)
            ->assertJsonPath('component', 'Error')
            ->assertJsonPath('props.status', 419);
    }
}
