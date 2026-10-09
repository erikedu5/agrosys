<?php

namespace App\Services;

use App\Models\AltaInventario;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\User;
use App\Services\Pos\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InventoryManagement
{
    public function canManageCosts(User $user, Sucursales $branch): bool
    {
        return in_array($user->tipo, ['adminEmpresa', 'superAdmin'], true)
            || ($branch->empresa->mostrar_campos_precio ?? true);
    }

    public function version(Producto $product): string
    {
        return hash('sha256', json_encode($product->only(['id', 'nombre', 'tamano', 'id_clasificacion', 'id_marca',
            'precio_unitario', 'precio_ieps', 'ieps', 'ingrediente_activo', 'barcode', 'updated_at']), JSON_THROW_ON_ERROR));
    }

    public function product(User $user, Sucursales $branch, array $input, ?int $id = null, ?string $version = null): Producto
    {
        return DB::transaction(function () use ($user, $branch, $input, $id, $version) {
            $product = $id === null ? new Producto : $this->lockedProduct($branch, $id, $version);
            foreach (['nombre', 'tamano', 'barcode', 'ingrediente_activo'] as $field) {
                if (isset($input[$field]) && is_string($input[$field])) {
                    $input[$field] = trim($input[$field]);
                }
            }
            $rules = ['nombre' => ['required', 'string', 'max:255', Rule::unique('productos')->ignore($id)
                ->where('id_empresa', $branch->id_empresa)->where('tamano', $input['tamano'] ?? null)],
                'tamano' => 'required|string|max:100', 'id_clasificacion' => 'required|integer|exists:cat_clasificacions,id',
                'id_marca' => 'required|integer|exists:cat_marcas,id', 'barcode' => 'nullable|string|max:255',
                'ingrediente_activo' => 'nullable|string|max:255'];
            $data = Validator::make($input, $rules, ['nombre.unique' => 'Ya existe un producto con ese nombre y tamaño. Edítalo en lugar de duplicarlo.'])->validate();
            $product->fill([...$data, ...$this->prices($user, $branch, $input), 'id_usuario' => $user->id]);
            if ($id === null) {
                $product->id_empresa = $branch->id_empresa;
                $product->cantidad = 0;
                $product->precio_unitario ??= 0;
                $product->ieps ??= 0;
                $product->barcode ??= '';
                $product->ingrediente_activo ??= '';
            }
            $product->save();
            if ($id === null) {
                AltaInventario::create(['cantidad_actual' => 0, 'cantidad_nueva' => 0, 'id_usuario' => $user->id,
                    'id_producto' => $product->id, 'id_sucursal' => $branch->id, 'tipo_evento' => AltaInventario::EVENTO_ALTA]);
            }

            return $product->refresh();
        }, 3);
    }

    public function updatePrices(User $user, Sucursales $branch, int $id, array $input, ?string $version = null): Producto
    {
        return DB::transaction(function () use ($user, $branch, $id, $input, $version) {
            $product = $this->lockedProduct($branch, $id, $version);
            $product->fill([...$this->prices($user, $branch, $input), 'id_usuario' => $user->id]);
            $product->save();

            return $product->refresh();
        }, 3);
    }

    private function prices(User $user, Sucursales $branch, array $input): array
    {
        $rules = ['precio_ieps' => 'required|numeric|min:0.01|max:99999999.99'];
        if ($this->canManageCosts($user, $branch)) {
            $rules += ['precio_unitario' => 'nullable|numeric|min:0|max:99999999.99', 'ieps' => 'nullable|numeric|min:0|max:99999999.99'];
        }
        $data = Validator::make($input, $rules)->validate();
        foreach (['precio_ieps', 'precio_unitario', 'ieps'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = Decimal::format(Decimal::units($data[$field] ?? '0', $field));
            }
        }
        if (array_key_exists('ieps', $data)) {
            $data['ieps'] ??= 0;
        }

        return $data;
    }

    private function lockedProduct(Sucursales $branch, int $id, ?string $version): Producto
    {
        $product = Producto::withoutGlobalScope('empresa')->where('id_empresa', $branch->id_empresa)->lockForUpdate()->findOrFail($id);
        abort_if($version !== null && ! hash_equals($this->version($product), $version), 409, 'El producto cambió. Recarga sus datos antes de guardar.');

        return $product;
    }

    private function authorizeDestructive(User $user): void
    {
        abort_unless(in_array($user->tipo, ['adminEmpresa', 'superAdmin'], true), 403, 'No tienes permiso para esta operación.');
    }

    private function stockState(Sucursales $branch, int $id, string $action): array
    {
        $query = AltaInventario::query()->join('sucursales', 'sucursales.id', '=', 'alta_inventarios.id_sucursal')
            ->where('alta_inventarios.id_producto', $id)->where('sucursales.id_empresa', $branch->id_empresa);
        if ($action === 'reset') {
            $query->where('alta_inventarios.id_sucursal', $branch->id);
        }
        $rows = $query->whereIn('alta_inventarios.id', function ($sub) use ($id) {
            $sub->selectRaw('MAX(id)')->from('alta_inventarios')->where('id_producto', $id)->groupBy('id_sucursal');
        })->orderBy('alta_inventarios.id_sucursal')->lockForUpdate()
            ->get(['alta_inventarios.id', 'alta_inventarios.id_sucursal', 'alta_inventarios.cantidad_nueva', 'sucursales.nombre']);
        $quantity = $rows->firstWhere('id_sucursal', $branch->id)?->cantidad_nueva ?? '0';

        return ['stockRevision' => hash('sha256', json_encode($rows->toArray(), JSON_THROW_ON_ERROR)),
            'quantity' => Decimal::format(Decimal::units($quantity, 'quantity', true)),
            'branchesWithStock' => $rows->filter(fn ($row) => Decimal::units($row->cantidad_nueva, 'quantity', true) > 0)
                ->map(fn ($row) => ['id' => (string) $row->id_sucursal, 'name' => $row->nombre,
                    'quantity' => Decimal::format(Decimal::units($row->cantidad_nueva, 'quantity', true))])->values()->all()];
    }

    public function destructivePreview(User $user, Sucursales $branch, int $id, string $action): array
    {
        $this->authorizeDestructive($user);

        return DB::transaction(function () use ($branch, $id, $action) {
            $product = $this->lockedProduct($branch, $id, null);

            return ['productId' => (string) $product->id, 'name' => $product->nombre, 'size' => $product->tamano,
                'version' => $this->version($product), ...$this->stockState($branch, $id, $action)];
        }, 3);
    }

    public function destructive(User $user, Sucursales $branch, int $id, string $action, ?string $version = null, ?string $stockRevision = null): ?AltaInventario
    {
        $this->authorizeDestructive($user);
        abort_unless(in_array($action, ['reset', 'delete'], true), 422);

        return DB::transaction(function () use ($user, $branch, $id, $action, $version, $stockRevision) {
            $product = $this->lockedProduct($branch, $id, $version);
            $stock = $this->stockState($branch, $id, $action);
            abort_if($stockRevision !== null && ! hash_equals($stock['stockRevision'], $stockRevision), 409,
                'Las existencias cambiaron. Recarga los datos y confirma de nuevo.');
            if ($action === 'delete') {
                if ($stock['branchesWithStock']) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['producto' => 'No se puede borrar el producto porque tiene inventario en al menos una sucursal. Sucursales con stock: '.
                        implode(', ', array_column($stock['branchesWithStock'], 'name'))]);
                }
                $product->id_usuario = $user->id;
                $product->save();
                $product->delete();

                return null;
            }

            return AltaInventario::create(['cantidad_actual' => $stock['quantity'], 'cantidad_nueva' => '0.00',
                'id_usuario' => $user->id, 'id_producto' => $id, 'id_sucursal' => $branch->id,
                'tipo_evento' => AltaInventario::EVENTO_RESETEO]);
        }, 3);
    }

    public function addStock(User $user, Sucursales $branch, int $id, mixed $quantity): AltaInventario
    {
        Validator::make(['cantidad' => $quantity], ['cantidad' => 'required|numeric|gt:0|max:999999.99'])->validate();
        $units = Decimal::units($quantity, 'cantidad');

        return $this->moveStock($user, $branch, $id, $units, AltaInventario::EVENTO_ALTA);
    }

    public function moveStock(User $user, Sucursales $branch, int $id, int $units, string $event): AltaInventario
    {
        return DB::transaction(function () use ($user, $branch, $id, $units, $event) {
            $product = $this->lockedProduct($branch, $id, null);
            $last = AltaInventario::where('id_producto', $product->id)->where('id_sucursal', $branch->id)
                ->orderByDesc('id')->lockForUpdate()->first();
            $before = Decimal::units($last?->cantidad_nueva ?? '0', 'cantidad', true);
            abort_if(abs($before + $units) > 9999999999, 422, 'La existencia excede el máximo permitido.');

            return AltaInventario::create(['cantidad_actual' => Decimal::format($before), 'cantidad_nueva' => Decimal::format($before + $units),
                'id_usuario' => $user->id, 'id_producto' => $product->id, 'id_sucursal' => $branch->id,
                'tipo_evento' => $event]);
        }, 3);
    }
}
