<?php

namespace Tests\Feature\Auditoria;

use App\Models\CatMarca;
use App\Services\Auditoria\AuditoriaReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AuditoriaReporteTest extends TestCase
{
    use RefreshDatabase;
    use AuditoriaHelpers;

    private \App\Models\Auditoria $auditoria;

    protected function setUp(): void
    {
        parent::setUp();
        $this->escenario();
        $marcaA = CatMarca::factory()->create(['nombre' => 'Alfa']);
        $marcaB = CatMarca::factory()->create(['nombre' => 'Beta']);
        $a1 = $this->producto('A1', $marcaA, 10, costo: 5);
        $a2 = $this->producto('A2', $marcaA, 4, costo: 5);
        $b1 = $this->producto('B1', $marcaB, 6, costo: 20);
        $this->auditoria = $this->auditoriaIniciada();
        foreach ([[$a1, 8], [$a2, 4], [$b1, 7]] as [$producto, $contado]) {
            $this->management()->capturar($this->auditor, $this->auditoria, ['id_producto' => $producto->id, 'cantidad' => $contado]);
        }
        $this->management()->cerrarConteo($this->auditor, $this->auditoria);
        $this->management()->finalizar($this->auditor, $this->auditoria);
    }

    public function test_subtotales_por_marca_y_valorizacion_para_admin(): void
    {
        $data = app(AuditoriaReport::class)->auditoria($this->admin, $this->auditoria->fresh());

        $this->assertSame(['Alfa', 'Beta'], array_column($data['marcas'], 'marca'));
        $this->assertSame('-2.00', $data['marcas'][0]['totales']['unidades_neto']);
        $this->assertSame('1.00', $data['marcas'][1]['totales']['unidades_sobrantes']);
        $this->assertSame('-10.00', $data['marcas'][0]['totales']['valor_neto']);
        $this->assertSame('10.00', $data['resumen']['valor_neto']);
        $this->assertEquals(33.3, $data['resumen']['exactitud']);
    }

    public function test_auditor_no_ve_costos(): void
    {
        $data = app(AuditoriaReport::class)->auditoria($this->auditor, $this->auditoria->fresh());

        $this->assertFalse($data['ver_costos']);
        $this->assertArrayNotHasKey('valor_neto', $data['resumen']);
        $this->assertArrayNotHasKey('costo_unitario', $data['marcas'][0]['renglones'][0]);
    }

    public function test_filtros_por_marca_sku_y_diferencias(): void
    {
        $report = app(AuditoriaReport::class);
        $marcaB = CatMarca::where('nombre', 'Beta')->value('id');

        $this->assertSame(['Beta'], array_column($report->auditoria($this->admin, $this->auditoria, ['marca' => $marcaB])['marcas'], 'marca'));
        $porSku = $report->auditoria($this->admin, $this->auditoria, ['sku' => 'BC-A2']);
        $this->assertSame(['A2'], array_column($porSku['marcas'][0]['renglones'], 'nombre'));
        $difs = $report->auditoria($this->admin, $this->auditoria, ['solo_diferencias' => 1]);
        $this->assertSame(2, $difs['resumen']['renglones']);
    }

    public function test_descarga_excel(): void
    {
        $this->actingAs($this->auditor);
        $this->get(route('auditorias.reporte.xlsx', $this->auditoria->id))->assertOk()->assertDownload("auditoria-{$this->auditoria->folio}.xlsx");
        $this->get(route('auditorias.show', $this->auditoria->id))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('reporte.resumen.renglones', 3)->where('reporte.ver_costos', false));
    }

    public function test_historico_filtra_por_fecha_y_sku(): void
    {
        $this->actingAs($this->admin);
        $hoy = now()->toDateString();

        $this->get(route('auditorias.historico', ['desde' => $hoy, 'hasta' => $hoy, 'sku' => 'BC-B1']))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('resultados', 1)
                ->where('resultados.0.folio', $this->auditoria->folio)
                ->has('resultados.0.renglones', 1)
                ->where('resultados.0.renglones.0.diferencia', '1.00'));

        $this->get(route('auditorias.historico', ['desde' => now()->addDay()->toDateString()]))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('resultados', 0));

        $this->get(route('auditorias.historico', ['sku' => 'BC-B1', 'formato' => 'xlsx']))->assertOk()->assertDownload('auditorias-historico.xlsx');
    }
}
