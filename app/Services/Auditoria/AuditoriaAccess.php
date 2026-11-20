<?php

namespace App\Services\Auditoria;

use App\Models\Auditoria;
use App\Models\Empresa;
use App\Models\Sucursales;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Reglas de acceso del módulo de auditorías.
 * - superAdmin: todo.
 * - adminEmpresa: consulta, reporte y cancelación en su empresa.
 * - auditor: opera auditorías donde participa y cuya sucursal cubre su alcance.
 */
class AuditoriaAccess
{
    /** @var array<int, array<int>> */
    private array $memo = [];

    public function empresasDisponibles(User $user): Collection
    {
        return match (true) {
            $user->isSuperAdmin() => Empresa::orderBy('nombre')->get(['id', 'nombre']),
            $user->isAuditor() && ! $user->accesoVigente() => collect(),
            $user->isAuditor() => Empresa::whereIn('id', $user->alcancesAuditor()->pluck('id_empresa'))
                ->orderBy('nombre')->get(['id', 'nombre']),
            default => collect(),
        };
    }

    public function sucursalesDisponibles(User $user, int $empresaId): Collection
    {
        $query = Sucursales::where('id_empresa', $empresaId)->whereHas('empresa')
            ->orderByRaw('COALESCE(es_bodega, 0) asc')->orderBy('nombre');
        if ($user->isAuditor()) {
            $ids = $this->sucursalIdsCubiertas($user, false);
            $query->whereIn('id', $ids ?: [0]);
        } elseif (! $user->isSuperAdmin()) {
            return collect();
        }

        return $query->get(['id', 'nombre', 'es_bodega', 'id_empresa']);
    }

    /**
     * Sucursales que cubre el alcance del auditor.
     * Con $incluirInactivas se incluyen sucursales desactivadas (para consultar auditorías históricas).
     */
    public function sucursalIdsCubiertas(User $user, bool $incluirInactivas = true): array
    {
        $key = $user->id * 10 + (int) $incluirInactivas;
        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }
        $alcances = $user->alcancesAuditor()->get(['id_empresa', 'id_sucursal']);
        $completas = $alcances->whereNull('id_sucursal')->pluck('id_empresa')->unique()->values();
        $query = Sucursales::query()->when($incluirInactivas, fn ($q) => $q->withTrashed());
        $ids = $completas->isEmpty() ? collect() : (clone $query)->whereIn('id_empresa', $completas)->pluck('id');
        $puntuales = $alcances->whereNotNull('id_sucursal');
        if ($puntuales->isNotEmpty()) {
            // La sucursal puntual debe seguir perteneciendo a la empresa del alcance.
            $ids = $ids->merge((clone $query)->where(function ($q) use ($puntuales) {
                foreach ($puntuales as $alcance) {
                    $q->orWhere(fn ($w) => $w->where('id', $alcance->id_sucursal)->where('id_empresa', $alcance->id_empresa));
                }
            })->pluck('id'));
        }

        return $this->memo[$key] = $ids->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    public function cubreSucursal(User $user, Sucursales|int $sucursal): bool
    {
        $id = $sucursal instanceof Sucursales ? $sucursal->id : $sucursal;

        return in_array((int) $id, $this->sucursalIdsCubiertas($user), true);
    }

    /** Auditorías visibles para el usuario. */
    public function query(User $user): Builder
    {
        $query = Auditoria::query();

        return match (true) {
            $user->isSuperAdmin() => $query,
            $user->isAdminEmpresa() => $query->where('id_empresa', $user->id_empresa ?: 0),
            $user->isAuditor() && ! $user->accesoVigente() => $query->whereRaw('1 = 0'),
            $user->isAuditor() => $query->whereIn('id_sucursal', $this->sucursalIdsCubiertas($user) ?: [0])
                ->whereHas('auditores', fn ($q) => $q->where('users.id', $user->id)),
            default => $query->whereRaw('1 = 0'),
        };
    }

    public function puedeVer(User $user, Auditoria $auditoria): bool
    {
        return match (true) {
            $user->isSuperAdmin() => true,
            $user->isAdminEmpresa() => (int) $auditoria->id_empresa === (int) $user->id_empresa,
            $user->isAuditor() => $this->participa($user, $auditoria),
            default => false,
        };
    }

    public function puedeOperar(User $user, Auditoria $auditoria): bool
    {
        return $user->isSuperAdmin() || ($user->isAuditor() && $this->participa($user, $auditoria));
    }

    public function puedeCancelar(User $user, Auditoria $auditoria): bool
    {
        return $this->puedeOperar($user, $auditoria)
            || ($user->isAdminEmpresa() && (int) $auditoria->id_empresa === (int) $user->id_empresa);
    }

    /** La valorización en pesos sólo la ven los administradores. */
    public function verCostos(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdminEmpresa();
    }

    public function autorizarVer(User $user, Auditoria $auditoria): void
    {
        abort_unless($this->puedeVer($user, $auditoria), 403, 'No tienes acceso a esta auditoría.');
        $this->exigirSuscripcion($user, $auditoria);
    }

    public function autorizarOperar(User $user, Auditoria $auditoria): void
    {
        abort_unless($this->puedeOperar($user, $auditoria), 403, 'No puedes operar esta auditoría.');
        $this->exigirSuscripcion($user, $auditoria);
    }

    public function autorizarCancelar(User $user, Auditoria $auditoria): void
    {
        abort_unless($this->puedeCancelar($user, $auditoria), 403, 'No puedes cancelar esta auditoría.');
    }

    /** Auditores que pueden sumarse a una auditoría de la sucursal. */
    public function auditoresElegibles(Sucursales $sucursal): Collection
    {
        return User::where('tipo', User::TIPO_AUDITOR)
            ->whereHas('alcancesAuditor', fn ($q) => $q->where('id_empresa', $sucursal->id_empresa)
                ->where(fn ($w) => $w->whereNull('id_sucursal')->orWhere('id_sucursal', $sucursal->id)))
            ->orderBy('name')->get(['id', 'name', 'email', 'acceso_hasta'])
            ->filter(fn (User $u) => $u->accesoVigente())->values();
    }

    private function participa(User $user, Auditoria $auditoria): bool
    {
        // La vigencia se revisa también aquí: una sesión abierta no sobrevive al vencimiento.
        return $user->accesoVigente() && $this->cubreSucursal($user, (int) $auditoria->id_sucursal)
            && $auditoria->auditores()->where('users.id', $user->id)->exists();
    }

    private function exigirSuscripcion(User $user, Auditoria $auditoria): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }
        $empresa = $auditoria->empresa;
        abort_unless($empresa && ! $empresa->trashed() && $empresa->canAccessApp(), 402, 'La empresa auditada no tiene una suscripción activa.');
    }
}
