<?php

namespace App\Http\Controllers;

use App\Models\AltaInventario;
use App\Models\CatClasificacion;
use App\Models\CatMarca;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Services\SucursalService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        [$sucursalId, $sucursal, $ventasBloqueadas, $motivoBloqueo] = $this->obtenerSucursalYEstado();

        if (!$sucursalId || !$sucursal) {
            return redirect()->route('sucursal.selection');
        }

        // Mostrar solo productos asociados a la empresa activa (pivot empresa_producto)
        $empresaId = $sucursal->id_empresa;

        $tienePivot = \App\Models\EmpresaProducto::where('id_empresa', $empresaId)->exists();
        $productosQuery = $tienePivot
            ? Producto::query()->forEmpresa($empresaId)
            : Producto::query();

        if ($request->filled('q')) {
            $term = $request->q;
            $productosQuery->where(function ($q) use ($term) {
                $q->where('productos.nombre', 'LIKE', "%$term%")
                  ->orWhere('productos.barcode', 'LIKE', "%$term%")
                  ->orWhere('productos.ingrediente_activo', 'LIKE', "%$term%");
            });
        }

        $productos = $productosQuery->latest('productos.created_at')->paginate(10);

        foreach ($productos as $producto) {
            $marca = CatMarca::where('id', $producto->id_marca)->first();
            $producto->marca = $marca;
            $altaInventario = AltaInventario::where('id_producto', $producto->id)
                ->where('id_sucursal', $sucursalId)
                ->orderBy('created_at', 'desc')->first();
            $producto->cantidad = $altaInventario !== null ? $altaInventario->cantidad_nueva : "0";
        }

        return Inertia::render('Inventario/Inventario', [
            'productos' => $productos,
            'ventasBloqueadas' => $ventasBloqueadas,
            'motivoBloqueo' => $motivoBloqueo,
            'sucursalId' => $sucursalId,
            'empresaId' => $sucursal->id_empresa,
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        [$sucursalId, $sucursal, $ventasBloqueadas, $motivoBloqueo] = $this->obtenerSucursalYEstado();

        $this->asegurarAccesoInventario($ventasBloqueadas, $motivoBloqueo);

        $clasificaciones = CatClasificacion::get();
        $marca = CatMarca::get();

        return Inertia::render('Inventario/CreateProducto', [
            'clasificaciones' => $clasificaciones,
            'marcas' => $marca,
            'sucursalId' => $sucursalId,
            'empresaId' => $sucursal?->id_empresa,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        [$sucursalId, , $ventasBloqueadas, $motivoBloqueo] = $this->obtenerSucursalYEstado();

        if (!$sucursalId) {
            throw ValidationException::withMessages([
                'bloqueo' => 'No se encontró una sucursal activa. Selecciona una sucursal antes de continuar.',
            ]);
        }

        $this->asegurarAccesoInventario($ventasBloqueadas, $motivoBloqueo);

        $request->merge([
            'precio_unitario' => $request->filled('precio_unitario') ? $request->precio_unitario : null,
            'precio_ieps' => $request->precio_ieps,
            'ieps' => $request->filled('ieps') ? $request->ieps : null,
            'ingrediente_activo' => $request->filled('ingrediente_activo') ? $request->ingrediente_activo : null,
            'barcode' => $request->filled('barcode') ? $request->barcode : null,
        ]);

        $request->validate(
            [
                'nombre' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('productos')->where(function ($query) use ($request) {
                        return $query->where('tamano', $request->tamano);
                    }),
                ],
                'id_clasificacion' => 'required|integer|min:1',
                'id_marca' => 'required|integer|min:1',
                'precio_unitario' => 'nullable|numeric|min:0.01',
                'precio_ieps' => 'required|numeric|min:0.01',
                'ieps' => 'nullable|numeric|min:0',
                'tamano' => 'required|string|max:100',
                'ingrediente_activo' => 'nullable|string|max:255',
                'barcode' => 'nullable|string|max:255',
            ],
            [
                'nombre.required' => 'El nombre del producto es requerido.',
                'nombre.string' => 'El nombre debe ser un texto válido.',
                'nombre.max' => 'El nombre no puede exceder 255 caracteres.',
                'id_clasificacion.required' => 'Debe seleccionar una clasificación.',
                'id_clasificacion.integer' => 'La clasificación debe ser válida.',
                'id_clasificacion.min' => 'Debe seleccionar una clasificación válida.',
                'id_marca.required' => 'Debe seleccionar una marca.',
                'id_marca.integer' => 'La marca debe ser válida.',
                'id_marca.min' => 'Debe seleccionar una marca válida.',
                'precio_unitario.numeric' => 'El precio de compra debe ser un número.',
                'precio_unitario.min' => 'El precio de compra debe ser mayor a 0 cuando se capture.',
                'precio_ieps.required' => 'El precio de venta es requerido.',
                'precio_ieps.numeric' => 'El precio de venta debe ser un número.',
                'precio_ieps.min' => 'El precio de venta debe ser mayor a 0.',
                'ieps.numeric' => 'El porcentaje de ganancia debe ser un número válido.',
                'ieps.min' => 'El porcentaje de ganancia debe ser mayor o igual a 0 cuando se capture.',
                'tamano.required' => 'El tamaño del producto es requerido.',
                'tamano.string' => 'El tamaño debe ser un texto válido.',
                'tamano.max' => 'El tamaño no puede exceder 100 caracteres.',
                'nombre.unique' => 'Ya existe un producto con ese nombre y tamaño. Edítalo en lugar de duplicarlo.',
            ]
        );

        $payload = $request->all();
        $payload['id_usuario'] = Auth::user()->id;
        $payload['cantidad'] = 0;
        $payload['precio_unitario'] = $request->precio_unitario ?? 0;
        $payload['ieps'] = $request->ieps ?? 0;
        $payload['ingrediente_activo'] = $request->ingrediente_activo ?? '';
        $payload['barcode'] = $request->barcode ?? '';

        $producto = Producto::create($payload);

        $altaInventario = new AltaInventario();
        $altaInventario->cantidad_actual = 0;
        $altaInventario->cantidad_nueva = 0;
        $altaInventario->id_usuario = Auth::user()->id;
        $altaInventario->id_producto = $producto->id;
        $altaInventario->id_sucursal = $sucursalId;
        $altaInventario->save();

        // Asociar producto a la empresa activa
        \App\Models\EmpresaProducto::firstOrCreate([
            'id_empresa' => $sucursal->id_empresa,
            'id_producto' => $producto->id,
        ]);

        if ($request->expectsJson()) {
            $producto->marca = CatMarca::find($producto->id_marca);
            return response()->json($producto);
        }

        return redirect()->route('solucion.index', [
            'id_producto' => $producto->id,
            'from_product_creation' => 'true'
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Producto $inventario)
    {
        [$sucursalId, , $ventasBloqueadas, $motivoBloqueo] = $this->obtenerSucursalYEstado();

        if (!$sucursalId) {
            return redirect()->route('sucursal.selection');
        }

        $this->asegurarAccesoInventario($ventasBloqueadas, $motivoBloqueo);

        $altaInventario = AltaInventario::where('id_producto', $inventario->id)
            ->where('id_sucursal', $sucursalId)
            ->orderBy('created_at', 'desc')->first();
        $inventario->cantidad = $altaInventario !== null ? $altaInventario->cantidad_nueva : "0";

        $clasificaciones = CatClasificacion::get();
        $marca = CatMarca::get();
        return Inertia::render('Inventario/CreateProducto', [
            'clasificaciones' => $clasificaciones,
            'marcas' => $marca,
            'producto' => $inventario
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        [, , $ventasBloqueadas, $motivoBloqueo] = $this->obtenerSucursalYEstado();
        $this->asegurarAccesoInventario($ventasBloqueadas, $motivoBloqueo);

        $request->merge([
            'precio_unitario' => $request->filled('precio_unitario') ? $request->precio_unitario : null,
            'ieps' => $request->filled('ieps') ? $request->ieps : null,
            'ingrediente_activo' => $request->filled('ingrediente_activo') ? $request->ingrediente_activo : null,
            'barcode' => $request->filled('barcode') ? $request->barcode : null,
        ]);

        $request->validate(
            [
                'nombre' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('productos')->ignore($request->id)->where(function ($query) use ($request) {
                        return $query->where('tamano', $request->tamano);
                    }),
                ],
                'id_clasificacion' => 'required|integer|min:1',
                'id_marca' => 'required|integer|min:1',
                'precio_unitario' => 'nullable|numeric|min:0.01',
                'tamano' => 'required|string|max:100',
                'precio_ieps' => 'required|numeric|min:0.01',
                'ieps' => 'nullable|numeric|min:0',
                'ingrediente_activo' => 'nullable|string|max:255',
                'barcode' => 'nullable|string|max:255',
            ],
            [
                'nombre.required' => 'El nombre del producto es requerido.',
                'nombre.string' => 'El nombre debe ser un texto válido.',
                'nombre.max' => 'El nombre no puede exceder 255 caracteres.',
                'id_clasificacion.required' => 'Debe seleccionar una clasificación.',
                'id_clasificacion.integer' => 'La clasificación debe ser válida.',
                'id_clasificacion.min' => 'Debe seleccionar una clasificación válida.',
                'id_marca.required' => 'Debe seleccionar una marca.',
                'id_marca.integer' => 'La marca debe ser válida.',
                'id_marca.min' => 'Debe seleccionar una marca válida.',
                'precio_unitario.numeric' => 'El precio de compra debe ser un número.',
                'precio_unitario.min' => 'El precio de compra debe ser mayor a 0 cuando se capture.',
                'precio_ieps.required' => 'El precio de venta es requerido.',
                'precio_ieps.numeric' => 'El precio de venta debe ser un número.',
                'precio_ieps.min' => 'El precio de venta debe ser mayor a 0.',
                'ieps.numeric' => 'El porcentaje de ganancia debe ser un número válido.',
                'ieps.min' => 'El porcentaje de ganancia debe ser mayor o igual a 0 cuando se capture.',
                'tamano.required' => 'El tamaño del producto es requerido.',
                'tamano.string' => 'El tamaño debe ser un texto válido.',
                'tamano.max' => 'El tamaño no puede exceder 100 caracteres.',
                'nombre.unique' => 'Ya existe un producto con ese nombre y tamaño. Edítalo en lugar de duplicarlo.',
            ]
        );

        $catProducto = Producto::find($request->id);
        $catProducto->nombre = $request->nombre;
        $catProducto->id_clasificacion = $request->id_clasificacion;
        $catProducto->id_marca = $request->id_marca;
        $catProducto->precio_unitario = $request->precio_unitario ?? 0;
        $catProducto->ieps = $request->ieps ?? 0;
        $catProducto->precio_ieps = $request->precio_ieps;
        $catProducto->tamano = $request->tamano;
        $catProducto->ingrediente_activo = $request->ingrediente_activo ?? '';
        $catProducto->barcode = $request->barcode ?? '';
        $catProducto->id_usuario = Auth::user()->id;
        $catProducto->save();

        return redirect()->route('solucion.index', [
            'id_producto' => $request->id
        ]);
    }

    public function show(Producto $inventario)
    {
        [$sucursalId, , $ventasBloqueadas, $motivoBloqueo] = $this->obtenerSucursalYEstado();

        if (!$sucursalId) {
            return redirect()->route('sucursal.selection');
        }

        $this->asegurarAccesoInventario($ventasBloqueadas, $motivoBloqueo);

        $altaInventario = AltaInventario::where('id_producto', $inventario->id)
            ->where('id_sucursal', $sucursalId)
            ->orderBy('created_at', 'desc')->first();
        $inventario->cantidad = $altaInventario !== null ? $altaInventario->cantidad_nueva : "0";
        return Inertia::render('Inventario/AddInventario', [
            'producto' => $inventario
        ]);
    }

    public function addInventario(Request $request)
    {
        [$sucursalId, , $ventasBloqueadas, $motivoBloqueo] = $this->obtenerSucursalYEstado();

        if (!$sucursalId) {
            throw ValidationException::withMessages([
                'bloqueo' => 'No se encontró una sucursal activa. Selecciona una sucursal antes de continuar.',
            ]);
        }

        $this->asegurarAccesoInventario($ventasBloqueadas, $motivoBloqueo);

        $request->validate(['cantidad' => 'required|numeric|gt:0']);

        $catProducto = Producto::find($request->id);

        $actualStock = AltaInventario::where('id_producto', $request->id)
            ->where('id_sucursal', $sucursalId)
            ->orderBy('created_at', 'desc')->first();

        $altaInventario = new AltaInventario();
        $cantidad = $actualStock !== null ? $actualStock->cantidad_nueva : 0;
        $altaInventario->cantidad_actual = $cantidad;
        $altaInventario->cantidad_nueva = $cantidad + $request->cantidad;
        $altaInventario->id_usuario = Auth::user()->id;
        $altaInventario->id_producto = $catProducto->id;
        $altaInventario->id_sucursal = $sucursalId;
        $altaInventario->save();

        return redirect()->route('inventario.index', [
            'id_producto' => $request->id
        ]);
    }

    public function resetInventario(Producto $producto)
    {
        [$sucursalId, , $ventasBloqueadas, $motivoBloqueo] = $this->obtenerSucursalYEstado();

        if (!$sucursalId) {
            return redirect()->route('sucursal.selection');
        }

        $this->asegurarAccesoInventario($ventasBloqueadas, $motivoBloqueo);

        $actualStock = AltaInventario::where('id_producto', $producto->id)
            ->where('id_sucursal', $sucursalId)
            ->orderBy('created_at', 'desc')
            ->first();

        $cantidad = $actualStock !== null ? $actualStock->cantidad_nueva : 0;

        $altaInventario = new AltaInventario();
        $altaInventario->cantidad_actual = $cantidad;
        $altaInventario->cantidad_nueva = 0;
        $altaInventario->id_usuario = Auth::user()->id;
        $altaInventario->id_producto = $producto->id;
        $altaInventario->id_sucursal = $sucursalId;
        $altaInventario->save();

        return redirect()->route('inventario.index');
    }

    // Eliminar lógicamente un producto del inventario de la empresa (sin borrar el producto)
    public function detachFromEmpresa(Producto $producto)
    {
        [, $sucursal, $ventasBloqueadas, $motivoBloqueo] = $this->obtenerSucursalYEstado();

        if (!$sucursal) {
            return redirect()->route('sucursal.selection');
        }

        $this->asegurarAccesoInventario($ventasBloqueadas, $motivoBloqueo);

        $empresaId = $sucursal->id_empresa;

        // Eliminar historial de inventario en TODAS las sucursales de la empresa
        $sucursalIds = Sucursales::where('id_empresa', $empresaId)->pluck('id');
        if ($sucursalIds->isNotEmpty()) {
            AltaInventario::where('id_producto', $producto->id)
                ->whereIn('id_sucursal', $sucursalIds)
                ->delete();
        }

        // Eliminar relación pivote (soft delete)
        \App\Models\EmpresaProducto::where('id_empresa', $empresaId)
            ->where('id_producto', $producto->id)
            ->first()?->delete();

        return redirect()->route('inventario.index');
    }

    public function updatePrecios(Request $request, Producto $producto)
    {
        [, , $ventasBloqueadas, $motivoBloqueo] = $this->obtenerSucursalYEstado();
        $this->asegurarAccesoInventario($ventasBloqueadas, $motivoBloqueo);

        $puedeGestionarCostos = $this->usuarioPuedeGestionarCostos();

        $rules = [
            'precio_ieps' => ['required', 'numeric', 'min:0.01'],
        ];

        $messages = [
            'precio_ieps.required' => 'El precio de venta es requerido.',
            'precio_ieps.numeric' => 'El precio de venta debe ser un número.',
            'precio_ieps.min' => 'El precio de venta debe ser mayor a 0.',
        ];

        if ($puedeGestionarCostos) {
            $rules['precio_unitario'] = ['nullable', 'numeric', 'min:0.01'];
            $rules['ieps'] = ['nullable', 'numeric', 'min:0'];

            $messages = array_merge($messages, [
                'precio_unitario.numeric' => 'El precio de compra debe ser un número.',
                'precio_unitario.min' => 'El precio de compra debe ser mayor a 0 cuando se capture.',
                'ieps.numeric' => 'El porcentaje de ganancia debe ser un número válido.',
                'ieps.min' => 'El porcentaje de ganancia debe ser mayor o igual a 0 cuando se capture.',
            ]);
        }

        $validated = $request->validate($rules, $messages);

        $producto->precio_ieps = $validated['precio_ieps'];

        if ($puedeGestionarCostos) {
            $producto->precio_unitario = $request->filled('precio_unitario') ? $validated['precio_unitario'] : 0;
            $producto->ieps = $request->filled('ieps') ? $validated['ieps'] : 0;
        }

        $producto->id_usuario = Auth::id();
        $producto->save();

        return redirect()->route('inventario.index');
    }

    private function obtenerSucursalYEstado(): array
    {
        $sucursalId = SucursalService::getSucursalActiva();

        if (!$sucursalId) {
            return [null, null, false, null];
        }

        $sucursal = Sucursales::withTrashed()->with(['empresa' => function ($query) {
            $query->withTrashed();
        }])->find($sucursalId);

        if (!$sucursal) {
            return [$sucursalId, null, false, null];
        }

        $empresa = $sucursal->empresa;
        $ventasBloqueadas = $empresa?->ventas_bloqueadas ?? false;
        $motivoBloqueo = $ventasBloqueadas
            ? ($empresa->motivo_bloqueo ?? 'Esta sección está bloqueada, Contacte a su administrador.')
            : null;

        return [$sucursalId, $sucursal, $ventasBloqueadas, $motivoBloqueo];
    }

    private function asegurarAccesoInventario(bool $ventasBloqueadas, ?string $motivoBloqueo): void
    {
        $usuario = Auth::user();

        if ($ventasBloqueadas && $usuario && $usuario->tipo !== 'superAdmin') {
            throw ValidationException::withMessages([
                'bloqueo' => $motivoBloqueo ?? 'Esta sección está bloqueada, Contacte a su administrador.',
            ]);
        }
    }

    private function usuarioPuedeGestionarCostos(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if (in_array($user->tipo, ['adminEmpresa', 'superAdmin'], true)) {
            return true;
        }

        $empresa = $user->empresa;

        if (!$empresa && $user->id_empresa) {
            $empresa = Empresa::find($user->id_empresa);
        }

        if (!$empresa) {
            $sucursal = SucursalService::getSucursalActivaCompleta();
            $empresa = $sucursal?->empresa;
        }

        return $empresa?->mostrar_campos_precio ?? true;
    }
}
