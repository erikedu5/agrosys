<?php

namespace Tests\Feature;

use App\Models\AltaInventario;
use App\Models\CatClasificacion;
use App\Models\CatMarca;
use App\Models\Clientes;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\ProductoVenta;
use App\Models\Sucursales;
use App\Models\User;
use App\Models\Venta;
use App\Services\DashboardMetrics;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    private Sucursales $centro;
    private Sucursales $norte;
    private Clientes $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-08 13:30:00', 'America/Mexico_City'));

        $empresa = Empresa::factory()->create();
        $this->centro = Sucursales::factory()->create(['id_empresa' => $empresa->id, 'nombre' => 'Centro']);
        $this->norte = Sucursales::factory()->create(['id_empresa' => $empresa->id, 'nombre' => 'Norte']);
        CatMarca::factory()->create();
        CatClasificacion::factory()->create();
        $this->cliente = Clientes::factory()->create(['id_sucursal' => $this->centro->id, 'balance' => 450]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function venta(User $user, Sucursales $sucursal, float $total, string $cuando, string $tipo = 'Contado'): Venta
    {
        $venta = Venta::factory()->create([
            'total' => (string) $total,
            'tipo_venta' => $tipo,
            'id_usuario' => $user->id,
            'id_cliente' => $this->cliente->id,
            'id_sucursal' => $sucursal->id,
        ]);
        $venta->forceFill(['created_at' => Carbon::parse($cuando)])->save();

        return $venta;
    }

    public function test_profiles_map_from_user_type(): void
    {
        $this->assertSame('empresa', DashboardMetrics::perfilDe(new User(['tipo' => 'adminEmpresa'])));
        $this->assertSame('empresa', DashboardMetrics::perfilDe(new User(['tipo' => 'superAdmin'])));
        $this->assertSame('admin', DashboardMetrics::perfilDe(new User(['tipo' => 'admin'])));
        $this->assertSame('inventario', DashboardMetrics::perfilDe(new User(['tipo' => 'inventario'])));
        $this->assertSame('vendedor', DashboardMetrics::perfilDe(new User(['tipo' => 'vendedor'])));
    }

    public function test_seller_sees_own_sales_against_branch_today(): void
    {
        $yo = User::factory()->create(['id_sucursal' => $this->centro->id]);
        $otro = User::factory()->create(['id_sucursal' => $this->centro->id]);

        $this->venta($yo, $this->centro, 100, '2026-10-08 09:15:00');
        $this->venta($yo, $this->centro, 50, '2026-10-08 12:05:00');
        $this->venta($otro, $this->centro, 250, '2026-10-08 12:40:00');
        $this->venta($yo, $this->centro, 999, '2026-10-07 18:00:00'); // ayer
        $this->venta($yo, $this->norte, 999, '2026-10-08 10:00:00');  // otra sucursal

        $m = (new DashboardMetrics($yo, $this->centro->id, $this->centro->id_empresa))->build();

        $this->assertSame('vendedor', $m['perfil']);
        $this->assertEquals(150, $m['resumen']['misVentasHoy']);
        $this->assertSame(2, $m['resumen']['misTicketsHoy']);
        $this->assertEquals(400, $m['resumen']['ventasSucursalHoy']);
        $porHora = collect($m['ventasPorHora'])->keyBy('hora');
        $this->assertEquals(300, $porHora[12]['total']);
        $this->assertSame(2, $porHora[12]['tickets']);
        $this->assertEquals(0, $porHora[8]['total']);
    }

    public function test_company_admin_gets_period_comparison_split_and_branches(): void
    {
        $admin = User::factory()->create(['tipo' => 'adminEmpresa', 'id_empresa' => $this->centro->id_empresa]);
        $vendedor = User::factory()->create(['name' => 'Ana López']);

        $this->venta($vendedor, $this->centro, 300, '2026-10-06 10:00:00');
        $this->venta($vendedor, $this->centro, 200, '2026-10-06 11:00:00', 'Credito');
        $this->venta($vendedor, $this->norte, 100, '2026-10-02 11:00:00');
        $this->venta($vendedor, $this->centro, 300, '2026-09-28 11:00:00'); // periodo anterior (7 días)

        $m = (new DashboardMetrics($admin, $this->centro->id, $this->centro->id_empresa, 7))->build();

        $this->assertSame('empresa', $m['perfil']);
        $this->assertEquals(600, $m['resumen']['ventas']);
        $this->assertEquals(300, $m['resumen']['ventasAnterior']);
        $this->assertSame(3, $m['resumen']['tickets']);
        $this->assertEquals(450, $m['resumen']['porCobrar']);

        $this->assertSame('dia', $m['serie']['agrupacion']);
        $this->assertCount(7, $m['serie']['puntos']);
        $dia = collect($m['serie']['puntos'])->firstWhere('fecha', '2026-10-06');
        $this->assertEquals(300, $dia['contado']);
        $this->assertEquals(200, $dia['credito']);

        $this->assertSame(['Centro', 'Norte'], collect($m['porSucursal'])->pluck('nombre')->all());
        $this->assertSame('Ana López', $m['porVendedor'][0]['nombre']);
    }

    public function test_long_periods_group_by_week(): void
    {
        $admin = User::factory()->create(['tipo' => 'admin']);
        $m = (new DashboardMetrics($admin, $this->centro->id, $this->centro->id_empresa, 90))->build();

        $this->assertSame('semana', $m['serie']['agrupacion']);
        $this->assertLessThanOrEqual(14, count($m['serie']['puntos']));
        $this->assertArrayNotHasKey('porSucursal', $m);
    }

    public function test_stock_coverage_flags_products_running_out(): void
    {
        $user = User::factory()->create(['tipo' => 'inventario']);
        $rapido = Producto::factory()->create(['nombre' => 'Urea 46%', 'id_marca' => 1, 'id_clasificacion' => 1, 'id_usuario' => $user->id, 'id_empresa' => $this->centro->id_empresa]);
        $agotado = Producto::factory()->create(['nombre' => 'Captan 50 PH', 'id_marca' => 1, 'id_clasificacion' => 1, 'id_usuario' => $user->id, 'id_empresa' => $this->centro->id_empresa]);
        $lento = Producto::factory()->create(['nombre' => 'Glifosato', 'id_marca' => 1, 'id_clasificacion' => 1, 'id_usuario' => $user->id, 'id_empresa' => $this->centro->id_empresa]);

        AltaInventario::factory()->create(['id_usuario' => $user->id, 'id_producto' => $rapido->id, 'id_sucursal' => $this->centro->id, 'cantidad_nueva' => 50]);
        AltaInventario::factory()->create(['id_usuario' => $user->id, 'id_producto' => $rapido->id, 'id_sucursal' => $this->centro->id, 'cantidad_nueva' => 2]);
        AltaInventario::factory()->create(['id_usuario' => $user->id, 'id_producto' => $agotado->id, 'id_sucursal' => $this->centro->id, 'cantidad_nueva' => 0]);
        AltaInventario::factory()->create(['id_usuario' => $user->id, 'id_producto' => $lento->id, 'id_sucursal' => $this->centro->id, 'cantidad_nueva' => 500]);

        $venta = $this->venta($user, $this->centro, 1000, '2026-10-01 10:00:00');
        ProductoVenta::factory()->create(['id_venta' => $venta->id, 'id_producto' => $rapido->id, 'cantidad' => 30, 'total_productos' => 600]);
        ProductoVenta::factory()->create(['id_venta' => $venta->id, 'id_producto' => $agotado->id, 'cantidad' => 3, 'total_productos' => 300]);
        ProductoVenta::factory()->create(['id_venta' => $venta->id, 'id_producto' => $lento->id, 'cantidad' => 3, 'total_productos' => 100]);

        $m = (new DashboardMetrics($user, $this->centro->id, $this->centro->id_empresa))->build();

        $nombres = collect($m['porAgotarse'])->pluck('nombre')->all();
        $this->assertSame(['Captan 50 PH', 'Urea 46%'], $nombres);
        $this->assertEquals(2.0, $m['porAgotarse'][1]['dias']);
        $this->assertSame(1, $m['resumen']['agotados']);
        $this->assertSame(1, $m['resumen']['porAgotarse']);
        $this->assertEquals(36, $m['resumen']['unidadesVendidas']);
        $this->assertSame('Urea 46%', $m['topProductos'][0]['nombre']);
    }
}
