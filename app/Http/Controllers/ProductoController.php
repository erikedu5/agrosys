<?php

namespace App\Http\Controllers;

use App\Models\AltaInventario;
use App\Models\CatClasificacion;
use App\Models\CatMarca;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Services\SucursalService;
use Carbon\Carbon;
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

        $productos = Producto::where(function ($query) use ($request) {
            $query->where('nombre', 'LIKE', "%$request->q%")
                ->orWhere('barcode', 'LIKE', "%$request->q%")
                ->orWhere('ingrediente_activo', 'LIKE', "%$request->q%");
        })
            ->latest()
            ->paginate(10);

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
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        [, , $ventasBloqueadas, $motivoBloqueo] = $this->obtenerSucursalYEstado();

        $this->asegurarAccesoInventario($ventasBloqueadas, $motivoBloqueo);

        $clasificaciones = CatClasificacion::get();
        $marca = CatMarca::get();

        return Inertia::render('Inventario/CreateProducto', [
            'clasificaciones' => $clasificaciones,
            'marcas' => $marca
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        [$sucursalId, , $ventasBloqueadas, $motivoBloqueo] = $this->obtenerSucursalYEstado();
        $empresaId = SucursalService::getEmpresaIdActiva();

        if (!$sucursalId) {
            throw ValidationException::withMessages([
                'bloqueo' => 'No se encontró una sucursal activa. Selecciona una sucursal antes de continuar.',
            ]);
        }

        if (!$empresaId) {
            throw ValidationException::withMessages([
                'empresa' => 'No se encontró una empresa activa para el usuario.',
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
                    Rule::unique('productos')->where(function ($query) use ($request, $empresaId) {
                        return $query
                            ->where('tamano', $request->tamano)
                            ->where('id_empresa', $empresaId);
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
        $payload['id_empresa'] = $empresaId;
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
        $altaInventario->tipo_evento = AltaInventario::EVENTO_ALTA;
        $altaInventario->save();

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
        $empresaId = SucursalService::getEmpresaIdActiva();
        [, , $ventasBloqueadas, $motivoBloqueo] = $this->obtenerSucursalYEstado();
        $this->asegurarAccesoInventario($ventasBloqueadas, $motivoBloqueo);

        if (!$empresaId) {
            throw ValidationException::withMessages([
                'empresa' => 'No se encontró una empresa activa para el usuario.',
            ]);
        }

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
                    Rule::unique('productos')->ignore($request->id)->where(function ($query) use ($request, $empresaId) {
                        return $query
                            ->where('tamano', $request->tamano)
                            ->where('id_empresa', $empresaId);
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
        if (!$catProducto) {
            throw ValidationException::withMessages([
                'producto' => 'No se encontró el producto en la empresa activa.',
            ]);
        }

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
        if (!$catProducto) {
            throw ValidationException::withMessages([
                'producto' => 'No se encontró el producto en la empresa activa.',
            ]);
        }

        // Iniciar logging del evento
        $logger = \App\Services\EventLogger::start('AGREGAR_INVENTARIO', [
            'producto_id' => $catProducto->id,
            'producto_nombre' => $catProducto->nombre,
            'cantidad_agregar' => $request->cantidad,
            'sucursal_id' => $sucursalId,
        ]);

        try {
            $actualStock = AltaInventario::where('id_producto', $request->id)
                ->where('id_sucursal', $sucursalId)
                ->orderBy('created_at', 'desc')->first();

            $cantidad = $actualStock !== null ? $actualStock->cantidad_nueva : 0;

            $logger->step('Stock actual obtenido', [
                'stock_actual' => $cantidad,
            ]);

            $altaInventario = new AltaInventario();
            $altaInventario->cantidad_actual = $cantidad;
            $altaInventario->cantidad_nueva = $cantidad + $request->cantidad;
            $altaInventario->id_usuario = Auth::user()->id;
            $altaInventario->id_producto = $catProducto->id;
            $altaInventario->id_sucursal = $sucursalId;
            $altaInventario->tipo_evento = AltaInventario::EVENTO_ALTA;
            $altaInventario->save();

            $logger->success([
                'stock_anterior' => $cantidad,
                'stock_nuevo' => $altaInventario->cantidad_nueva,
                'incremento' => $request->cantidad,
            ]);

            return redirect()->route('inventario.index', [
                'id_producto' => $request->id
            ]);
        } catch (\Exception $e) {
            $logger->error($e, [
                'producto_id' => $request->id,
            ]);

            throw $e;
        }
    }

    public function resetInventario(Producto $producto)
    {
        [$sucursalId, , $ventasBloqueadas, $motivoBloqueo] = $this->obtenerSucursalYEstado();

        if (!$sucursalId) {
            return redirect()->route('sucursal.selection');
        }

        $this->asegurarAccesoInventario($ventasBloqueadas, $motivoBloqueo);

        // Iniciar logging del evento
        $logger = \App\Services\EventLogger::start('RESET_INVENTARIO', [
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre,
            'sucursal_id' => $sucursalId,
        ]);

        try {
            $actualStock = AltaInventario::where('id_producto', $producto->id)
                ->where('id_sucursal', $sucursalId)
                ->orderBy('created_at', 'desc')
                ->first();

            $cantidad = $actualStock !== null ? $actualStock->cantidad_nueva : 0;

            $logger->step('Stock actual obtenido', [
                'stock_actual' => $cantidad,
            ]);

            $altaInventario = new AltaInventario();
            $altaInventario->cantidad_actual = $cantidad;
            $altaInventario->cantidad_nueva = 0;
            $altaInventario->id_usuario = Auth::user()->id;
            $altaInventario->id_producto = $producto->id;
            $altaInventario->id_sucursal = $sucursalId;
            $altaInventario->tipo_evento = AltaInventario::EVENTO_RESETEO;
            $altaInventario->save();

            $logger->success([
                'stock_anterior' => $cantidad,
                'stock_nuevo' => 0,
            ]);

            return redirect()->route('inventario.index');

        } catch (\Exception $e) {
            $logger->error($e, [
                'producto_id' => $producto->id,
            ]);

            throw $e;
        }
    }

    public function destroy(Producto $inventario)
    {
        [$sucursalId, , $ventasBloqueadas, $motivoBloqueo] = $this->obtenerSucursalYEstado();

        if (!$sucursalId) {
            return redirect()->route('sucursal.selection');
        }

        $this->asegurarAccesoInventario($ventasBloqueadas, $motivoBloqueo);

        $user = Auth::user();
        if (!$user || !in_array($user->tipo, ['adminEmpresa', 'superAdmin'], true)) {
            abort(403, 'No tiene permisos para borrar productos.');
        }

        $empresaId = SucursalService::getEmpresaIdActiva();
        if (!$empresaId || (int) $inventario->id_empresa !== (int) $empresaId) {
            abort(404);
        }

        $stockSucursales = AltaInventario::query()
            ->select('alta_inventarios.id_sucursal', 'sucursales.nombre', 'alta_inventarios.cantidad_nueva')
            ->join('sucursales', 'sucursales.id', '=', 'alta_inventarios.id_sucursal')
            ->where('alta_inventarios.id_producto', $inventario->id)
            ->where('sucursales.id_empresa', $empresaId)
            ->whereIn('alta_inventarios.id', function ($sub) use ($inventario, $empresaId) {
                $sub->selectRaw('MAX(ai2.id)')
                    ->from('alta_inventarios as ai2')
                    ->join('sucursales as s2', 's2.id', '=', 'ai2.id_sucursal')
                    ->where('ai2.id_producto', $inventario->id)
                    ->where('s2.id_empresa', $empresaId)
                    ->groupBy('ai2.id_sucursal');
            })
            ->where('alta_inventarios.cantidad_nueva', '>', 0)
            ->get();

        if ($stockSucursales->isNotEmpty()) {
            $sucursales = $stockSucursales->pluck('nombre')->filter()->unique()->values();
            $detalle = $sucursales->isNotEmpty()
                ? ' Sucursales con stock: ' . $sucursales->join(', ')
                : '';

            throw ValidationException::withMessages([
                'producto' => 'No se puede borrar el producto porque tiene inventario en al menos una sucursal.' . $detalle,
            ]);
        }

        $inventario->delete();

        return redirect()->route('inventario.index')
            ->with('success', 'Producto eliminado correctamente.');
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

    public function cardex(Request $request, Producto $producto)
    {
        [$sucursalId, , $ventasBloqueadas, $motivoBloqueo] = $this->obtenerSucursalYEstado();

        if (!$sucursalId) {
            return response()->json([
                'message' => 'No se encontró una sucursal activa. Selecciona una sucursal antes de continuar.',
            ], 422);
        }

        $this->asegurarAccesoInventario($ventasBloqueadas, $motivoBloqueo);

        $movimientos = AltaInventario::with('usuario:id,name')
            ->where('id_producto', $producto->id)
            ->where('id_sucursal', $sucursalId)
            ->orderByDesc('created_at')
            ->get();

        $primeraId = $movimientos->last()?->id;

        $data = $movimientos->map(function (AltaInventario $movimiento) use ($primeraId) {
            $actual = (float) $movimiento->cantidad_actual;
            $nueva = (float) $movimiento->cantidad_nueva;
            $diferencia = round($nueva - $actual, 2);

            $tipo = $this->resolverTipoMovimiento($movimiento, $primeraId, $diferencia);

            return [
                'id' => $movimiento->id,
                'tipo' => $tipo,
                'cantidad_actual' => $actual,
                'cantidad_movida' => $diferencia,
                'cantidad_resultante' => $nueva,
                'usuario' => $movimiento->usuario?->name ?? 'Desconocido',
                'fecha' => $this->formatearFechaMovimiento($movimiento),
            ];
        });

        return response()->json([
            'producto' => [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
            ],
            'movimientos' => $data,
        ]);
    }

    private function resolverTipoMovimiento(AltaInventario $movimiento, ?int $primeraId, float $diferencia): string
    {
        $tiposPorEvento = [
            AltaInventario::EVENTO_ALTA => 'Alta de inventario',
            AltaInventario::EVENTO_RESETEO => 'Reseteo a cero',
            AltaInventario::EVENTO_VENTA => 'Venta',
            AltaInventario::EVENTO_TRANSFERENCIA_ENTRADA => 'Entrada por transferencia',
            AltaInventario::EVENTO_TRANSFERENCIA_SALIDA => 'Salida por transferencia',
        ];

        $tipoEvento = $movimiento->tipo_evento ?? null;
        if ($tipoEvento && isset($tiposPorEvento[$tipoEvento])) {
            return $tiposPorEvento[$tipoEvento];
        }

        if ($primeraId && $movimiento->id === $primeraId) {
            return 'Creación';
        }

        if ($diferencia > 0) {
            return 'Entrada';
        }

        if ($diferencia < 0) {
            return 'Salida';
        }

        return 'Ajuste';
    }

    private function formatearFechaMovimiento(AltaInventario $movimiento): ?string
    {
        $raw = $movimiento->getRawOriginal('created_at') ?? $movimiento->created_at;

        if (!$raw) {
            return null;
        }

        try {
            return Carbon::parse($raw)->toDateTimeString();
        } catch (\Throwable) {
            return (string) $raw;
        }
    }

    private function obtenerSucursalYEstado(): array
    {
        $sucursalId = SucursalService::getSucursalActiva();

        if (!$sucursalId) {
            return [null, null, false, null];
        }

        $sucursal = Sucursales::withTrashed()->with([
            'empresa' => function ($query) {
                $query->withTrashed();
            }
        ])->find($sucursalId);

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
