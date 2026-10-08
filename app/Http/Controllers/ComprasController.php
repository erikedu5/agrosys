<?php

namespace App\Http\Controllers;

use App\Models\CatMarca;
use App\Models\CatClasificacion;
use App\Models\Compras;
use App\Models\ComprasAbonos;
use App\Models\ComprasProductos;
use App\Models\Producto;
use App\Services\SucursalService;
use App\Services\PurchaseIdempotency;
use App\Models\Sucursales;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\AltaInventario;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ComprasController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $compras = Compras::where('proveedor', 'LIKE', "%$request->q%")
            ->where('id_sucursal', SucursalService::getSucursalActiva())
            ->latest()
            ->paginate(10);

        return Inertia::render('Inventario/Compra/Compras', [
            'compras' => $compras,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $productos = Producto::where(function ($query) use ($request) {
            $query->where('nombre', 'LIKE', "%$request->q%")
                ->orWhere('barcode', 'LIKE', "%$request->q%");
        })->get();

        foreach ($productos as $product) {
            $product->marca = CatMarca::where('id', $product->id_marca)->first();
        }

        $clasificaciones = CatClasificacion::get();
        $marcas = CatMarca::get();

        return Inertia::render('Inventario/Compra/AddProductoCompra', [
            'productos' => $productos,
            'clasificaciones' => $clasificaciones,
            'marcas' => $marcas,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'idempotency_key' => 'required|uuid',
            'proveedor' => 'required|string|max:255',
            'fecha_compra' => 'required|date',
            'total_compra' => 'required|numeric|min:0|max:99999999.99',
            'status' => 'required|in:pagada,pagado,adeudo,retrasada',
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|integer|exists:productos,id',
            'productos.*.cantidad' => 'required|numeric|min:0.01',
            'productos.*.precio_compra' => 'required|numeric|min:0|max:99999999.99',
            'total_credito' => 'required|numeric|min:0|max:99999999.99',
            'abonos' => 'sometimes|array',
            'abonos.*.cantidad_abonada' => 'required|numeric|min:0.01|max:99999999.99',
        ]);

        $branch = Sucursales::findOrFail(SucursalService::getSucursalActiva());
        $key = strtolower($data['idempotency_key']);
        $hash = PurchaseIdempotency::fingerprint($data, $branch->id);
        if ($existing = PurchaseIdempotency::find($branch->id_empresa, $key, $hash)) {
            return $this->purchaseResponse($request, $existing);
        }

        // Iniciar logging del evento
        $logger = \App\Services\EventLogger::start('PROCESAR_COMPRA', [
            'proveedor' => $request->proveedor,
            'total_compra' => $request->total_compra,
            'num_productos' => count($request->productos),
            'status' => $request->status,
            'sucursal_id' => SucursalService::getSucursalActiva(),
        ]);

        try {
            try {
                $compra = DB::transaction(function () use ($request, $logger, $branch, $key, $hash) {
                    $fecha_compra = Carbon::parse($request->fecha_compra)->format('Y-m-d H:i:s');
                    $fecha_credito = Carbon::parse($fecha_compra)->addDays(30);
                    $compra = Compras::create([
                        'proveedor' => $request->proveedor,
                        'fecha_compra' => $fecha_compra,
                        'total_compra' => $request->total_compra,
                        'status' => $request->status,
                        'fecha_credito' => $fecha_credito,
                        'total_credito' => $request->total_credito,
                        'id_sucursal' => $branch->id,
                        'id_empresa' => $branch->id_empresa,
                        'idempotency_key' => $key,
                        'request_hash' => $hash,
                    ]);

                    $logger->step('Compra creada', [
                        'compra_id' => $compra->id,
                        'fecha' => $fecha_compra,
                        'total' => $request->total_compra,
                    ]);

                    foreach ($request->productos as $index => $producto) {
                        ComprasProductos::create([
                            'id_compra' => $compra->id,
                            'id_producto' => $producto['id'],
                            'cantidad' => $producto['cantidad'],
                            'cantidad_disponible' => $producto['cantidad'],
                            'precio' => $producto['precio_compra']
                        ]);

                        $altaInventarioSaved = AltaInventario::where('id_producto', $producto['id'])
                            ->where('id_sucursal', SucursalService::getSucursalActiva())
                            ->orderBy('created_at', 'desc')->first();

                        $cantidadActual = $altaInventarioSaved?->cantidad_nueva ?? 0;
                        $altaInventario = new AltaInventario();
                        $altaInventario->cantidad_actual = $cantidadActual;
                        $altaInventario->cantidad_nueva = $cantidadActual + $producto['cantidad'];
                        $altaInventario->id_usuario = Auth::user()->id;
                        $altaInventario->id_producto = $producto['id'];
                        $altaInventario->id_sucursal = SucursalService::getSucursalActiva();
                        $altaInventario->tipo_evento = AltaInventario::EVENTO_ALTA;
                        Log::info($altaInventario);
                        $altaInventario->save();

                        $logger->step("Producto {$index} procesado", [
                            'producto_id' => $producto['id'],
                            'cantidad' => $producto['cantidad'],
                            'precio' => $producto['precio_compra'],
                            'stock_anterior' => $cantidadActual,
                            'stock_nuevo' => $altaInventario->cantidad_nueva,
                        ]);
                    }

                    foreach ($request->input('abonos', []) as $abono) {
                        $abono = ComprasAbonos::create([
                            'id_compra' => $compra->id,
                            'cantidad_abonada' => $abono['cantidad_abonada']
                        ]);
                    }

                    $logger->step('Abonos registrados', [
                        'num_abonos' => count($request->input('abonos', [])),
                    ]);

                    return $compra;
                });

            } catch (UniqueConstraintViolationException $e) {
                // A concurrent request may have committed while this insert waited.
                $compra = PurchaseIdempotency::find($branch->id_empresa, $key, $hash);
                if (!$compra) {
                    throw $e;
                }
            }

            $logger->success([
                'compra_id' => $compra->id,
                'total' => $compra->total_compra,
            ]);

            return $this->purchaseResponse($request, $compra);

        } catch (\Exception $e) {
            $logger->error($e, [
                'proveedor' => $request->proveedor,
                'productos' => array_map(fn($p) => [
                    'id' => $p['id'],
                    'cantidad' => $p['cantidad']
                ], $request->productos),
            ]);

            throw $e;
        }
    }


    private function purchaseResponse(Request $request, Compras $purchase)
    {
        $compras = Compras::where('proveedor', 'LIKE', "%$request->q%")
            ->where('id_sucursal', SucursalService::getSucursalActiva())
            ->latest()
            ->paginate(10);

        return Inertia::render('Inventario/Compra/Compras', [
            'compras' => $compras,
            'compra_registrada' => $purchase->id,
        ]);
    }

    public function show(Request $request, $id)
    {
        $compra = Compras::where('id_sucursal', SucursalService::getSucursalActiva())->findOrFail($id);
        $compra_productos = ComprasProductos::where('id_compra', '=', $id)->get();
        $productos_array = [];
        foreach ($compra_productos as $product) {
            $producto_db = Producto::withTrashed()->find($product->id_producto);
            if ($producto_db) {
                if ($producto_db->trashed()) {
                    $producto_db->nombre = $producto_db->nombre . ' (BORRADO)';
                }
                $producto_db->marca = CatMarca::where('id', $producto_db->id_marca)->first();
            } else {
                $producto_db = (object) [
                    'id' => $product->id_producto,
                    'nombre' => 'Producto (BORRADO)',
                    'id_marca' => null,
                    'marca' => (object) ['nombre' => ''],
                ];
            }
            $producto_db->cantidad = $product->cantidad;
            $producto_db->precio_compra = $product->precio;
            array_push($productos_array, $producto_db);
        }
        $compra->productos = $productos_array;
        $compra->abonos = ComprasAbonos::where('id_compra', '=', $id)->get();
        $productos = Producto::where(function ($query) use ($request) {
            $query->where('nombre', 'LIKE', "%$request->q%")
                ->orWhere('barcode', 'LIKE', "%$request->q%");
        })->get();

        foreach ($productos as $product) {
            $product->marca = CatMarca::where('id', $product->id_marca)->first();
        }

        $clasificaciones = CatClasificacion::get();
        $marcas = CatMarca::get();

        return Inertia::render('Inventario/Compra/AddProductoCompra', [
            'productos' => $productos,
            'compra' => $compra,
            'clasificaciones' => $clasificaciones,
            'marcas' => $marcas,
        ]);
    }

    public function update(Request $request, string $compra)
    {
        $request->validate([
            'proveedor' => 'required|string|max:255',
            'fecha_compra' => 'required|date',
            'total_compra' => 'required|numeric|min:0|max:99999999.99',
            'status' => 'required|in:pagada,pagado,adeudo,retrasada',
            'productos' => 'required|array|min:1',
            'total_credito' => 'required|numeric|min:0|max:99999999.99',
            'abonos' => 'sometimes|array',
            'abonos.*.cantidad_abonada' => 'required|numeric|min:0.01|max:99999999.99',
        ]);

        $compra = Compras::where('id_sucursal', SucursalService::getSucursalActiva())->findOrFail($compra);
        DB::transaction(function () use ($request, $compra) {
            $compra->total_credito = $request->total_credito;
            if ($request->total_credito == 0) {
                $compra->status = 'pagado';
            }
            $compra->save();

            foreach ($request->input('abonos', []) as $abono) {
                if (!isset($abono['id'])) {
                    $abono = ComprasAbonos::create([
                        'id_compra' => $compra->id,
                        'cantidad_abonada' => $abono['cantidad_abonada']
                    ]);
                }
            }
        });

        $compras = Compras::where('proveedor', 'LIKE', "%$request->q%")
            ->where('id_sucursal', SucursalService::getSucursalActiva())
            ->latest()
            ->paginate(10);

        return Inertia::render('Inventario/Compra/Compras', [
            'compras' => $compras,
        ]);
    }
}
