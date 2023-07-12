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
        $productos = Producto::where('nombre', 'LIKE', "%$request->q%")
        ->latest()
        ->paginate(10);

        foreach ($productos as $producto) {
            $marca = CatMarca::where('id', $producto->id_marca)->first();
            $producto->marca = $marca;
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
        $request->merge($data);
        $producto = Producto::create($request->all());
        return redirect()->route('solucion.index', [
            'id_producto' => $producto->id
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Producto $inventario)
    { 
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
        $catProducto->cantidad = $request->cantidad;
        $catProducto->precio_unitario = $request->precio_unitario;
        $catProducto->ieps = $request->ieps;
        $catProducto->precio_ieps = $request->precio_ieps;
        $catProducto->tamano = $request->tamano;
        $catProducto->id_usuario = Auth::user()->id;;
        $catProducto->save();

        return redirect()->route('solucion.index', [
            'id_producto' => $request->id
        ]);
    }

    public function show(Producto $inventario)
    { 
        return Inertia::render('Inventario/AddInventario', [
            'producto' =>$inventario
        ]);
    }

    public function addInventario(Request $request) 
    {
        $request->validate(['cantidad' => 'required|numeric|gt:0']);

        $catProducto = Producto::find($request->id);
        
        $altaInventario = new AltaInventario();
        $altaInventario->cantidad_actual = $catProducto->cantidad; 
        $altaInventario->cantidad_nueva = $request->cantidad + $catProducto->cantidad;
        $altaInventario->id_usuario = Auth::user()->id ;
        $altaInventario->id_producto = $catProducto->id;
        $altaInventario->save();

        $catProducto->cantidad = $request->cantidad + $catProducto->cantidad;
        $catProducto->save();

        return redirect()->route('inventario.index', [
            'id_producto' => $request->id
        ]);
    }
}
