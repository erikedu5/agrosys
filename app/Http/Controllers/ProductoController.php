<?php

namespace App\Http\Controllers;

use App\Models\AltaInventario;
use App\Models\CatClasificacion;
use App\Models\CatMarca;
use App\Models\Producto;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ProductoController extends Controller
{
      /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $productos = Producto::orWhere('nombre', 'LIKE', "%$request->q%")
        ->orWhere('ingrediente_activo','LIKE',"%$request->q%")
        ->latest()
        ->paginate(10);

        foreach ($productos as $producto) {
            $marca = CatMarca::where('id', $producto->id_marca)->first();
            $producto->marca = $marca;
            $altaInventario = AltaInventario::where('id_producto', $producto->id)
            ->where('id_sucursal',  Auth::user()->id_sucursal)
            ->orderBy('created_at', 'desc')->first();
            $producto->cantidad = $altaInventario !== null ?  $altaInventario->cantidad_nueva: "0" ;
        }

        return Inertia::render('Inventario/Inventario', [
             'productos' => $productos
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
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
        $request->validate([
            'nombre' => 'required',
            'id_clasificacion' => 'required',
            'id_marca' => 'required',
            'precio_unitario' => 'required',
            'precio_ieps' => 'required',
            'ieps' => 'required',
            'tamano' => 'required',
        ]);

        $data['id_usuario'] = Auth::user()->id;
        $data['cantidad'] = 0;
        $request->merge($data);
        $producto = Producto::create($request->all());

        $altaInventario = new AltaInventario();
        $altaInventario->cantidad_actual = 0;
        $altaInventario->cantidad_nueva = 0;
        $altaInventario->id_usuario = Auth::user()->id ;
        $altaInventario->id_producto = $producto->id;
        $altaInventario->id_sucursal = Auth::user()->id_sucursal;
        $altaInventario->save();

        if ($request->expectsJson()) {
            $producto->marca = CatMarca::find($producto->id_marca);
            return response()->json($producto);
        }

        return redirect()->route('solucion.index', [
            'id_producto' => $producto->id
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Producto $inventario)
    {
        $altaInventario = AltaInventario::where('id_producto', $inventario->id)
            ->where('id_sucursal',  Auth::user()->id_sucursal)
            ->orderBy('created_at', 'desc')->first();
        $inventario->cantidad = $altaInventario !== null ?  $altaInventario->cantidad_nueva: "0" ;

        $clasificaciones = CatClasificacion::get();
        $marca = CatMarca::get();
        return Inertia::render('Inventario/CreateProducto', [
            'clasificaciones' => $clasificaciones,
            'marcas' => $marca,
            'producto' =>$inventario
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'id_clasificacion' => 'required',
            'id_marca' => 'required',
            'precio_unitario' => 'required',
            'tamano' => 'required',
            'precio_ieps' => 'required',
            'ieps' => 'required',
        ]);

        $catProducto = Producto::find($request->id);
        $catProducto->nombre = $request->nombre;
        $catProducto->id_clasificacion = $request->id_clasificacion;
        $catProducto->id_marca = $request->id_marca;
        $catProducto->precio_unitario = $request->precio_unitario;
        $catProducto->ieps = $request->ieps;
        $catProducto->precio_ieps = $request->precio_ieps;
        $catProducto->tamano = $request->tamano;
        $catProducto->ingrediente_activo = $request->ingrediente_activo;
        $catProducto->id_usuario = Auth::user()->id;
        $catProducto->save();

        return redirect()->route('solucion.index', [
            'id_producto' => $request->id
        ]);
    }

    public function show(Producto $inventario)
    {
        $altaInventario = AltaInventario::where('id_producto', $inventario->id)
            ->where('id_sucursal',  Auth::user()->id_sucursal)
            ->orderBy('created_at', 'desc')->first();
        $inventario->cantidad = $altaInventario !== null ?  $altaInventario->cantidad_nueva: "0" ;
        return Inertia::render('Inventario/AddInventario', [
            'producto' =>$inventario
        ]);
    }

    public function addInventario(Request $request)
    {
        $request->validate(['cantidad' => 'required|numeric|gt:0']);

        $catProducto = Producto::find($request->id);

        $actualStock = AltaInventario::where('id_producto', $request->id)
        ->where('id_sucursal',  Auth::user()->id_sucursal)
        ->orderBy('created_at', 'desc')->first();

        $altaInventario = new AltaInventario();
        $cantidad = $actualStock !== null? $actualStock->cantidad_nueva: 0;
        $altaInventario->cantidad_actual = $cantidad;
        $altaInventario->cantidad_nueva = $cantidad + $request->cantidad;
        $altaInventario->id_usuario = Auth::user()->id ;
        $altaInventario->id_producto = $catProducto->id;
        $altaInventario->id_sucursal = Auth::user()->id_sucursal;
        $altaInventario->save();

        return redirect()->route('inventario.index', [
            'id_producto' => $request->id
        ]);
    }
}
