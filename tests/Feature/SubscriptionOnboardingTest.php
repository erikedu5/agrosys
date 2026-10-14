<?php

namespace Tests\Feature;

use App\Mail\TrialStartedMail;
use App\Models\Clientes;
use App\Models\Empresa;
use App\Models\Sucursales;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SubscriptionOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_onboarding_creates_empresa_sucursal_and_admin_user_and_starts_trial(): void
    {
        Mail::fake();

        $payload = [
            'plan' => 'campo',
            'cycle' => 'monthly',
            'name' => 'Admin Test',
            'email' => 'admin@example.com',
            'password' => 'Admin1234.',
            'empresa_nombre' => 'Empresa Test',
            'empresa_direccion' => 'Calle 123',
            'empresa_telefono' => '555-111-2222',
            'empresa_email' => 'empresa@example.com',
            'billing_requires_invoice' => false,
            'terms_accepted' => true,
            'privacy_accepted' => true,
        ];

        $response = $this->post(route('subscription.onboarding.store'), $payload);

        $response->assertRedirect(route('subscription.onboarding', ['step' => 2]));
        $this->assertAuthenticated();

        $empresa = Empresa::where('nombre', 'Empresa Test')->firstOrFail();
        $this->assertEquals('campo', $empresa->plan_code);
        $this->assertEquals('monthly', $empresa->plan_cycle);
        $this->assertNotNull($empresa->trial_ends_at);

        $sucursal = Sucursales::where('id_empresa', $empresa->id)->firstOrFail();
        $this->assertTrue((bool) $sucursal->es_matriz);

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->assertEquals('adminEmpresa', $admin->tipo);
        $this->assertEquals($empresa->id, $admin->id_empresa);
        $this->assertEquals($sucursal->id, $admin->id_sucursal);

        $clientePublico = Clientes::where('id_sucursal', $sucursal->id)->firstOrFail();
        $this->assertEquals($clientePublico->id, $sucursal->id_cliente_publico);

        Mail::assertSent(TrialStartedMail::class, function (TrialStartedMail $mail) use ($admin, $empresa) {
            return $mail->hasTo($admin->email) && $mail->empresa->id === $empresa->id;
        });
    }
}

