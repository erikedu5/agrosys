<?php

namespace Tests\Feature\Auditoria;

use App\Models\Empresa;
use App\Models\Sucursales;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolAuditorTest extends TestCase
{
    use RefreshDatabase;
    use AuditoriaHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->escenario();
    }

    private function datos(array $extra = []): array
    {
        return ['name' => 'Auditor Externo', 'email' => 'auditor'.uniqid().'@example.com', 'password' => 'secret-123', 'tipo' => 'auditor'] + $extra;
    }

    public function test_super_admin_crea_auditor_multiempresa(): void
    {
        $otra = Empresa::factory()->create();
        $super = User::factory()->create(['tipo' => 'superAdmin', 'id_sucursal' => $this->sucursal->id]);

        $this->actingAs($super)->post(route('usuario.store'), $this->datos([
            'alcances' => [['id_empresa' => $this->empresa->id, 'id_sucursal' => null], ['id_empresa' => $otra->id, 'id_sucursal' => null]],
            'acceso_hasta' => now()->addMonth()->toDateString(),
        ]))->assertRedirect(route('usuario.index'));

        $auditor = User::where('name', 'Auditor Externo')->firstOrFail();
        $this->assertSame('auditor', $auditor->tipo);
        $this->assertNull($auditor->id_sucursal);
        $this->assertNull($auditor->id_empresa);
        $this->assertSame(2, $auditor->alcancesAuditor()->count());
    }

    public function test_admin_empresa_solo_crea_auditor_de_su_empresa(): void
    {
        $otra = Empresa::factory()->create();
        $this->actingAs($this->admin);

        $this->post(route('usuario.store'), $this->datos(['alcances' => [['id_empresa' => $otra->id, 'id_sucursal' => null]]]))
            ->assertSessionHasErrors('alcances');

        $this->post(route('usuario.store'), $this->datos(['alcances' => [['id_empresa' => $this->empresa->id, 'id_sucursal' => $this->sucursal->id]]]))
            ->assertRedirect(route('usuario.index'));
        $auditor = User::where('tipo', 'auditor')->latest('id')->firstOrFail();
        $this->assertSame($this->sucursal->id, (int) $auditor->alcancesAuditor()->value('id_sucursal'));

        // adminEmpresa ve a su auditor en la lista aunque no tenga sucursal.
        $this->get(route('usuario.index'))->assertInertia(fn ($page) => $page->where('usuarios.data', fn ($rows) => collect($rows)->contains('id', $auditor->id)));
    }

    public function test_admin_no_puede_crear_auditor_ni_escalar_rol(): void
    {
        $admin = User::factory()->create(['tipo' => 'admin', 'id_sucursal' => $this->sucursal->id, 'id_empresa' => $this->empresa->id]);
        $this->actingAs($admin);

        $this->post(route('usuario.store'), $this->datos(['alcances' => [['id_empresa' => $this->empresa->id]]]))->assertSessionHasErrors('tipo');
        $this->post(route('usuario.store'), $this->datos(['tipo' => 'superAdmin', 'id_sucursal' => $this->sucursal->id]))->assertSessionHasErrors('tipo');
        $this->assertSame(0, User::where('name', 'Auditor Externo')->count());
    }

    public function test_login_de_auditor_valida_vigencia_y_alcance(): void
    {
        $vigente = $this->crearAuditor([[$this->empresa->id, null]], ['email' => 'vigente@example.com']);
        $this->post('/login', ['email' => $vigente->email, 'password' => 'password']);
        $this->assertAuthenticatedAs($vigente);
        $this->post('/logout');

        $vencido = $this->crearAuditor([[$this->empresa->id, null]], ['email' => 'vencido@example.com', 'acceso_hasta' => now()->subDay()->toDateString()]);
        $this->post('/login', ['email' => $vencido->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $sinAlcance = $this->crearAuditor([], ['email' => 'sinalcance@example.com']);
        $this->post('/login', ['email' => $sinAlcance->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_auditor_no_accede_a_modulos_operativos(): void
    {
        $this->actingAs($this->auditor);

        $this->get(route('dashboard'))->assertRedirect(route('auditorias.index'));
        $this->get(route('inventario.index'))->assertForbidden();
        $this->get(route('transferencias.index'))->assertForbidden();
        $this->get(route('compra.index'))->assertForbidden();
        $this->get(route('venta.index'))->assertForbidden();
        $this->get(route('usuario.index'))->assertForbidden();
        $this->get(route('auditorias.index'))->assertOk();
    }

    public function test_acceso_vencido_bloquea_sesion_abierta(): void
    {
        $this->producto('Vence', stock: 1);
        $auditoria = $this->auditoriaIniciada();
        $this->auditor->update(['acceso_hasta' => now()->subDay()->toDateString()]);

        $this->actingAs($this->auditor->fresh())->get(route('auditorias.captura', $auditoria->id))->assertForbidden();
    }
}
