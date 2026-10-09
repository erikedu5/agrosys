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
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

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
        $logger = \App\Services\EventLogger::start('PROCESAR_COMPRA', ['proveedor' => $data['proveedor'],
            'total_compra' => $data['total_compra'], 'num_productos' => count($data['productos']), 'sucursal_id' => $branch->id]);
        try {
            $purchase = app(\App\Services\PurchaseManagement::class)->record($request->user(), $branch, $data);
            $logger->success(['compra_id' => $purchase->id, 'total' => $purchase->total_compra]);
            return $this->purchaseResponse($request, $purchase);
        } catch (\Throwable $e) {
            $logger->error($e);
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
            'expected_debt' => 'sometimes|numeric|min:0|max:99999999.99',
            'abonos' => 'sometimes|array',
            'abonos.*.id' => 'sometimes|integer',
            'abonos.*.cantidad_abonada' => 'required|numeric|min:0.01|max:99999999.99',
        ]);

        app(\App\Services\PurchaseManagement::class)->webPayments(
            Sucursales::findOrFail(SucursalService::getSucursalActiva()), (int) $compra, $request->all());

        $compras = Compras::where('proveedor', 'LIKE', "%$request->q%")
            ->where('id_sucursal', SucursalService::getSucursalActiva())
            ->latest()
            ->paginate(10);

        return Inertia::render('Inventario/Compra/Compras', [
            'compras' => $compras,
        ]);
    }
}
