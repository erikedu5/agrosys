<?php

namespace App\Services\Auditoria;

use App\Models\AltaInventario;
use App\Models\Auditoria;
use App\Models\Sucursales;
use Illuminate\Validation\ValidationException;

/**
 * Bloqueo híbrido: mientras una auditoría está en conteo o revisión, la sucursal
 * no admite movimientos de inventario distintos a ventas.
 */
class InventoryFreeze
{
    /** Eventos del ledger que siguen permitidos durante una auditoría. */
    public const EVENTOS_PERMITIDOS = [AltaInventario::EVENTO_VENTA];

    public function auditoriaActiva(Sucursales|int|null $sucursal): ?Auditoria
    {
        $id = $sucursal instanceof Sucursales ? $sucursal->id : $sucursal;
        if (! $id) {
            return null;
        }

        // Sin memo: el estado cambia dentro del mismo request/prueba y la consulta es por índice.
        return Auditoria::where('id_sucursal', $id)->whereIn('status', Auditoria::BLOQUEANTES)
            ->orderByDesc('id')->first(['id', 'folio', 'id_sucursal', 'status']);
    }

    public function isFrozen(Sucursales|int|null $sucursal): bool
    {
        return $this->auditoriaActiva($sucursal) !== null;
    }

    /** @throws ValidationException */
    public function assertNotFrozen(Sucursales|int|null $sucursal, string $operacion): void
    {
        if ($auditoria = $this->auditoriaActiva($sucursal)) {
            throw ValidationException::withMessages([
                'auditoria' => "No se puede realizar {$operacion}: la sucursal está en auditoría de inventario (folio {$auditoria->folio}). Las ventas siguen permitidas.",
            ]);
        }
    }

    /** Respaldo a nivel ledger para escritores que no pasan por los servicios. */
    public function guardLedger(AltaInventario $movimiento): void
    {
        if (in_array($movimiento->tipo_evento, self::EVENTOS_PERMITIDOS, true)) {
            return;
        }
        // El alta en cero de un producto nuevo no cambia existencias.
        if ((string) $movimiento->cantidad_actual === (string) $movimiento->cantidad_nueva
            || (float) $movimiento->cantidad_actual === (float) $movimiento->cantidad_nueva) {
            return;
        }
        $this->assertNotFrozen((int) $movimiento->id_sucursal, 'este movimiento de inventario');
    }
}
