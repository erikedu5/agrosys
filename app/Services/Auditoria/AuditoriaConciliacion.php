<?php

namespace App\Services\Auditoria;

use App\Models\AltaInventario;
use App\Models\Auditoria;
use App\Models\AuditoriaConteo;
use App\Models\AuditoriaItem;
use App\Services\Pos\Decimal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Calcula esperado y diferencia por renglón usando cortes del ledger (alta_inventarios.id).
 * Nunca escribe en alta_inventarios.
 */
class AuditoriaConciliacion
{
    /** Cantidad del ledger a unidades ×100; tolera valores legados con más decimales. */
    public static function units(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        $text = is_float($value) ? rtrim(rtrim(sprintf('%.8F', $value), '0'), '.') : trim((string) $value);
        if (preg_match('/^(-?)(\d{1,10})(?:\.(\d{1,2}))?$/', $text, $m)) {
            $units = (int) $m[2] * 100 + (int) str_pad($m[3] ?? '', 2, '0');

            return $m[1] === '-' ? -$units : $units;
        }

        return (int) round(((float) $value) * 100);
    }

    public static function corteActual(): int
    {
        return (int) (AltaInventario::max('id') ?? 0);
    }

    /** Existencia por producto al corte indicado: [id_producto => unidades]. */
    public function existenciasAlCorte(int $sucursalId, int $corte, ?array $productIds = null): array
    {
        $ultimos = DB::table('alta_inventarios')->selectRaw('MAX(id)')->where('id_sucursal', $sucursalId)
            ->where('id', '<=', $corte)
            ->when($productIds !== null, fn ($q) => $q->whereIn('id_producto', $productIds ?: [0]))
            ->groupBy('id_producto');

        return DB::table('alta_inventarios')->whereIn('id', $ultimos)->get(['id_producto', 'cantidad_nueva'])
            ->mapWithKeys(fn ($row) => [(int) $row->id_producto => self::units($row->cantidad_nueva)])->all();
    }

    /**
     * Recalcula cantidad contada, contado_at, corte y estado del renglón a partir de sus capturas vigentes.
     * Ronda 2 sin capturas conserva la ronda 1.
     */
    public function recalcularTotales(AuditoriaItem $item): AuditoriaItem
    {
        $conteos = AuditoriaConteo::where('id_auditoria_item', $item->id)->whereNull('anulado_at')->orderBy('id')->get();
        $r1 = $conteos->where('ronda', 1);
        $r2 = $conteos->where('ronda', 2);
        $vigentes = $item->ronda === 2 && $r2->isNotEmpty() ? $r2 : $r1;

        $item->contado_r1 = $r1->isEmpty() ? null : Decimal::format($r1->sum(fn ($c) => self::units($c->cantidad)));
        if ($vigentes->isEmpty()) {
            $item->fill(['cantidad_contada' => null, 'contado_at' => null, 'corte_conteo_id' => null,
                'esperado' => null, 'diferencia' => null]);
        } else {
            $ultimo = $vigentes->last();
            $item->fill([
                'cantidad_contada' => Decimal::format($vigentes->sum(fn ($c) => self::units($c->cantidad))),
                'contado_at' => $ultimo->created_at,
                'corte_conteo_id' => (int) $vigentes->max('corte_id'),
            ]);
        }
        $item->estado = match (true) {
            $item->ronda === 2 && $r2->isEmpty() => AuditoriaItem::ESTADO_RECONTEO,
            $vigentes->isEmpty() => AuditoriaItem::ESTADO_PENDIENTE,
            default => AuditoriaItem::ESTADO_CONTADO,
        };
        $item->save();

        return $item;
    }

    /** Concilia todos los renglones contados de la auditoría (o sólo los indicados). */
    public function conciliar(Auditoria $auditoria, ?Collection $items = null): void
    {
        $items ??= $auditoria->items()->get();
        $items = $items->filter(fn (AuditoriaItem $i) => $i->cantidad_contada !== null);
        if ($items->isEmpty()) {
            return;
        }
        $corteInicio = (int) $auditoria->corte_inicio_id;
        $productIds = $items->pluck('id_producto')->filter()->unique()->values()->all();

        // Movimientos de la sucursal posteriores al corte de inicio, en orden del ledger.
        $movimientos = collect();
        $ventasTardias = collect();
        $transito = ['salida' => [], 'entrada' => []];
        if ($productIds) {
            $movimientos = DB::table('alta_inventarios')->where('id_sucursal', $auditoria->id_sucursal)
                ->where('id', '>', $corteInicio)->whereIn('id_producto', $productIds)->orderBy('id')
                ->get(['id', 'id_producto', 'cantidad_actual', 'cantidad_nueva', 'tipo_evento'])->groupBy('id_producto');
            $ventasTardias = $this->ventasConFechaAnterior($auditoria, $productIds)->groupBy('id_producto');
            $transito = $this->transito((int) $auditoria->id_sucursal, $productIds);
        }

        foreach ($items as $item) {
            $contado = self::units($item->cantidad_contada);
            $inicio = self::units($item->cantidad_sistema_inicio);
            if (! $item->id_producto) {
                // Producto físico sin registro en catálogo: todo lo contado es sobrante.
                $item->fill(['esperado' => '0.00', 'diferencia' => Decimal::format($contado), 'movimientos_durante' => '0.00',
                    'desglose_movimientos' => null, 'ventas_offline_tardias' => '0.00'])->save();

                continue;
            }
            $corte = (int) $item->corte_conteo_id;
            $alConteo = $inicio;
            $desglose = [];
            foreach ($movimientos->get($item->id_producto, collect()) as $mov) {
                if ((int) $mov->id > $corte) {
                    break;
                }
                $alConteo = self::units($mov->cantidad_nueva);
                $delta = self::units($mov->cantidad_nueva) - self::units($mov->cantidad_actual);
                $desglose[$mov->tipo_evento] = ($desglose[$mov->tipo_evento] ?? 0) + $delta;
            }
            $tardias = $ventasTardias->get($item->id_producto, collect())
                ->filter(fn ($v) => $item->contado_at && $v->occurred_at <= $item->contado_at && $v->created_at > $item->contado_at)
                ->sum(fn ($v) => self::units($v->cantidad));
            $esperado = $alConteo - $tardias;

            $item->fill([
                'movimientos_durante' => Decimal::format($alConteo - $inicio),
                'desglose_movimientos' => $desglose ? array_map(fn ($u) => Decimal::format($u), $desglose) : null,
                'ventas_offline_tardias' => Decimal::format($tardias),
                'esperado' => Decimal::format($esperado),
                'diferencia' => Decimal::format($contado - $esperado),
                'transito_salida' => Decimal::format($transito['salida'][$item->id_producto] ?? 0),
                'transito_entrada' => Decimal::format($transito['entrada'][$item->id_producto] ?? 0),
            ])->save();
        }
    }

    /**
     * Ventas de la sucursal cuya hora real (occurred_at) es anterior a su registro (created_at):
     * ventas offline/POS sincronizadas tarde. Se filtran por renglón según su contado_at.
     */
    private function ventasConFechaAnterior(Auditoria $auditoria, array $productIds): Collection
    {
        $desde = $auditoria->iniciada_at ?? $auditoria->created_at;

        return DB::table('producto_ventas')->join('ventas', 'ventas.id', '=', 'producto_ventas.id_venta')
            ->where('ventas.id_sucursal', $auditoria->id_sucursal)->whereNotNull('ventas.occurred_at')
            ->where('ventas.created_at', '>=', $desde)->whereColumn('ventas.occurred_at', '<', 'ventas.created_at')
            ->whereIn('producto_ventas.id_producto', $productIds)
            ->get(['producto_ventas.id_producto', 'producto_ventas.cantidad', 'ventas.occurred_at', 'ventas.created_at'])
            ->map(function ($row) {
                $row->occurred_at = \Illuminate\Support\Carbon::parse($row->occurred_at);
                $row->created_at = \Illuminate\Support\Carbon::parse($row->created_at);

                return $row;
            });
    }

    /** Cantidades en transferencias pendientes desde/hacia la sucursal. */
    private function transito(int $sucursalId, array $productIds): array
    {
        $rows = DB::table('transferencia_detalles')->join('transferencias', 'transferencias.id', '=', 'transferencia_detalles.id_transferencia')
            ->where('transferencias.status', 'pendiente')
            ->where(fn ($q) => $q->where('transferencias.id_sucursal_origen', $sucursalId)->orWhere('transferencias.id_sucursal_destino', $sucursalId))
            ->whereIn('transferencia_detalles.id_producto', $productIds)
            ->get(['transferencia_detalles.id_producto', 'transferencia_detalles.cantidad', 'transferencias.id_sucursal_origen']);
        $out = ['salida' => [], 'entrada' => []];
        foreach ($rows as $row) {
            $tipo = (int) $row->id_sucursal_origen === $sucursalId ? 'salida' : 'entrada';
            $out[$tipo][(int) $row->id_producto] = ($out[$tipo][(int) $row->id_producto] ?? 0) + self::units($row->cantidad);
        }

        return $out;
    }
}
