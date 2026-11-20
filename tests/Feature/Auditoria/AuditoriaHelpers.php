<?php

namespace Tests\Feature\Auditoria;

use App\Models\AltaInventario;
use App\Models\Auditoria;
use App\Models\AuditorAlcance;
use App\Models\CatClasificacion;
use App\Models\CatMarca;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\User;
use App\Services\Auditoria\AuditoriaManagement;

trait AuditoriaHelpers
{
    protected Empresa $empresa;
    protected Sucursales $sucursal;
    protected Sucursales $otraSucursal;
    protected User $admin;
    protected User $auditor;

    protected function escenario(): void
    {
        $this->empresa = Empresa::factory()->create();
        $this->sucursal = Sucursales::factory()->create(['id_empresa' => $this->empresa->id, 'nombre' => 'Bodega Norte', 'es_bodega' => true]);
        $this->otraSucursal = Sucursales::factory()->create(['id_empresa' => $this->empresa->id, 'nombre' => 'Centro']);
        $this->admin = User::factory()->create(['tipo' => 'adminEmpresa', 'id_empresa' => $this->empresa->id, 'id_sucursal' => $this->sucursal->id]);
        $this->auditor = $this->crearAuditor([[$this->empresa->id, null]]);
    }

    /** @param array<array{0:int,1:?int}> $alcances */
    protected function crearAuditor(array $alcances, array $attrs = []): User
    {
        $auditor = User::factory()->create(['tipo' => User::TIPO_AUDITOR, 'id_sucursal' => null, 'id_empresa' => null] + $attrs);
        foreach ($alcances as [$empresa, $sucursal]) {
            AuditorAlcance::create(['id_usuario' => $auditor->id, 'id_empresa' => $empresa, 'id_sucursal' => $sucursal]);
        }

        return $auditor;
    }

    protected function producto(string $nombre, ?CatMarca $marca = null, float $stock = 0, ?Sucursales $sucursal = null, float $costo = 10): Producto
    {
        $producto = Producto::factory()->create([
            'nombre' => $nombre,
            'tamano' => '1 L',
            'barcode' => 'BC-'.$nombre,
            'id_empresa' => $this->empresa->id,
            'id_usuario' => $this->admin->id,
            'id_marca' => ($marca ?? CatMarca::factory()->create())->id,
            'id_clasificacion' => CatClasificacion::factory()->create()->id,
            'precio_unitario' => $costo,
        ]);
        if ($stock > 0) {
            AltaInventario::create(['cantidad_actual' => 0, 'cantidad_nueva' => $stock, 'id_usuario' => $this->admin->id,
                'id_producto' => $producto->id, 'id_sucursal' => ($sucursal ?? $this->sucursal)->id, 'tipo_evento' => AltaInventario::EVENTO_ALTA]);
        }

        return $producto;
    }

    protected function management(): AuditoriaManagement
    {
        return app(AuditoriaManagement::class);
    }

    protected function auditoriaIniciada(array $input = []): Auditoria
    {
        $auditoria = $this->management()->crear($this->auditor, $input + ['id_sucursal' => $this->sucursal->id, 'alcance_tipo' => 'total']);

        return $this->management()->iniciar($this->auditor, $auditoria, true);
    }

    protected function stock(Producto $producto, ?Sucursales $sucursal = null): float
    {
        return (float) AltaInventario::where('id_producto', $producto->id)->where('id_sucursal', ($sucursal ?? $this->sucursal)->id)
            ->orderByDesc('id')->value('cantidad_nueva');
    }
}
