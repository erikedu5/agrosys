<?php

namespace App\Http\Controllers;

use App\Models\AltaInventario;
use App\Models\CatClasificacion;
use App\Models\CatMarca;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Services\SucursalService;
use App\Services\InventoryManagement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
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
        [$sucursalId, , $bloqueado, $motivo] = $this->obtenerSucursalYEstado();
        $this->asegurarAccesoInventario($bloqueado, $motivo);
        $branch = Sucursales::findOrFail($sucursalId);
        $producto = app(InventoryManagement::class)->product($request->user(), $branch, $request->all());
        if ($request->expectsJson()) {
            $producto->marca = CatMarca::find($producto->id_marca);
            return response()->json($producto);
        }
        return redirect()->route('solucion.index', ['id_producto' => $producto->id, 'from_product_creation' => 'true']);
    }

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
    public function update(Request $request, Producto $inventario)
    {
        [$sucursalId, , $bloqueado, $motivo] = $this->obtenerSucursalYEstado();
        $this->asegurarAccesoInventario($bloqueado, $motivo);
        app(InventoryManagement::class)->product($request->user(), Sucursales::findOrFail($sucursalId), $request->all(), $inventario->id);
        return redirect()->route('solucion.index', ['id_producto' => $inventario->id]);
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
        [$sucursalId, , $bloqueado, $motivo] = $this->obtenerSucursalYEstado();
        $this->asegurarAccesoInventario($bloqueado, $motivo);
        $request->validate(['id' => 'required|integer|min:1', 'cantidad' => 'required|numeric|gt:0']);
        $logger = \App\Services\EventLogger::start('AGREGAR_INVENTARIO', ['producto_id' => $request->id, 'cantidad_agregar' => $request->cantidad, 'sucursal_id' => $sucursalId]);
        try {
            $movement = app(InventoryManagement::class)->addStock($request->user(), Sucursales::findOrFail($sucursalId), (int) $request->id, $request->cantidad);
            $logger->success(['stock_anterior' => $movement->cantidad_actual, 'stock_nuevo' => $movement->cantidad_nueva]);
            return redirect()->route('inventario.index', ['id_producto' => $request->id]);
        } catch (\Exception $e) {
            $logger->error($e, ['producto_id' => $request->id]);
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
            $movement = app(InventoryManagement::class)->destructive(Auth::user(), Sucursales::findOrFail($sucursalId), $producto->id, 'reset');
            $logger->success(['stock_anterior' => $movement->cantidad_actual, 'stock_nuevo' => 0]);

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

        app(InventoryManagement::class)->destructive(Auth::user(), Sucursales::findOrFail($sucursalId), $inventario->id, 'delete');

        return redirect()->route('inventario.index')
            ->with('success', 'Producto eliminado correctamente.');
    }

    public function updatePrecios(Request $request, Producto $producto)
    {
        [$sucursalId, , $bloqueado, $motivo] = $this->obtenerSucursalYEstado();
        $this->asegurarAccesoInventario($bloqueado, $motivo);
        app(InventoryManagement::class)->updatePrices($request->user(), Sucursales::findOrFail($sucursalId), $producto->id, $request->all());
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

}
