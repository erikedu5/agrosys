<?php

namespace App\Services\Auditoria;

use App\Models\AltaInventario;
use App\Models\Auditoria;
use App\Models\AuditoriaItem;
use App\Models\User;
use App\Services\Pos\Decimal;
use Illuminate\Support\Collection;

class AuditoriaReport
{
    public function __construct(private AuditoriaAccess $access)
    {
    }

    /**
     * Datos del reporte de una auditoría. Filtros: marca (id), sku (barcode o id), solo_diferencias.
     * Sin permiso de costos, se omiten importes.
     */
    public function auditoria(User $user, Auditoria $auditoria, array $filtros = []): array
    {
        $auditoria->loadMissing(['empresa:id,nombre', 'sucursal:id,nombre,es_bodega', 'auditores:id,name,email', 'creador:id,name']);
        $costos = $this->access->verCostos($user);
        $items = $this->items($auditoria, $filtros);
        $renglones = $items->map(fn (AuditoriaItem $i) => $this->renglon($i, $costos));

        $marcas = $renglones->groupBy(fn ($r) => $r['marca'])->map(fn (Collection $rows, $marca) => [
            'marca' => $marca,
            'renglones' => $rows->values()->all(),
            'totales' => $this->totales($rows, $costos),
        ])->sortKeys()->values()->all();

        $movimientos = [];
        foreach ($items as $item) {
            foreach ($item->desglose_movimientos ?? [] as $tipo => $cantidad) {
                $movimientos[$tipo] = ($movimientos[$tipo] ?? 0) + AuditoriaConciliacion::units($cantidad);
            }
        }

        return [
            'auditoria' => [
                'id' => $auditoria->id,
                'folio' => $auditoria->folio,
                'status' => $auditoria->status,
                'status_label' => Auditoria::ETIQUETAS[$auditoria->status] ?? $auditoria->status,
                'empresa' => $auditoria->empresa?->nombre,
                'sucursal' => $auditoria->sucursal?->nombre,
                'es_bodega' => (bool) $auditoria->sucursal?->es_bodega,
                'alcance_tipo' => $auditoria->alcance_tipo,
                'alcance' => $this->alcanceTexto($auditoria),
                'auditores' => $auditoria->auditores->pluck('name')->all(),
                'creador' => $auditoria->creador?->name,
                'created_at' => $auditoria->created_at?->toIso8601String(),
                'iniciada_at' => $auditoria->iniciada_at?->toIso8601String(),
                'conteo_cerrado_at' => $auditoria->conteo_cerrado_at?->toIso8601String(),
                'finalizada_at' => $auditoria->finalizada_at?->toIso8601String(),
                'cancelada_at' => $auditoria->cancelada_at?->toIso8601String(),
                'motivo_cancelacion' => $auditoria->motivo_cancelacion,
                'notas' => $auditoria->notas,
            ],
            'ver_costos' => $costos,
            'resumen' => $this->totales($renglones, $costos) + ['renglones_total' => $auditoria->items()->count()],
            'marcas' => $marcas,
            'movimientos_durante' => collect($movimientos)->map(fn ($u, $tipo) => [
                'tipo' => $tipo, 'etiqueta' => AltaInventario::ETIQUETAS[$tipo] ?? $tipo, 'cantidad' => Decimal::format($u),
            ])->values()->all(),
            'no_esperados' => $renglones->where('no_esperado', true)->values()->all(),
            'reconteos' => $renglones->where('ronda', 2)->values()->all(),
        ];
    }

    public function items(Auditoria $auditoria, array $filtros = []): Collection
    {
        $sku = trim((string) ($filtros['sku'] ?? ''));

        return $auditoria->items()
            ->when(! empty($filtros['marca']), fn ($q) => $q->where('id_marca', (int) $filtros['marca']))
            ->when($sku !== '', fn ($q) => $q->where(fn ($w) => $w->where('barcode', $sku)
                ->orWhere(fn ($x) => ctype_digit($sku) ? $x->where('id_producto', (int) $sku) : $x->whereRaw('1 = 0'))))
            ->when(! empty($filtros['solo_diferencias']), fn ($q) => $q->whereNotNull('diferencia')->where('diferencia', '!=', 0))
            ->orderBy('marca_nombre')->orderBy('nombre')->get();
    }

    public function renglon(AuditoriaItem $i, bool $costos): array
    {
        $diferencia = $i->diferencia === null ? null : AuditoriaConciliacion::units($i->diferencia);
        $row = [
            'id' => $i->id,
            'id_producto' => $i->id_producto,
            'sku' => $i->sku,
            'nombre' => $i->nombre,
            'tamano' => $i->tamano,
            'marca' => $i->marca_nombre ?: 'Sin marca',
            'clasificacion' => $i->clasificacion_nombre,
            'sistema_inicio' => $this->fmt($i->cantidad_sistema_inicio),
            'movimientos_durante' => $this->fmt($i->movimientos_durante),
            'ventas_offline_tardias' => $this->fmt($i->ventas_offline_tardias),
            'esperado' => $i->esperado === null ? null : $this->fmt($i->esperado),
            'contado' => $i->cantidad_contada === null ? null : $this->fmt($i->cantidad_contada),
            'contado_r1' => $i->contado_r1 === null ? null : $this->fmt($i->contado_r1),
            'ronda' => (int) $i->ronda,
            'diferencia' => $diferencia === null ? null : Decimal::format($diferencia),
            'transito_salida' => $this->fmt($i->transito_salida),
            'transito_entrada' => $this->fmt($i->transito_entrada),
            'estado' => $i->estado,
            'no_esperado' => (bool) $i->no_esperado,
            'comentario' => $i->comentario,
        ];
        if ($costos) {
            $costo = AuditoriaConciliacion::units($i->costo_unitario);
            $row['costo_unitario'] = Decimal::format($costo);
            $row['valor_diferencia'] = $diferencia === null ? null : Decimal::format(self::multiplicar($diferencia, $costo));
        }

        return $row;
    }

    /** KPIs: exactitud = renglones conciliados sin diferencia / renglones conciliados. */
    public function totales(Collection $rows, bool $costos): array
    {
        $conciliados = $rows->filter(fn ($r) => $r['diferencia'] !== null);
        $difs = $conciliados->map(fn ($r) => AuditoriaConciliacion::units($r['diferencia']));
        $exactos = $difs->filter(fn ($d) => $d === 0)->count();
        $out = [
            'renglones' => $rows->count(),
            'contados' => $rows->filter(fn ($r) => $r['contado'] !== null)->count(),
            'conciliados' => $conciliados->count(),
            'exactos' => $exactos,
            'exactitud' => $conciliados->count() ? round($exactos * 100 / $conciliados->count(), 1) : null,
            'unidades_faltantes' => Decimal::format(-$difs->filter(fn ($d) => $d < 0)->sum()),
            'unidades_sobrantes' => Decimal::format($difs->filter(fn ($d) => $d > 0)->sum()),
            'unidades_neto' => Decimal::format($difs->sum()),
        ];
        if ($costos) {
            $valores = $conciliados->map(fn ($r) => AuditoriaConciliacion::units($r['valor_diferencia'] ?? '0'));
            $out += [
                'valor_faltante' => Decimal::format(-$valores->filter(fn ($v) => $v < 0)->sum()),
                'valor_sobrante' => Decimal::format($valores->filter(fn ($v) => $v > 0)->sum()),
                'valor_neto' => Decimal::format($valores->sum()),
            ];
        }

        return $out;
    }

    /**
     * Histórico: auditorías visibles filtradas por empresa, sucursal, rango de fechas, marca y SKU.
     * Con marca o SKU, cada auditoría incluye los renglones que coinciden.
     */
    public function historico(User $user, array $filtros): array
    {
        $costos = $this->access->verCostos($user);
        $sku = trim((string) ($filtros['sku'] ?? ''));
        $porRenglon = ! empty($filtros['marca']) || $sku !== '';
        $auditorias = $this->access->query($user)->with(['empresa:id,nombre', 'sucursal:id,nombre,es_bodega'])
            ->whereIn('status', [Auditoria::STATUS_FINALIZADA, Auditoria::STATUS_EN_REVISION])
            ->when(! empty($filtros['empresa']), fn ($q) => $q->where('id_empresa', (int) $filtros['empresa']))
            ->when(! empty($filtros['sucursal']), fn ($q) => $q->where('id_sucursal', (int) $filtros['sucursal']))
            ->when(! empty($filtros['desde']), fn ($q) => $q->whereDate('iniciada_at', '>=', $filtros['desde']))
            ->when(! empty($filtros['hasta']), fn ($q) => $q->whereDate('iniciada_at', '<=', $filtros['hasta']))
            ->when($porRenglon, fn ($q) => $q->whereHas('items', fn ($i) => $this->filtroItems($i, $filtros, $sku)))
            ->orderByDesc('iniciada_at')->limit(200)->get();

        return $auditorias->map(function (Auditoria $a) use ($costos, $filtros, $sku, $porRenglon) {
            $query = $a->items();
            if ($porRenglon) {
                $this->filtroItems($query, $filtros, $sku);
            }
            $rows = $query->orderBy('marca_nombre')->orderBy('nombre')->get()->map(fn ($i) => $this->renglon($i, $costos));

            return [
                'id' => $a->id,
                'folio' => $a->folio,
                'status' => $a->status,
                'status_label' => Auditoria::ETIQUETAS[$a->status] ?? $a->status,
                'empresa' => $a->empresa?->nombre,
                'sucursal' => $a->sucursal?->nombre,
                'es_bodega' => (bool) $a->sucursal?->es_bodega,
                'fecha' => $a->iniciada_at?->toIso8601String(),
                'totales' => $this->totales($rows, $costos),
                'renglones' => $porRenglon ? $rows->values()->all() : [],
            ];
        })->all();
    }

    private function filtroItems($query, array $filtros, string $sku)
    {
        return $query->when(! empty($filtros['marca']), fn ($q) => $q->where('id_marca', (int) $filtros['marca']))
            ->when($sku !== '', fn ($q) => $q->where(fn ($w) => $w->where('barcode', $sku)
                ->orWhere(fn ($x) => ctype_digit($sku) ? $x->where('id_producto', (int) $sku) : $x->whereRaw('1 = 0'))));
    }

    private function alcanceTexto(Auditoria $auditoria): string
    {
        $f = $auditoria->alcance_filtros ?? [];

        return match ($auditoria->alcance_tipo) {
            Auditoria::ALCANCE_MARCAS => 'Marcas: '.\App\Models\CatMarca::whereIn('id', $f['marcas'] ?? [])->orderBy('nombre')->pluck('nombre')->implode(', '),
            Auditoria::ALCANCE_CLASIFICACIONES => 'Clasificaciones: '.\App\Models\CatClasificacion::whereIn('id', $f['clasificaciones'] ?? [])->orderBy('nombre')->pluck('nombre')->implode(', '),
            Auditoria::ALCANCE_PRODUCTOS => count($f['productos'] ?? []).' producto(s) seleccionados',
            default => 'Inventario total',
        };
    }

    /** Producto de dos cantidades ×100 con redondeo simétrico (Decimal::roundRatio trunca negativos hacia cero). */
    public static function multiplicar(int $a, int $b): int
    {
        $producto = $a * $b;
        $redondeado = intdiv(abs($producto) + 50, 100);

        return $producto < 0 ? -$redondeado : $redondeado;
    }

    private function fmt(mixed $value): string
    {
        return Decimal::format(AuditoriaConciliacion::units($value));
    }
}
