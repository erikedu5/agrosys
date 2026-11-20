<?php

namespace App\Http\Controllers;

use App\Exports\AuditoriaExport;
use App\Exports\AuditoriaHistoricoExport;
use App\Models\Auditoria;
use App\Models\AuditoriaConteo;
use App\Models\AuditoriaItem;
use App\Models\CatClasificacion;
use App\Models\CatMarca;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\User;
use App\Services\Auditoria\AuditoriaAccess;
use App\Services\Auditoria\AuditoriaConciliacion;
use App\Services\Auditoria\AuditoriaManagement;
use App\Services\Auditoria\AuditoriaReport;
use App\Services\EventLogger;
use App\Services\Pos\Decimal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class AuditoriaController extends Controller
{
    public function __construct(
        private AuditoriaAccess $access,
        private AuditoriaManagement $management,
        private AuditoriaReport $report,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->query('status');
        $auditorias = $this->access->query($user)
            ->with(['empresa:id,nombre', 'sucursal:id,nombre,es_bodega', 'auditores:id,name'])
            ->withCount(['items', 'items as contados_count' => fn ($q) => $q->whereNotNull('cantidad_contada')])
            ->when(in_array($status, array_keys(Auditoria::ETIQUETAS), true), fn ($q) => $q->where('status', $status))
            ->latest('id')->paginate(12)->withQueryString();

        return Inertia::render('Auditoria/Index', [
            'auditorias' => $auditorias,
            'status' => $status,
            'etiquetas' => Auditoria::ETIQUETAS,
            'puedeCrear' => $user->isAuditor() || $user->isSuperAdmin(),
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isAuditor() || $user->isSuperAdmin(), 403, 'Sólo un auditor puede abrir auditorías.');

        return Inertia::render('Auditoria/Create', $this->formProps($user));
    }

    public function store(Request $request)
    {
        $logger = EventLogger::start('CREAR_AUDITORIA', $request->only(['id_empresa', 'id_sucursal', 'alcance_tipo']));
        try {
            $auditoria = $this->management->crear($request->user(), $request->all());
            $logger->success(['auditoria_id' => $auditoria->id, 'folio' => $auditoria->folio]);

            return redirect()->route('auditorias.show', $auditoria)->with('success', "Auditoría {$auditoria->folio} creada en borrador.");
        } catch (Throwable $e) {
            $logger->error($e);
            throw $e;
        }
    }

    public function edit(Request $request, Auditoria $auditoria)
    {
        $user = $request->user();
        $this->access->autorizarOperar($user, $auditoria);
        abort_unless($auditoria->status === Auditoria::STATUS_BORRADOR, 409, 'Sólo se puede editar una auditoría en borrador.');
        $auditoria->load('auditores:id');

        return Inertia::render('Auditoria/Create', $this->formProps($user) + [
            'auditoria' => [
                'id' => $auditoria->id,
                'folio' => $auditoria->folio,
                'id_empresa' => $auditoria->id_empresa,
                'id_sucursal' => $auditoria->id_sucursal,
                'alcance_tipo' => $auditoria->alcance_tipo,
                'alcance_filtros' => $auditoria->alcance_filtros ?? [],
                'auditores' => $auditoria->auditores->pluck('id')->all(),
                'notas' => $auditoria->notas,
            ],
        ]);
    }

    public function update(Request $request, Auditoria $auditoria)
    {
        $this->management->actualizar($request->user(), $auditoria, $request->all());

        return redirect()->route('auditorias.show', $auditoria)->with('success', 'Auditoría actualizada.');
    }

    public function show(Request $request, Auditoria $auditoria)
    {
        $user = $request->user();
        $this->access->autorizarVer($user, $auditoria);
        $auditoria->load(['empresa:id,nombre', 'sucursal:id,nombre,es_bodega', 'auditores:id,name,email', 'creador:id,name']);
        $ciego = $this->esCiego($auditoria);
        $filtros = $request->only(['marca', 'sku', 'solo_diferencias']);

        return Inertia::render('Auditoria/Show', [
            'auditoria' => $this->resumenAuditoria($auditoria),
            'permisos' => [
                'operar' => $this->access->puedeOperar($user, $auditoria),
                'cancelar' => $this->access->puedeCancelar($user, $auditoria) && $auditoria->esActiva(),
                'ver_costos' => $this->access->verCostos($user),
            ],
            'progreso' => $this->progreso($auditoria),
            'transferenciasPendientes' => $auditoria->status === Auditoria::STATUS_BORRADOR ? $this->management->transferenciasPendientes($auditoria) : 0,
            'reporte' => $ciego ? null : $this->report->auditoria($user, $auditoria, $filtros),
            'filtros' => $filtros,
            'marcas' => $ciego ? [] : $auditoria->items()->whereNotNull('id_marca')->select('id_marca as id', 'marca_nombre as nombre')
                ->distinct()->orderBy('nombre')->get(),
        ]);
    }

    public function captura(Request $request, Auditoria $auditoria)
    {
        $user = $request->user();
        $this->access->autorizarOperar($user, $auditoria);
        if (! in_array($auditoria->status, [Auditoria::STATUS_EN_CONTEO, Auditoria::STATUS_EN_REVISION], true)) {
            return redirect()->route('auditorias.show', $auditoria);
        }
        $auditoria->load(['sucursal:id,nombre,es_bodega', 'empresa:id,nombre']);

        return Inertia::render('Auditoria/Captura', [
            'auditoria' => $this->resumenAuditoria($auditoria),
            'progreso' => $this->progreso($auditoria),
            'capturas' => $this->capturasRecientes($auditoria, $user),
            'reconteo' => $auditoria->status === Auditoria::STATUS_EN_REVISION
                ? $auditoria->items()->where('ronda', 2)->orderBy('nombre')->get()->map(fn ($i) => $this->itemCaptura($i))->all()
                : [],
        ]);
    }

    /** Búsqueda para captura: nunca expone existencias del sistema. */
    public function buscarProducto(Request $request, Auditoria $auditoria)
    {
        $this->access->autorizarOperar($request->user(), $auditoria);
        $term = trim((string) $request->query('q', ''));
        if ($term === '') {
            return response()->json(['data' => []]);
        }
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
        $productos = Producto::withoutGlobalScope('empresa')->where('productos.id_empresa', $auditoria->id_empresa)
            ->leftJoin('cat_marcas', 'cat_marcas.id', '=', 'productos.id_marca')
            ->where(function ($q) use ($term, $like) {
                $q->where('productos.barcode', $term)->orWhere('productos.nombre', 'like', $like)->orWhere('productos.barcode', 'like', $like);
                if (ctype_digit($term)) {
                    $q->orWhere('productos.id', (int) $term);
                }
            })
            ->orderByRaw('CASE WHEN productos.barcode = ? THEN 0 ELSE 1 END', [$term])->orderBy('productos.nombre')
            ->limit(20)->get(['productos.id', 'productos.nombre', 'productos.tamano', 'productos.barcode', 'cat_marcas.nombre as marca']);
        $items = $auditoria->items()->whereIn('id_producto', $productos->pluck('id'))->get()->keyBy('id_producto');

        return response()->json(['data' => $productos->map(function ($p) use ($items, $term) {
            $item = $items->get($p->id);

            return [
                'id_producto' => $p->id,
                'id_item' => $item?->id,
                'nombre' => $p->nombre,
                'tamano' => $p->tamano,
                'barcode' => $p->barcode,
                'marca' => $p->marca,
                'en_alcance' => $item !== null && ! $item->no_esperado,
                'contado' => $item?->cantidad_contada === null ? null : Decimal::format(AuditoriaConciliacion::units($item->cantidad_contada)),
                'ronda' => $item?->ronda ?? 1,
                'exacto' => trim((string) $p->barcode) !== '' && $p->barcode === $term,
            ];
        })->values()]);
    }

    public function capturar(Request $request, Auditoria $auditoria)
    {
        $user = $request->user();
        $item = $this->management->capturar($user, $auditoria, $request->all());

        return response()->json($this->respuestaCaptura($auditoria->refresh(), $user, $item));
    }

    public function anular(Request $request, Auditoria $auditoria, int $conteo)
    {
        $user = $request->user();
        $item = $this->management->anular($user, $auditoria, $conteo);

        return response()->json($this->respuestaCaptura($auditoria->refresh(), $user, $item));
    }

    public function iniciar(Request $request, Auditoria $auditoria)
    {
        return $this->transicion('INICIAR_AUDITORIA', $auditoria, fn () => $this->management->iniciar($request->user(), $auditoria, $request->boolean('confirmar_transito')),
            'Auditoría iniciada: movimientos distintos a ventas bloqueados en la sucursal.', 'auditorias.captura');
    }

    public function cerrarConteo(Request $request, Auditoria $auditoria)
    {
        return $this->transicion('CERRAR_CONTEO_AUDITORIA', $auditoria, fn () => $this->management->cerrarConteo($request->user(), $auditoria, $request->boolean('no_contados_cero')),
            'Conteo cerrado. Revisa las diferencias y solicita reconteo si es necesario.');
    }

    public function reconteo(Request $request, Auditoria $auditoria)
    {
        return $this->transicion('RECONTEO_AUDITORIA', $auditoria, fn () => $this->management->solicitarReconteo($request->user(), $auditoria, (array) $request->input('items', [])),
            'Productos marcados para reconteo.', 'auditorias.captura');
    }

    public function finalizar(Request $request, Auditoria $auditoria)
    {
        return $this->transicion('FINALIZAR_AUDITORIA', $auditoria, fn () => $this->management->finalizar($request->user(), $auditoria),
            'Auditoría finalizada. La sucursal ya puede mover inventario.');
    }

    public function cancelar(Request $request, Auditoria $auditoria)
    {
        return $this->transicion('CANCELAR_AUDITORIA', $auditoria, fn () => $this->management->cancelar($request->user(), $auditoria, $request->input('motivo')),
            'Auditoría cancelada.');
    }

    public function comentario(Request $request, Auditoria $auditoria, AuditoriaItem $item)
    {
        $this->access->autorizarOperar($request->user(), $auditoria);
        abort_unless((int) $item->id_auditoria === (int) $auditoria->id, 404);
        abort_unless($auditoria->esActiva(), 409, 'La auditoría ya está cerrada.');
        $data = $request->validate(['comentario' => 'nullable|string|max:1000']);
        $item->update(['comentario' => $data['comentario'] ?? null]);

        return back()->with('success', 'Comentario guardado.');
    }

    public function reporteXlsx(Request $request, Auditoria $auditoria)
    {
        $user = $request->user();
        $this->access->autorizarVer($user, $auditoria);
        abort_if($this->esCiego($auditoria), 409, 'El reporte estará disponible al cerrar el conteo.');
        $data = $this->report->auditoria($user, $auditoria, $request->only(['marca', 'sku', 'solo_diferencias']));

        return Excel::download(new AuditoriaExport($data), "auditoria-{$auditoria->folio}.xlsx");
    }

    public function historico(Request $request)
    {
        $user = $request->user();
        $filtros = $request->only(['empresa', 'sucursal', 'desde', 'hasta', 'marca', 'sku']);
        $empresas = $user->isAdminEmpresa()
            ? \App\Models\Empresa::whereKey($user->id_empresa)->get(['id', 'nombre'])
            : $this->access->empresasDisponibles($user);
        $empresaIds = $empresas->pluck('id')->all();
        $sucursales = Sucursales::withTrashed()->whereIn('id_empresa', $empresaIds ?: [0])
            ->when($user->isAuditor(), fn ($q) => $q->whereIn('id', $this->access->sucursalIdsCubiertas($user) ?: [0]))
            ->orderBy('nombre')->get(['id', 'nombre', 'id_empresa', 'es_bodega']);

        if ($request->query('formato') === 'xlsx') {
            return Excel::download(new AuditoriaHistoricoExport($this->report->historico($user, $filtros), $this->access->verCostos($user)),
                'auditorias-historico.xlsx');
        }

        return Inertia::render('Auditoria/Historico', [
            'filtros' => $filtros,
            'empresas' => $empresas,
            'sucursales' => $sucursales,
            'marcas' => CatMarca::orderBy('nombre')->get(['id', 'nombre']),
            'resultados' => $request->hasAny(['empresa', 'sucursal', 'desde', 'hasta', 'marca', 'sku', 'buscar'])
                ? $this->report->historico($user, $filtros) : null,
            'verCostos' => $this->access->verCostos($user),
        ]);
    }

    /** JSON: sucursales de la empresa dentro del alcance, marcando las que ya tienen auditoría activa. */
    public function sucursalesAlcance(Request $request)
    {
        $user = $request->user();
        $empresaId = (int) $request->query('empresa');
        abort_unless($this->access->empresasDisponibles($user)->contains('id', $empresaId), 403, 'Empresa fuera de tu alcance.');
        $sucursales = $this->access->sucursalesDisponibles($user, $empresaId);
        $activas = Auditoria::whereIn('id_sucursal', $sucursales->pluck('id'))->whereIn('status', Auditoria::ACTIVAS)
            ->pluck('folio', 'id_sucursal');

        return response()->json(['data' => $sucursales->map(fn ($s) => [
            'id' => $s->id, 'nombre' => $s->nombre, 'es_bodega' => (bool) $s->es_bodega,
            'auditoria_activa' => $activas[$s->id] ?? null,
        ])->values()]);
    }

    /** JSON: auditores con alcance vigente sobre la sucursal. */
    public function auditoresDisponibles(Request $request)
    {
        $user = $request->user();
        $sucursal = Sucursales::findOrFail((int) $request->query('sucursal'));
        abort_unless($user->isSuperAdmin() || $this->access->cubreSucursal($user, $sucursal), 403, 'Sucursal fuera de tu alcance.');

        return response()->json(['data' => $this->access->auditoresElegibles($sucursal)
            ->reject(fn (User $u) => $u->id === $user->id)->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])->values()]);
    }

    private function formProps(User $user): array
    {
        return [
            'empresas' => $this->access->empresasDisponibles($user),
            'marcas' => CatMarca::orderBy('nombre')->get(['id', 'nombre']),
            'clasificaciones' => CatClasificacion::orderBy('nombre')->get(['id', 'nombre']),
        ];
    }

    private function transicion(string $evento, Auditoria $auditoria, callable $accion, string $mensaje, ?string $destino = null)
    {
        $logger = EventLogger::start($evento, ['auditoria_id' => $auditoria->id, 'folio' => $auditoria->folio, 'sucursal_auditada' => $auditoria->id_sucursal]);
        try {
            $accion();
            $logger->success(['auditoria_id' => $auditoria->id]);
        } catch (Throwable $e) {
            $logger->error($e);
            throw $e;
        }

        return redirect()->route($destino ?? 'auditorias.show', $auditoria)->with('success', $mensaje);
    }

    private function esCiego(Auditoria $auditoria): bool
    {
        return in_array($auditoria->status, [Auditoria::STATUS_BORRADOR, Auditoria::STATUS_EN_CONTEO], true)
            || ($auditoria->status === Auditoria::STATUS_CANCELADA && ! $auditoria->conteo_cerrado_at);
    }

    private function resumenAuditoria(Auditoria $auditoria): array
    {
        return [
            'id' => $auditoria->id,
            'folio' => $auditoria->folio,
            'status' => $auditoria->status,
            'status_label' => Auditoria::ETIQUETAS[$auditoria->status] ?? $auditoria->status,
            'empresa' => $auditoria->empresa?->nombre,
            'sucursal' => $auditoria->sucursal?->nombre,
            'es_bodega' => (bool) $auditoria->sucursal?->es_bodega,
            'alcance_tipo' => $auditoria->alcance_tipo,
            'auditores' => $auditoria->relationLoaded('auditores') ? $auditoria->auditores->pluck('name')->all() : [],
            'creador' => $auditoria->relationLoaded('creador') ? $auditoria->creador?->name : null,
            'notas' => $auditoria->notas,
            'motivo_cancelacion' => $auditoria->motivo_cancelacion,
            'created_at' => $auditoria->created_at?->toIso8601String(),
            'iniciada_at' => $auditoria->iniciada_at?->toIso8601String(),
            'conteo_cerrado_at' => $auditoria->conteo_cerrado_at?->toIso8601String(),
            'finalizada_at' => $auditoria->finalizada_at?->toIso8601String(),
            'cancelada_at' => $auditoria->cancelada_at?->toIso8601String(),
        ];
    }

    private function progreso(Auditoria $auditoria): array
    {
        $base = $auditoria->items();

        return [
            'total' => (clone $base)->where('no_esperado', false)->count(),
            'contados' => (clone $base)->where('no_esperado', false)->whereNotNull('cantidad_contada')->count(),
            'no_esperados' => (clone $base)->where('no_esperado', true)->count(),
            'reconteo_pendiente' => (clone $base)->where('ronda', 2)->where('estado', AuditoriaItem::ESTADO_RECONTEO)->count(),
        ];
    }

    private function itemCaptura(AuditoriaItem $i): array
    {
        return [
            'id' => $i->id,
            'id_producto' => $i->id_producto,
            'sku' => $i->sku,
            'nombre' => $i->nombre,
            'tamano' => $i->tamano,
            'marca' => $i->marca_nombre,
            'contado' => $i->cantidad_contada === null ? null : Decimal::format(AuditoriaConciliacion::units($i->cantidad_contada)),
            'contado_r1' => $i->contado_r1 === null ? null : Decimal::format(AuditoriaConciliacion::units($i->contado_r1)),
            'ronda' => (int) $i->ronda,
            'estado' => $i->estado,
            'no_esperado' => (bool) $i->no_esperado,
        ];
    }

    private function capturasRecientes(Auditoria $auditoria, User $user): array
    {
        return AuditoriaConteo::with('item:id,nombre,tamano,barcode,id_producto')
            ->whereHas('item', fn ($q) => $q->where('id_auditoria', $auditoria->id))
            ->where('id_usuario', $user->id)->latest('id')->limit(50)->get()
            ->map(fn (AuditoriaConteo $c) => [
                'id' => $c->id,
                'id_item' => $c->id_auditoria_item,
                'nombre' => $c->item?->nombre,
                'tamano' => $c->item?->tamano,
                'sku' => $c->item?->sku,
                'cantidad' => Decimal::format(AuditoriaConciliacion::units($c->cantidad)),
                'ubicacion' => $c->ubicacion,
                'ronda' => (int) $c->ronda,
                'anulado' => $c->anulado_at !== null,
                'created_at' => $c->created_at?->toIso8601String(),
            ])->all();
    }

    private function respuestaCaptura(Auditoria $auditoria, User $user, AuditoriaItem $item): array
    {
        return [
            'item' => $this->itemCaptura($item),
            'progreso' => $this->progreso($auditoria),
            'capturas' => $this->capturasRecientes($auditoria, $user),
        ];
    }
}
