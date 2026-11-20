<?php

namespace App\Services\Auditoria;

use App\Models\Auditoria;
use App\Models\AuditoriaConteo;
use App\Models\AuditoriaItem;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\User;
use App\Services\Pos\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Ciclo de vida de la auditoría. El módulo sólo reporta: nunca escribe en alta_inventarios.
 */
class AuditoriaManagement
{
    public function __construct(
        private AuditoriaAccess $access,
        private AuditoriaConciliacion $conciliacion,
    ) {
    }

    public function crear(User $user, array $input): Auditoria
    {
        abort_unless($user->isAuditor() || $user->isSuperAdmin(), 403, 'Sólo un auditor puede abrir auditorías.');
        $empresas = $this->access->empresasDisponibles($user);
        // Con una sola empresa en alcance no se pide elegirla.
        if (empty($input['id_empresa']) && $empresas->count() === 1) {
            $input['id_empresa'] = $empresas->first()->id;
        }
        $data = $this->validarDatos($input, true);
        abort_unless($empresas->contains('id', (int) $data['id_empresa']), 403, 'Empresa fuera de tu alcance.');
        $sucursal = Sucursales::where('id_empresa', $data['id_empresa'])->find($data['id_sucursal']);
        if (! $sucursal) {
            throw ValidationException::withMessages(['id_sucursal' => 'La sucursal no pertenece a la empresa seleccionada.']);
        }
        abort_unless($user->isSuperAdmin() || $this->access->cubreSucursal($user, $sucursal), 403, 'Sucursal fuera de tu alcance.');
        $auditores = $this->auditoresValidos($sucursal, $data['auditores'] ?? [], $user->isAuditor() ? [$user->id] : []);

        return DB::transaction(function () use ($user, $data, $sucursal, $auditores) {
            Sucursales::whereKey($sucursal->id)->lockForUpdate()->first();
            $activa = Auditoria::where('id_sucursal', $sucursal->id)->whereIn('status', Auditoria::ACTIVAS)->first();
            if ($activa) {
                throw ValidationException::withMessages(['id_sucursal' => "La sucursal ya tiene una auditoría activa (folio {$activa->folio})."]);
            }
            $auditoria = Auditoria::create([
                'folio' => $this->siguienteFolio((int) $sucursal->id_empresa),
                'id_empresa' => $sucursal->id_empresa,
                'id_sucursal' => $sucursal->id,
                'status' => Auditoria::STATUS_BORRADOR,
                'alcance_tipo' => $data['alcance_tipo'],
                'alcance_filtros' => $this->filtros($data),
                'conteo_ciego' => true,
                'id_usuario_crea' => $user->id,
                'notas' => $data['notas'] ?? null,
            ]);
            $auditoria->auditores()->sync($auditores);

            return $auditoria;
        }, 3);
    }

    public function actualizar(User $user, Auditoria $auditoria, array $input): Auditoria
    {
        $this->access->autorizarOperar($user, $auditoria);
        $this->exigirEstado($auditoria, [Auditoria::STATUS_BORRADOR], 'Sólo se puede editar una auditoría en borrador.');
        $data = $this->validarDatos($input, false);
        // Quien abrió la auditoría y quien edita (si es auditor) siempre permanecen.
        $siempre = array_filter([$auditoria->creador?->isAuditor() ? $auditoria->id_usuario_crea : null, $user->isAuditor() ? $user->id : null]);
        $auditores = $this->auditoresValidos($auditoria->sucursal, $data['auditores'] ?? [], $siempre);
        $auditoria->fill(['alcance_tipo' => $data['alcance_tipo'], 'alcance_filtros' => $this->filtros($data),
            'notas' => $data['notas'] ?? null])->save();
        $auditoria->auditores()->sync($auditores);

        return $auditoria;
    }

    /** Transferencias pendientes que involucran la sucursal (stock en tránsito). */
    public function transferenciasPendientes(Auditoria $auditoria): int
    {
        return DB::table('transferencias')->where('status', 'pendiente')
            ->where(fn ($q) => $q->where('id_sucursal_origen', $auditoria->id_sucursal)->orWhere('id_sucursal_destino', $auditoria->id_sucursal))
            ->count();
    }

    public function iniciar(User $user, Auditoria $auditoria, bool $confirmaTransito = false): Auditoria
    {
        $this->access->autorizarOperar($user, $auditoria);
        $pendientes = $this->transferenciasPendientes($auditoria);
        if ($pendientes > 0 && ! $confirmaTransito) {
            throw ValidationException::withMessages(['transito' => "Hay {$pendientes} transferencia(s) pendiente(s) desde o hacia esta sucursal. Se recomienda recibirlas antes de iniciar; confirma para continuar."]);
        }

        return DB::transaction(function () use ($auditoria) {
            $auditoria = Auditoria::lockForUpdate()->findOrFail($auditoria->id);
            $this->exigirEstado($auditoria, [Auditoria::STATUS_BORRADOR], 'La auditoría ya fue iniciada.');
            $corte = AuditoriaConciliacion::corteActual();
            $productos = $this->productosDelAlcance($auditoria);
            if ($productos->isEmpty()) {
                throw ValidationException::withMessages(['alcance' => 'El alcance seleccionado no tiene productos.']);
            }
            $existencias = $this->conciliacion->existenciasAlCorte((int) $auditoria->id_sucursal, $corte);
            $now = now();
            foreach ($productos->chunk(500) as $chunk) {
                AuditoriaItem::insert($chunk->map(fn ($p) => $this->snapshot($auditoria, $p, $existencias[$p->id] ?? 0) + [
                    'created_at' => $now, 'updated_at' => $now,
                ])->values()->all());
            }
            $auditoria->fill(['status' => Auditoria::STATUS_EN_CONTEO, 'corte_inicio_id' => $corte, 'iniciada_at' => $now])->save();

            return $auditoria;
        }, 3);
    }

    /**
     * Registra una captura. En conteo acepta cualquier producto de la empresa (los fuera de
     * alcance quedan como "no esperados") o un producto no registrado. En revisión sólo
     * acepta renglones marcados para reconteo.
     */
    public function capturar(User $user, Auditoria $auditoria, array $input): AuditoriaItem
    {
        $this->access->autorizarOperar($user, $auditoria);
        $data = Validator::make($input, [
            'id_item' => 'nullable|integer',
            'id_producto' => 'nullable|integer',
            'descripcion_libre' => 'nullable|string|max:255',
            'cantidad' => 'required|numeric|min:0|max:999999.99',
            'ubicacion' => 'nullable|string|max:120',
        ], [], ['cantidad' => 'cantidad contada'])->validate();
        if (empty($data['id_item']) && empty($data['id_producto']) && blank($data['descripcion_libre'] ?? null)) {
            throw ValidationException::withMessages(['id_producto' => 'Selecciona un producto o describe el producto no registrado.']);
        }
        $cantidad = Decimal::units((string) $data['cantidad'], 'cantidad');

        return DB::transaction(function () use ($user, $auditoria, $data, $cantidad) {
            $auditoria = Auditoria::lockForUpdate()->findOrFail($auditoria->id);
            $this->exigirEstado($auditoria, [Auditoria::STATUS_EN_CONTEO, Auditoria::STATUS_EN_REVISION], 'La auditoría no está abierta a capturas.');
            $item = $this->resolverItem($auditoria, $data);
            if ($auditoria->status === Auditoria::STATUS_EN_REVISION && $item->ronda !== 2) {
                throw ValidationException::withMessages(['id_producto' => 'En revisión sólo se capturan productos marcados para reconteo.']);
            }
            AuditoriaConteo::create([
                'id_auditoria_item' => $item->id,
                'id_usuario' => $user->id,
                'ronda' => $item->ronda,
                'cantidad' => Decimal::format($cantidad),
                'ubicacion' => filled($data['ubicacion'] ?? null) ? trim($data['ubicacion']) : null,
                'corte_id' => AuditoriaConciliacion::corteActual(),
            ]);
            $item = $this->conciliacion->recalcularTotales($item);
            if ($auditoria->status === Auditoria::STATUS_EN_REVISION) {
                $this->conciliacion->conciliar($auditoria, collect([$item]));
            }

            return $item->refresh();
        }, 3);
    }

    public function anular(User $user, Auditoria $auditoria, int $conteoId): AuditoriaItem
    {
        $this->access->autorizarOperar($user, $auditoria);

        return DB::transaction(function () use ($user, $auditoria, $conteoId) {
            $auditoria = Auditoria::lockForUpdate()->findOrFail($auditoria->id);
            $conteo = AuditoriaConteo::whereHas('item', fn ($q) => $q->where('id_auditoria', $auditoria->id))
                ->lockForUpdate()->findOrFail($conteoId);
            $item = $conteo->item;
            $editable = ($auditoria->status === Auditoria::STATUS_EN_CONTEO && $conteo->ronda === 1)
                || ($auditoria->status === Auditoria::STATUS_EN_REVISION && $conteo->ronda === 2 && $item->ronda === 2);
            if (! $editable) {
                throw ValidationException::withMessages(['conteo' => 'Esta captura ya no se puede anular.']);
            }
            if (! $conteo->anulado_at) {
                $conteo->fill(['anulado_at' => now(), 'anulado_por' => $user->id])->save();
            }
            $item = $this->conciliacion->recalcularTotales($item);
            if ($auditoria->status === Auditoria::STATUS_EN_REVISION) {
                $this->conciliacion->conciliar($auditoria, collect([$item]));
            }

            return $item->refresh();
        }, 3);
    }

    public function cerrarConteo(User $user, Auditoria $auditoria, bool $noContadosEnCero = false): Auditoria
    {
        $this->access->autorizarOperar($user, $auditoria);

        return DB::transaction(function () use ($user, $auditoria, $noContadosEnCero) {
            $auditoria = Auditoria::lockForUpdate()->findOrFail($auditoria->id);
            $this->exigirEstado($auditoria, [Auditoria::STATUS_EN_CONTEO], 'La auditoría no está en conteo.');
            $pendientes = $auditoria->items()->where('estado', AuditoriaItem::ESTADO_PENDIENTE)->get();
            if ($pendientes->isNotEmpty() && ! $noContadosEnCero) {
                throw ValidationException::withMessages(['pendientes' => "Hay {$pendientes->count()} producto(s) sin contar. Confirma que se consideren en cero para cerrar el conteo."]);
            }
            $corte = AuditoriaConciliacion::corteActual();
            foreach ($pendientes as $item) {
                // Captura explícita en cero: deja rastro de quién cerró sin contar.
                AuditoriaConteo::create(['id_auditoria_item' => $item->id, 'id_usuario' => $user->id, 'ronda' => 1,
                    'cantidad' => '0.00', 'ubicacion' => 'No contado (cierre en cero)', 'corte_id' => $corte]);
                $this->conciliacion->recalcularTotales($item);
            }
            $this->conciliacion->conciliar($auditoria);
            $auditoria->fill(['status' => Auditoria::STATUS_EN_REVISION, 'conteo_cerrado_at' => now()])->save();

            return $auditoria;
        }, 3);
    }

    /** Marca renglones para reconteo (ronda 2). La ronda 1 se conserva para el reporte. */
    public function solicitarReconteo(User $user, Auditoria $auditoria, array $itemIds): int
    {
        $this->access->autorizarOperar($user, $auditoria);
        $ids = collect($itemIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Selecciona al menos un producto para reconteo.']);
        }

        return DB::transaction(function () use ($auditoria, $ids) {
            $auditoria = Auditoria::lockForUpdate()->findOrFail($auditoria->id);
            $this->exigirEstado($auditoria, [Auditoria::STATUS_EN_REVISION], 'El reconteo sólo aplica en revisión.');

            return $auditoria->items()->whereIn('id', $ids)->where('ronda', 1)
                ->update(['ronda' => 2, 'estado' => AuditoriaItem::ESTADO_RECONTEO, 'updated_at' => now()]);
        }, 3);
    }

    public function finalizar(User $user, Auditoria $auditoria): Auditoria
    {
        $this->access->autorizarOperar($user, $auditoria);

        return DB::transaction(function () use ($user, $auditoria) {
            $auditoria = Auditoria::lockForUpdate()->findOrFail($auditoria->id);
            $this->exigirEstado($auditoria, [Auditoria::STATUS_EN_REVISION], 'Sólo se puede finalizar una auditoría en revisión.');
            // Reconteos solicitados y no realizados conservan la ronda 1.
            $sinReconteo = $auditoria->items()->where('ronda', 2)->where('estado', AuditoriaItem::ESTADO_RECONTEO)->get();
            foreach ($sinReconteo as $item) {
                $item->ronda = 1;
                $this->conciliacion->recalcularTotales($item);
            }
            // Recalcular todo: incorpora ventas offline sincronizadas después del cierre del conteo.
            $this->conciliacion->conciliar($auditoria);
            $auditoria->items()->update(['estado' => AuditoriaItem::ESTADO_CONCILIADO]);
            $auditoria->fill(['status' => Auditoria::STATUS_FINALIZADA, 'finalizada_at' => now(), 'id_usuario_finaliza' => $user->id])->save();

            return $auditoria;
        }, 3);
    }

    public function cancelar(User $user, Auditoria $auditoria, ?string $motivo): Auditoria
    {
        $this->access->autorizarCancelar($user, $auditoria);
        Validator::make(['motivo' => $motivo], ['motivo' => 'required|string|min:5|max:2000'], [],
            ['motivo' => 'motivo de cancelación'])->validate();

        return DB::transaction(function () use ($user, $auditoria, $motivo) {
            $auditoria = Auditoria::lockForUpdate()->findOrFail($auditoria->id);
            $this->exigirEstado($auditoria, Auditoria::ACTIVAS, 'La auditoría ya está cerrada.');
            $auditoria->fill(['status' => Auditoria::STATUS_CANCELADA, 'cancelada_at' => now(),
                'motivo_cancelacion' => trim((string) $motivo), 'id_usuario_cancela' => $user->id])->save();

            return $auditoria;
        }, 3);
    }

    private function resolverItem(Auditoria $auditoria, array $data): AuditoriaItem
    {
        if (! empty($data['id_item'])) {
            return $auditoria->items()->lockForUpdate()->findOrFail($data['id_item']);
        }
        if (! empty($data['id_producto'])) {
            $item = $auditoria->items()->where('id_producto', $data['id_producto'])->lockForUpdate()->first();
            if ($item) {
                return $item;
            }
            if ($auditoria->status !== Auditoria::STATUS_EN_CONTEO) {
                throw ValidationException::withMessages(['id_producto' => 'El producto no forma parte de esta auditoría.']);
            }
            $producto = $this->productosQuery((int) $auditoria->id_empresa)->where('productos.id', $data['id_producto'])->first();
            if (! $producto) {
                throw ValidationException::withMessages(['id_producto' => 'El producto no pertenece a la empresa auditada.']);
            }
            $existencia = $this->conciliacion->existenciasAlCorte((int) $auditoria->id_sucursal, (int) $auditoria->corte_inicio_id, [$producto->id]);

            return AuditoriaItem::create(['no_esperado' => true] + $this->snapshot($auditoria, $producto, $existencia[$producto->id] ?? 0));
        }
        if ($auditoria->status !== Auditoria::STATUS_EN_CONTEO) {
            throw ValidationException::withMessages(['descripcion_libre' => 'Sólo se registran productos nuevos durante el conteo.']);
        }
        $descripcion = trim($data['descripcion_libre']);
        $item = $auditoria->items()->whereNull('id_producto')->where('descripcion_libre', $descripcion)->lockForUpdate()->first();

        return $item ?? AuditoriaItem::create(['id_auditoria' => $auditoria->id, 'id_producto' => null,
            'descripcion_libre' => $descripcion, 'nombre' => $descripcion, 'no_esperado' => true]);
    }

    private function productosQuery(int $empresaId)
    {
        return Producto::withoutGlobalScope('empresa')->where('productos.id_empresa', $empresaId)
            ->leftJoin('cat_marcas', 'cat_marcas.id', '=', 'productos.id_marca')
            ->leftJoin('cat_clasificacions', 'cat_clasificacions.id', '=', 'productos.id_clasificacion')
            ->select(['productos.id', 'productos.nombre', 'productos.tamano', 'productos.barcode', 'productos.id_marca',
                'productos.id_clasificacion', 'productos.precio_unitario', 'cat_marcas.nombre as marca_nombre',
                'cat_clasificacions.nombre as clasificacion_nombre']);
    }

    private function productosDelAlcance(Auditoria $auditoria)
    {
        $filtros = $auditoria->alcance_filtros ?? [];

        return $this->productosQuery((int) $auditoria->id_empresa)
            ->when($auditoria->alcance_tipo === Auditoria::ALCANCE_MARCAS, fn ($q) => $q->whereIn('productos.id_marca', $filtros['marcas'] ?? [0]))
            ->when($auditoria->alcance_tipo === Auditoria::ALCANCE_CLASIFICACIONES, fn ($q) => $q->whereIn('productos.id_clasificacion', $filtros['clasificaciones'] ?? [0]))
            ->when($auditoria->alcance_tipo === Auditoria::ALCANCE_PRODUCTOS, fn ($q) => $q->whereIn('productos.id', $filtros['productos'] ?? [0]))
            ->orderBy('cat_marcas.nombre')->orderBy('productos.nombre')->get();
    }

    private function snapshot(Auditoria $auditoria, Producto $p, int $existencia): array
    {
        return [
            'id_auditoria' => $auditoria->id,
            'id_producto' => $p->id,
            'nombre' => $p->nombre,
            'tamano' => $p->tamano,
            'barcode' => $p->barcode,
            'id_marca' => $p->id_marca,
            'marca_nombre' => $p->marca_nombre,
            'clasificacion_nombre' => $p->clasificacion_nombre,
            'costo_unitario' => Decimal::format(AuditoriaConciliacion::units($p->precio_unitario)),
            'cantidad_sistema_inicio' => Decimal::format($existencia),
            'ronda' => 1,
            'estado' => AuditoriaItem::ESTADO_PENDIENTE,
            'no_esperado' => false,
        ];
    }

    private function validarDatos(array $input, bool $creando): array
    {
        $rules = [
            'alcance_tipo' => ['required', Rule::in(Auditoria::ALCANCES)],
            'marcas' => 'required_if:alcance_tipo,marcas|array',
            'marcas.*' => 'integer',
            'clasificaciones' => 'required_if:alcance_tipo,clasificaciones|array',
            'clasificaciones.*' => 'integer',
            'productos' => 'required_if:alcance_tipo,productos|array|max:5000',
            'productos.*' => 'integer',
            'auditores' => 'nullable|array|max:50',
            'auditores.*' => 'integer',
            'notas' => 'nullable|string|max:2000',
        ];
        if ($creando) {
            $rules += ['id_empresa' => 'required|integer', 'id_sucursal' => 'required|integer'];
        }

        return Validator::make($input, $rules, [
            'id_empresa.required' => 'Selecciona la empresa a auditar.',
            'id_sucursal.required' => 'Selecciona la sucursal o bodega a auditar.',
            'required_if' => 'Selecciona al menos un elemento para el alcance.',
        ])->validate();
    }

    private function filtros(array $data): ?array
    {
        return match ($data['alcance_tipo']) {
            Auditoria::ALCANCE_MARCAS => ['marcas' => array_values(array_unique(array_map('intval', $data['marcas'] ?? [])))],
            Auditoria::ALCANCE_CLASIFICACIONES => ['clasificaciones' => array_values(array_unique(array_map('intval', $data['clasificaciones'] ?? [])))],
            Auditoria::ALCANCE_PRODUCTOS => ['productos' => array_values(array_unique(array_map('intval', $data['productos'] ?? [])))],
            default => null,
        };
    }

    /** $siempre participa sin revalidar; los demás deben tener alcance vigente sobre la sucursal. */
    private function auditoresValidos(Sucursales $sucursal, array $ids, array $siempre): array
    {
        $siempre = array_map('intval', $siempre);
        $elegibles = $this->access->auditoresElegibles($sucursal)->pluck('id')->map(fn ($id) => (int) $id);
        $ids = collect($ids)->map(fn ($id) => (int) $id)->unique()->reject(fn ($id) => in_array($id, $siempre, true));
        if ($ids->diff($elegibles)->isNotEmpty()) {
            throw ValidationException::withMessages(['auditores' => 'Algún auditor seleccionado no tiene alcance sobre esta sucursal.']);
        }

        return $ids->merge($siempre)->unique()->values()->all();
    }

    private function siguienteFolio(int $empresaId): string
    {
        Empresa::whereKey($empresaId)->lockForUpdate()->first();
        $anio = now()->format('Y');
        $prefijo = "AUD-{$anio}-";
        $ultimo = Auditoria::where('id_empresa', $empresaId)->where('folio', 'like', $prefijo.'%')
            ->orderByDesc('folio')->value('folio');
        $siguiente = $ultimo ? ((int) substr($ultimo, strlen($prefijo))) + 1 : 1;

        return $prefijo.str_pad((string) $siguiente, 4, '0', STR_PAD_LEFT);
    }

    private function exigirEstado(Auditoria $auditoria, array $estados, string $mensaje): void
    {
        if (! in_array($auditoria->status, $estados, true)) {
            throw ValidationException::withMessages(['status' => $mensaje]);
        }
    }
}
