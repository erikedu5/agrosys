<?php

namespace App\Services\Pos;

use App\Models\Empresa;
use App\Models\OfflineDevice;
use App\Models\Sucursales;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PosAccess
{
    public function checkUser(User $user): void
    {
        abort_if($user->trashed(), 403, 'Usuario desactivado.');
        abort_unless(in_array($user->tipo, ['vendedor', 'inventario', 'admin', 'adminEmpresa', 'superAdmin'], true), 403, 'Rol no autorizado.');
        if ($user->tipo !== 'superAdmin') {
            $companyId = $user->id_empresa ?: Sucursales::find($user->id_sucursal)?->id_empresa;
            $company = Empresa::find($companyId);
            abort_unless($company, 403, 'Empresa desactivada.');
            abort_unless($company->canAccessApp(), 402, 'Suscripción requerida.');
            if ($user->tipo !== 'adminEmpresa') {
                $branch = Sucursales::find($user->id_sucursal);
                abort_unless($branch && (int) $branch->id_empresa === (int) $company->id, 403, 'Sucursal desactivada.');
            }
        }
    }

    public function branches(User $user): Collection
    {
        $this->checkUser($user);
        $query = Sucursales::query()->whereHas('empresa');
        if ($user->tipo === 'adminEmpresa') {
            $query->where('id_empresa', $user->id_empresa);
        } elseif ($user->tipo !== 'superAdmin') {
            $query->whereKey($user->id_sucursal);
        }

        return $query->orderBy('id')->get();
    }

    public function branch(User $user, int|string $id): Sucursales
    {
        $branch = $this->branches($user)->first(fn ($branch) => (string) $branch->id === (string) $id);
        abort_unless($branch, 403, 'Sucursal no autorizada.');
        abort_unless($branch->empresa->canAccessApp(), 402, 'Suscripción requerida.');

        return $branch;
    }

    public function permissions(User $user, Sucursales $branch): array
    {
        $permissions = ['catalog.read', 'product.search', 'stock.read_estimated', 'account.read_estimated', 'sync.view', 'sync.retry'];
        if (in_array($user->tipo, ['vendedor', 'admin', 'adminEmpresa', 'superAdmin'], true) && ! $branch->empresa->ventas_bloqueadas) {
            $permissions = [...$permissions, 'sale.create', 'sale.credit', 'sale.print_local_ticket'];
        }

        return $permissions;
    }

    public function device(Request $request, string $id, int|string $branchId): OfflineDevice
    {
        abort_unless($request->user()->currentAccessToken()?->pos_device_id === $id, 403, 'Token de otro dispositivo.');
        $device = OfflineDevice::whereKey($id)->where('user_id', $request->user()->id)->where('branch_id', $branchId)->where('client_kind', 'pos')->first();
        abort_unless($device && $device->authorized && ! $device->revoked_at, 403, 'Dispositivo revocado o no autorizado.');
        $this->branch($request->user(), $branchId);

        return $device;
    }
}
