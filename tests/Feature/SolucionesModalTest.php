<?php

namespace Tests\Feature;

use App\Models\CatClasificacion;
use App\Models\CatEnfermedades;
use App\Models\CatMarca;
use App\Models\CatTipoFlor;
use App\Models\Empresa;
use App\Models\EnfermedadesTipoFlor;
use App\Models\Producto;
use App\Models\SolucionEnfermedad;
use App\Models\Sucursales;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SolucionesModalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Sucursales $branch;
    private Producto $product;
    private EnfermedadesTipoFlor $enfermedadFlor;

    protected function setUp(): void
    {
        parent::setUp();
        $company = Empresa::factory()->create();
        $this->branch = Sucursales::factory()->create(['id_empresa' => $company->id]);
        $this->user = User::factory()->create(['tipo' => 'adminEmpresa', 'id_empresa' => $company->id, 'id_sucursal' => $this->branch->id]);
        $this->product = Producto::factory()->create(['id_empresa' => $company->id, 'id_usuario' => $this->user->id,
            'id_marca' => CatMarca::factory()->create()->id, 'id_clasificacion' => CatClasificacion::factory()->create()->id]);
        $this->enfermedadFlor = EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => CatTipoFlor::factory()->create(['nombre' => 'Rosa'])->id,
            'id_enfermedad' => CatEnfermedades::factory()->create(['nombre' => 'Roya'])->id,
        ]);
    }

    private function asUser()
    {
        return $this->actingAs($this->user)->withSession(['sucursal_activa' => $this->branch->id]);
    }

    public function test_modal_carga_soluciones_y_catalogo_en_json(): void
    {
        SolucionEnfermedad::factory()->create(['id_producto' => $this->product->id, 'id_enfermedad_tipo_flor' => $this->enfermedadFlor->id,
            'id_sucursal' => $this->branch->id, 'dosis_bomba_ml' => 25, 'dosis_tambo_ml' => 375]);

        $this->asUser()->getJson(route('solucion.index', ['id_producto' => $this->product->id]))
            ->assertOk()
            ->assertJsonCount(1, 'soluciones')
            ->assertJsonPath('soluciones.0.enfermedad.nombre', 'Roya')
            ->assertJsonPath('soluciones.0.tipoFlor.nombre', 'Rosa')
            ->assertJsonPath('enfermedadesFlor.0.nombre', 'Roya en Rosa');
    }

    public function test_modal_crea_y_actualiza_solucion_en_json(): void
    {
        $id = $this->asUser()->postJson(route('solucion.store'), ['id_producto' => $this->product->id,
            'id_enfermedad_tipo_flor' => $this->enfermedadFlor->id, 'dosis_bomba_ml' => 20, 'dosis_tambo_ml' => 300, 'condiciones' => 'Mañana'])
            ->assertOk()
            ->assertJsonPath('enfermedad.nombre', 'Roya')
            ->json('id');

        $this->asUser()->putJson(route('solucion.update', $id), ['id' => $id, 'id_producto' => $this->product->id,
            'id_enfermedad_tipo_flor' => $this->enfermedadFlor->id, 'dosis_bomba_ml' => 30, 'dosis_tambo_ml' => 450, 'condiciones' => 'Tarde'])
            ->assertOk()
            ->assertJsonPath('condiciones', 'Tarde');

        $this->assertDatabaseHas('solucion_enfermedads', ['id' => $id, 'dosis_bomba_ml' => 30, 'condiciones' => 'Tarde', 'id_sucursal' => $this->branch->id]);
        $this->asUser()->postJson(route('solucion.store'), ['id_producto' => $this->product->id])->assertUnprocessable()
            ->assertJsonValidationErrors(['dosis_bomba_ml', 'dosis_tambo_ml', 'id_enfermedad_tipo_flor']);
    }

    public function test_pantalla_de_soluciones_sigue_funcionando(): void
    {
        $this->asUser()->get(route('solucion.index', ['id_producto' => $this->product->id]))->assertOk();
    }
}
