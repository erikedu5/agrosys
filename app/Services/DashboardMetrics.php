<?php

namespace App\Services;

use App\Models\AltaInventario;
use App\Models\Clientes;
use App\Models\Producto;
use App\Models\ProductoVenta;
use App\Models\Sucursales;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Métricas de la pantalla de inicio, según el perfil del usuario.
 *
 * Las agrupaciones se hacen en PHP sobre filas ya filtradas por sucursal y
 * periodo para no depender de funciones de fecha específicas de MySQL.
 */
class DashboardMetrics
{
    public const PERIODOS = [7, 30, 90];

    /**
     * vendedor e inventario ven su sucursal; admin ve su sucursal con
     * desglose por vendedor; adminEmpresa y superAdmin ven toda la empresa.
     */
    public static function perfilDe(User $user): string
    {
        return match ($user->tipo) {
            'adminEmpresa', 'superAdmin' => 'empresa',
            'admin' => 'admin',
            'inventario' => 'inventario',
            default => 'vendedor',
        };
    }

    public function __construct(
        private User $user,
        private int $sucursalId,
        private ?int $empresaId,
        private int $periodo = 30,
    ) {
        if (!in_array($this->periodo, self::PERIODOS, true)) {
            $this->periodo = 30;
        }
    }

    public function build(): array
    {
        $perfil = self::perfilDe($this->user);

        return match ($perfil) {
            'empresa' => $this->paraAdministrador(true),
            'admin' => $this->paraAdministrador(false),
            'inventario' => $this->paraInventario(),
            default => $this->paraVendedor(),
        } + ['perfil' => $perfil, 'periodo' => $this->periodo, 'generado' => now()->toIso8601String()];
    }

    // -----------------------------------------------------------------
    // Perfiles
    // -----------------------------------------------------------------

    private function paraVendedor(): array
    {
        $hoy = $this->ventas([$this->sucursalId], now()->startOfDay(), now());
        $mias = $hoy->where('id_usuario', $this->user->id);
        $cobertura = $this->cobertura($this->sucursalId);

        return [
            'resumen' => [
                'misVentasHoy' => round($mias->sum('monto'), 2),
                'misTicketsHoy' => $mias->count(),
                'ventasSucursalHoy' => round($hoy->sum('monto'), 2),
                'ticketPromedioHoy' => $hoy->count() ? round($hoy->avg('monto'), 2) : 0,
            ],
            'ventasPorHora' => $this->porHora($hoy),
            'topProductos' => $this->topProductos([$this->sucursalId], now()->subDays(6)->startOfDay(), now(), 6),
            'porAgotarse' => $cobertura['lista']->take(6)->values(),
        ];
    }

    private function paraInventario(): array
    {
        $cobertura = $this->cobertura($this->sucursalId);
        [$desde, $hasta] = $this->rango();

        return [
            'resumen' => [
                'agotados' => $cobertura['agotados'],
                'porAgotarse' => $cobertura['porAgotarse'],
                'conExistencia' => $cobertura['conExistencia'],
                'unidadesVendidas' => round($this->unidadesVendidas([$this->sucursalId], $desde, $hasta), 2),
            ],
            'porAgotarse' => $cobertura['lista']->take(12)->values(),
            'topProductos' => $this->topProductos([$this->sucursalId], $desde, $hasta, 8, 'unidades'),
        ];
    }

    private function paraAdministrador(bool $empresa): array
    {
        $sucursales = $empresa && $this->empresaId
            ? Sucursales::where('id_empresa', $this->empresaId)->orderBy('nombre')->get(['id', 'nombre'])
            : Sucursales::whereKey($this->sucursalId)->get(['id', 'nombre']);
        $ids = $sucursales->pluck('id')->all();

        [$desde, $hasta] = $this->rango();
        [$desdeAnterior, $hastaAnterior] = [$desde->copy()->subDays($this->periodo), $desde->copy()->subSecond()];

        $actual = $this->ventas($ids, $desde, $hasta);
        $anterior = $this->ventas($ids, $desdeAnterior, $hastaAnterior);
        $porCobrar = Clientes::whereIn('id_sucursal', $ids)->where('balance', '>', 0);

        $datos = [
            'resumen' => [
                'ventas' => round($actual->sum('monto'), 2),
                'ventasAnterior' => round($anterior->sum('monto'), 2),
                'tickets' => $actual->count(),
                'ticketsAnterior' => $anterior->count(),
                'ticketPromedio' => $actual->count() ? round($actual->avg('monto'), 2) : 0,
                'ticketPromedioAnterior' => $anterior->count() ? round($anterior->avg('monto'), 2) : 0,
                'porCobrar' => round((float) (clone $porCobrar)->sum('balance'), 2),
                'clientesConAdeudo' => (clone $porCobrar)->count(),
            ],
            'serie' => $this->serie($actual, $desde, $hasta),
            'porVendedor' => $this->porVendedor($actual),
            'topProductos' => $this->topProductos($ids, $desde, $hasta, 8),
            'porAgotarse' => $this->cobertura($this->sucursalId)['lista']->take(6)->values(),
            'clientesAdeudo' => (clone $porCobrar)->orderByDesc('balance')->limit(6)->get(['nombre', 'balance'])
                ->map(fn ($c) => ['nombre' => $c->nombre, 'total' => round((float) $c->balance, 2)])->values(),
        ];

        if ($empresa) {
            $porSucursal = $actual->groupBy('id_sucursal')->map->sum('monto');
            $datos['porSucursal'] = $sucursales
                ->map(fn ($s) => ['nombre' => $s->nombre, 'total' => round((float) ($porSucursal[$s->id] ?? 0), 2)])
                ->sortByDesc('total')->values();
        }

        return $datos;
    }

    // -----------------------------------------------------------------
    // Consultas
    // -----------------------------------------------------------------

    private function rango(): array
    {
        return [now()->subDays($this->periodo - 1)->startOfDay(), now()];
    }

    private function ventas(array $sucursales, Carbon $desde, Carbon $hasta): Collection
    {
        return Venta::whereIn('id_sucursal', $sucursales)
            ->whereBetween('created_at', [$desde, $hasta])
            ->get(['id', 'total', 'tipo_venta', 'id_usuario', 'id_sucursal', 'created_at'])
            ->map(function ($venta) {
                $venta->monto = (float) $venta->total;
                $venta->credito = strtolower((string) $venta->tipo_venta) === 'credito';
                return $venta;
            });
    }

    /** Ventas por día (o por semana en periodos largos), separadas en contado y crédito. */
    private function serie(Collection $ventas, Carbon $desde, Carbon $hasta): array
    {
        $semanal = $this->periodo > 31;
        $clave = fn (Carbon $fecha) => $semanal ? $fecha->copy()->startOfWeek()->toDateString() : $fecha->toDateString();

        $cubetas = [];
        for ($dia = $desde->copy(); $dia->lte($hasta); $dia->addDay()) {
            $cubetas[$clave($dia)] ??= ['fecha' => $clave($dia), 'contado' => 0.0, 'credito' => 0.0, 'tickets' => 0];
        }

        foreach ($ventas as $venta) {
            $k = $clave(Carbon::parse($venta->created_at));
            if (!isset($cubetas[$k])) {
                continue;
            }
            $cubetas[$k][$venta->credito ? 'credito' : 'contado'] += $venta->monto;
            $cubetas[$k]['tickets']++;
        }

        return [
            'agrupacion' => $semanal ? 'semana' : 'dia',
            'puntos' => array_values(array_map(fn ($c) => [
                ...$c,
                'contado' => round($c['contado'], 2),
                'credito' => round($c['credito'], 2),
            ], $cubetas)),
        ];
    }

    private function porHora(Collection $ventas): array
    {
        $porHora = $ventas->groupBy(fn ($v) => (int) Carbon::parse($v->created_at)->format('G'));
        $horas = $porHora->keys()->push(8, 19);
        $resultado = [];
        for ($h = $horas->min(); $h <= $horas->max(); $h++) {
            $grupo = $porHora->get($h, collect());
            $resultado[] = ['hora' => $h, 'total' => round($grupo->sum('monto'), 2), 'tickets' => $grupo->count()];
        }

        return $resultado;
    }

    private function porVendedor(Collection $ventas): Collection
    {
        $nombres = User::whereIn('id', $ventas->pluck('id_usuario')->unique())->pluck('name', 'id');

        return $ventas->groupBy('id_usuario')
            ->map(fn ($grupo, $id) => [
                'nombre' => $nombres[$id] ?? 'Usuario ' . $id,
                'total' => round($grupo->sum('monto'), 2),
                'tickets' => $grupo->count(),
            ])
            ->sortByDesc('total')->take(8)->values();
    }

    private function lineasVendidas(array $sucursales, Carbon $desde, Carbon $hasta): Collection
    {
        return ProductoVenta::query()
            ->join('ventas', 'ventas.id', '=', 'producto_ventas.id_venta')
            ->whereIn('ventas.id_sucursal', $sucursales)
            ->whereBetween('ventas.created_at', [$desde, $hasta])
            ->get(['producto_ventas.id_producto', 'producto_ventas.cantidad', 'producto_ventas.total_productos']);
    }

    private function unidadesVendidas(array $sucursales, Carbon $desde, Carbon $hasta): float
    {
        return (float) $this->lineasVendidas($sucursales, $desde, $hasta)->sum('cantidad');
    }

    private function topProductos(array $sucursales, Carbon $desde, Carbon $hasta, int $limite, string $orden = 'importe'): Collection
    {
        $agrupado = $this->lineasVendidas($sucursales, $desde, $hasta)
            ->groupBy('id_producto')
            ->map(fn ($lineas, $id) => [
                'id' => (int) $id,
                'unidades' => round($lineas->sum(fn ($l) => (float) $l->cantidad), 2),
                'importe' => round($lineas->sum(fn ($l) => (float) $l->total_productos), 2),
            ])
            ->sortByDesc($orden)->take($limite);

        $productos = Producto::withTrashed()->whereIn('id', $agrupado->keys())->get(['id', 'nombre', 'tamano'])->keyBy('id');

        return $agrupado->map(fn ($fila) => [
            'nombre' => $productos[$fila['id']]?->nombre ?? 'Producto ' . $fila['id'],
            'tamano' => $productos[$fila['id']]?->tamano,
            ...$fila,
        ])->values();
    }

    /**
     * Días de cobertura = existencia / venta diaria promedio de los últimos 30 días.
     * Solo se listan productos que se han vendido en ese lapso.
     */
    private function cobertura(int $sucursalId): array
    {
        $ultimas = AltaInventario::whereIn('id', function ($q) use ($sucursalId) {
            $q->selectRaw('MAX(id)')->from('alta_inventarios')->where('id_sucursal', $sucursalId)->groupBy('id_producto');
        })->get(['id_producto', 'cantidad_nueva'])->mapWithKeys(fn ($a) => [(int) $a->id_producto => (float) $a->cantidad_nueva]);

        $vendidas = $this->lineasVendidas([$sucursalId], now()->subDays(29)->startOfDay(), now())
            ->groupBy('id_producto')
            ->map(fn ($lineas) => $lineas->sum(fn ($l) => (float) $l->cantidad));

        $productos = Producto::whereIn('id', $vendidas->keys())->get(['id', 'nombre', 'tamano'])->keyBy('id');

        $lista = $vendidas
            ->filter(fn ($unidades, $id) => isset($productos[$id]))
            ->map(function ($unidades, $id) use ($ultimas, $productos) {
                $existencia = max(0, $ultimas[$id] ?? 0);
                $diaria = $unidades / 30;
                return [
                    'nombre' => $productos[$id]->nombre,
                    'tamano' => $productos[$id]->tamano,
                    'existencia' => round($existencia, 2),
                    'ventaDiaria' => round($diaria, 2),
                    'dias' => $diaria > 0 ? round($existencia / $diaria, 1) : null,
                ];
            })
            ->filter(fn ($p) => $p['dias'] !== null && $p['dias'] < 14)
            ->sortBy('dias')
            ->values();

        return [
            'lista' => $lista,
            'agotados' => $lista->where('existencia', '<=', 0)->count(),
            'porAgotarse' => $lista->where('existencia', '>', 0)->where('dias', '<', 7)->count(),
            'conExistencia' => $ultimas->filter(fn ($c) => $c > 0)->count(),
        ];
    }
}
