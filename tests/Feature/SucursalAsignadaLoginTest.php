<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Sucursales;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SucursalAsignadaLoginTest extends TestCase
{
    use RefreshDatabase;

    private function login(User $user)
    {
        return $this->post('/login', ['email' => $user->email, 'password' => 'password']);
    }

    public function test_usuario_regular_con_sucursal_desactivada_no_puede_entrar(): void
    {
        $sucursal = Sucursales::factory()->create();
        $user = User::factory()->create(['id_sucursal' => $sucursal->id, 'id_empresa' => $sucursal->id_empresa]);
        $sucursal->delete();

        $this->login($user)->assertSessionHasErrors(['email' => 'La sucursal asociada a esta cuenta esta desactivada.']);
        $this->assertGuest();
    }

    public function test_admin_empresa_sin_sucursal_se_reasigna_a_una_activa(): void
    {
        $empresa = Empresa::factory()->create();
        $sucursal = Sucursales::factory()->create(['id_empresa' => $empresa->id]);
        $user = User::factory()->create(['tipo' => 'adminEmpresa', 'id_sucursal' => null, 'id_empresa' => $empresa->id]);

        $this->login($user);

        $this->assertAuthenticatedAs($user);
        $this->assertSame($sucursal->id, (int) $user->fresh()->id_sucursal);
    }

    public function test_admin_empresa_con_sucursal_desactivada_se_reasigna_a_otra_activa(): void
    {
        $empresa = Empresa::factory()->create();
        $borrada = Sucursales::factory()->create(['id_empresa' => $empresa->id]);
        $activa = Sucursales::factory()->create(['id_empresa' => $empresa->id, 'es_matriz' => false]);
        $user = User::factory()->create(['tipo' => 'adminEmpresa', 'id_sucursal' => $borrada->id, 'id_empresa' => $empresa->id]);
        $borrada->delete();

        $this->login($user);

        $this->assertAuthenticatedAs($user);
        $this->assertSame($activa->id, (int) $user->fresh()->id_sucursal);
    }

    public function test_admin_empresa_sin_sucursales_activas_no_puede_entrar(): void
    {
        $empresa = Empresa::factory()->create();
        $sucursal = Sucursales::factory()->create(['id_empresa' => $empresa->id]);
        $user = User::factory()->create(['tipo' => 'adminEmpresa', 'id_sucursal' => $sucursal->id, 'id_empresa' => $empresa->id]);
        $sucursal->delete();

        $this->login($user)->assertSessionHasErrors(['email' => 'La empresa asociada a esta cuenta no tiene sucursales activas.']);
        $this->assertGuest();
    }

    public function test_no_se_desactiva_sucursal_con_usuarios_activos(): void
    {
        $empresa = Empresa::factory()->create();
        $matriz = Sucursales::factory()->create(['id_empresa' => $empresa->id]);
        $otra = Sucursales::factory()->create(['id_empresa' => $empresa->id, 'es_matriz' => false]);
        $admin = User::factory()->create(['tipo' => 'admin', 'id_sucursal' => $matriz->id, 'id_empresa' => $empresa->id]);
        $vendedor = User::factory()->create(['tipo' => 'vendedor', 'id_sucursal' => $otra->id, 'id_empresa' => $empresa->id]);

        $this->actingAs($admin)->delete(route('sucursal.destroy', $otra->id))
            ->assertSessionHas('flash.bannerStyle', 'danger');
        $this->assertNotSoftDeleted($otra);

        $vendedor->delete();

        $this->actingAs($admin)->delete(route('sucursal.destroy', $otra->id));
        $this->assertSoftDeleted($otra);
    }

    public function test_editar_sucursal_envia_ancho_de_ticket_guardado(): void
    {
        $sucursal = Sucursales::factory()->create(['ticket_width_mm' => 58]);
        $superAdmin = User::factory()->create(['tipo' => 'superAdmin']);

        $this->actingAs($superAdmin)->get(route('sucursal.edit', $sucursal->id))
            ->assertInertia(fn ($page) => $page->component('Sucursal/CreateSucursal')
                ->where('sucursal.ticket_width_mm', fn ($value) => $value === 58));
    }

    public function test_actualizar_sucursal_guarda_matriz_bodega_y_ticket_y_la_edicion_los_envia(): void
    {
        $sucursal = Sucursales::factory()->create(['es_matriz' => false, 'es_bodega' => false, 'ticket_width_mm' => 80]);
        $superAdmin = User::factory()->create(['tipo' => 'superAdmin']);

        $this->actingAs($superAdmin)->put(route('sucursal.update', $sucursal->id), [
            'id' => $sucursal->id, 'nombre' => 'Bodega central', 'direccion' => 'Calle 1', 'id_empresa' => $sucursal->id_empresa,
            'es_matriz' => true, 'es_bodega' => true, 'ticket_width_mm' => 58,
        ])->assertRedirect(route('sucursal.index'));

        $sucursal->refresh();
        $this->assertTrue($sucursal->es_matriz);
        $this->assertTrue($sucursal->es_bodega);
        $this->assertSame(58, $sucursal->ticket_width_mm);

        $this->actingAs($superAdmin)->get(route('sucursal.edit', $sucursal->id))
            ->assertInertia(fn ($page) => $page->component('Sucursal/CreateSucursal')
                ->where('sucursal.es_matriz', true)
                ->where('sucursal.es_bodega', true)
                ->where('sucursal.ticket_width_mm', 58));

        $this->actingAs($superAdmin)->put(route('sucursal.update', $sucursal->id), [
            'id' => $sucursal->id, 'nombre' => 'Bodega central', 'direccion' => 'Calle 1', 'id_empresa' => $sucursal->id_empresa,
            'es_matriz' => false, 'es_bodega' => false, 'ticket_width_mm' => 80,
        ])->assertRedirect(route('sucursal.index'));
        $this->assertFalse($sucursal->fresh()->es_bodega);
        $this->assertFalse($sucursal->fresh()->es_matriz);
    }

    public function test_editar_admin_empresa_conserva_su_sucursal(): void
    {
        $empresa = Empresa::factory()->create();
        $sucursal = Sucursales::factory()->create(['id_empresa' => $empresa->id]);
        $superAdmin = User::factory()->create(['tipo' => 'superAdmin']);
        $adminEmpresa = User::factory()->create(['tipo' => 'adminEmpresa', 'id_sucursal' => $sucursal->id, 'id_empresa' => $empresa->id]);

        $this->actingAs($superAdmin)->put(route('usuario.update', $adminEmpresa->id), [
            'name' => 'Nuevo nombre',
            'email' => $adminEmpresa->email,
            'tipo' => 'adminEmpresa',
            'id_sucursal' => null,
            'id_empresa' => $empresa->id,
        ])->assertRedirect(route('usuario.index'));

        $adminEmpresa->refresh();
        $this->assertSame('Nuevo nombre', $adminEmpresa->name);
        $this->assertSame($sucursal->id, (int) $adminEmpresa->id_sucursal);
    }

    public function test_editar_usuario_usa_id_de_la_ruta_y_requiere_sucursal(): void
    {
        $sucursal = Sucursales::factory()->create();
        $superAdmin = User::factory()->create(['tipo' => 'superAdmin']);
        $a = User::factory()->create(['id_sucursal' => $sucursal->id, 'id_empresa' => $sucursal->id_empresa]);
        $b = User::factory()->create(['id_sucursal' => $sucursal->id, 'id_empresa' => $sucursal->id_empresa]);

        $this->actingAs($superAdmin)->put(route('usuario.update', $b->id), [
            'id' => $a->id,
            'name' => 'B editado',
            'email' => $b->email,
            'tipo' => 'vendedor',
            'id_sucursal' => null,
        ])->assertSessionHasErrors('id_sucursal');

        $this->actingAs($superAdmin)->put(route('usuario.update', $b->id), [
            'id' => $a->id,
            'name' => 'B editado',
            'email' => $b->email,
            'tipo' => 'vendedor',
            'id_sucursal' => $sucursal->id,
        ]);

        $this->assertSame('B editado', $b->fresh()->name);
        $this->assertNotSame('B editado', $a->fresh()->name);
    }

    public function test_admin_no_puede_mover_usuario_a_sucursal_de_otra_empresa(): void
    {
        $propia = Sucursales::factory()->create();
        $ajena = Sucursales::factory()->create();
        $admin = User::factory()->create(['tipo' => 'admin', 'id_sucursal' => $propia->id, 'id_empresa' => $propia->id_empresa]);
        $vendedor = User::factory()->create(['id_sucursal' => $propia->id, 'id_empresa' => $propia->id_empresa]);

        $this->actingAs($admin)->put(route('usuario.update', $vendedor->id), [
            'name' => $vendedor->name,
            'email' => $vendedor->email,
            'tipo' => 'vendedor',
            'id_sucursal' => $ajena->id,
        ])->assertSessionHasErrors('id_sucursal');

        $this->assertSame($propia->id, (int) $vendedor->fresh()->id_sucursal);
    }
}
