<?php

namespace App\Http\Controllers;

use App\Models\CatMarca;
use App\Models\CatClasificacion;
use App\Models\Compras;
use App\Models\ComprasAbonos;
use App\Models\ComprasProductos;
use App\Models\Producto;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\AltaInventario;
use Illuminate\Support\Facades\Log;

class ComprasController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $compras = Compras::where('proveedor', 'LIKE', "%$request->q%")
        ->where('id_sucursal', Auth::user()->id_sucursal)
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
        $productos = Producto::where('nombre', 'LIKE', "%$request->q%")
        ->get();

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
        $request->validate([
            'proveedor' => 'required',
            'fecha_compra' => 'required',
            'total_compra' => 'required',
            'status' => 'required',
            'productos' => 'required',
        ]);
        $fecha_compra= Carbon::parse($request->fecha_compra)->format('Y-m-d H:i:s');
        $fecha_credito =Carbon::parse($fecha_compra)->addDays(30);
        $compra = Compras::create([
            'proveedor' => $request->proveedor,
            'fecha_compra' => $fecha_compra,
            'total_compra' => $request->total_compra,
            'status' => $request->status,
            'fecha_credito' => $fecha_credito,
            'total_credito' => $request->total_credito,
            'id_sucursal' => Auth::user()->id_sucursal
        ]);

        foreach($request->productos as $producto) {
            ComprasProductos::create([
                'id_compra' =>$compra->id,
                'id_producto' => $producto['id'],
                'cantidad' => $producto['cantidad'],
                'precio' => $producto['precio_compra']
            ]);

            $altaInventarioSaved = AltaInventario::where('id_producto', $producto['id'])
            ->where('id_sucursal',  Auth::user()->id_sucursal)
            ->orderBy('created_at', 'desc')->first();

            $altaInventario = new AltaInventario();
            $altaInventario->cantidad_actual = $altaInventarioSaved->cantidad_nueva;
            $altaInventario->cantidad_nueva = $altaInventarioSaved->cantidad_nueva + $producto['cantidad'];
            $altaInventario->id_usuario = Auth::user()->id ;
            $altaInventario->id_producto = $producto['id'];
            $altaInventario->id_sucursal = Auth::user()->id_sucursal;
            Log::info($altaInventario);
            $altaInventario->save();
        }

        foreach($request->abonos as $abono) {
            $abono = ComprasAbonos::create([
                'id_compra' => $compra->id,
                'cantidad_abonada' => $abono['cantidad_abonada']
            ]);
        }

        $compras = Compras::where('proveedor', 'LIKE', "%$request->q%")
        ->where('id_sucursal', Auth::user()->id_sucursal)
        ->latest()
        ->paginate(10);

        return Inertia::render('Inventario/Compra/Compras', [
            'compras' => $compras,
        ]);
    }


    public function show(Request $request, $id)
    {
        $compra = Compras::where('id', $id)->first();
        $compra_productos = ComprasProductos::where('id_compra','=',$id)->get();
        $productos_array = [];
        foreach ($compra_productos as $product) {
            $producto_db = Producto::find($product->id_producto);
            $producto_db->cantidad = $product->cantidad;
            $producto_db->precio_compra = $product->precio;
            $producto_db->marca = CatMarca::where('id', $producto_db->id_marca)->first();
            array_push($productos_array, $producto_db);
        }
        $compra->productos = $productos_array;
        $compra->abonos = ComprasAbonos::where('id_compra','=',$id)->get();
        $productos = Producto::where('nombre', 'LIKE', "%$request->q%")
        ->get();

        foreach ($productos as $product) {
            $product->marca = CatMarca::where('id', $product->id_marca)->first();
        }

        $clasificaciones = CatClasificacion::get();
        $marcas = CatMarca::get();

        return Inertia::render('Inventario/Compra/AddProductoCompra', [
            'productos' => $productos,
            'compra'  => $compra,
            'clasificaciones' => $clasificaciones,
            'marcas' => $marcas,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'proveedor' => 'required',
            'fecha_compra' => 'required',
            'total_compra' => 'required',
            'status' => 'required',
            'productos' => 'required',
            'abonos' => 'required'
        ]);

        $compra = Compras::find(intval($request->id));
        $compra->total_credito = $request->total_credito;
        if ($request->total_credito == 0) {
            $compra->status = 'pagado';
        }
        $compra->save();

        if (!$compra) {
            return  redirect()->route('compras.index')->with(['error'=>'No existe compra que intenta actualizar']);
        }

        foreach($request->abonos as $abono) {
            if (!isset($abono['id'])) {
                $abono = ComprasAbonos::create([
                    'id_compra' => $request->id,
                    'cantidad_abonada' => $abono['cantidad_abonada']
                ]);
            }
        }

        $compras = Compras::where('proveedor', 'LIKE', "%$request->q%")
        ->where('id_sucursal', Auth::user()->id_sucursal)
        ->latest()
        ->paginate(10);

        return Inertia::render('Inventario/Compra/Compras', [
            'compras' => $compras,
        ]);
    }

}
