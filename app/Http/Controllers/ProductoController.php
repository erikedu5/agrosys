<?php

namespace App\Http\Controllers;

use App\Models\AltaInventario;
use App\Models\CatClasificacion;
use App\Models\CatMarca;
use App\Models\Producto;
use App\Services\SucursalService;
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
        $sucursal = SucursalService::getSucursalActiva();

        $productos = Producto::orWhere('nombre', 'LIKE', "%$request->q%")
            ->orWhere('barcode', 'LIKE', "%$request->q%")
            ->orWhere('ingrediente_activo', 'LIKE', "%$request->q%")
            ->latest()
            ->paginate(10);

        foreach ($productos as $producto) {
            $marca = CatMarca::where('id', $producto->id_marca)->first();
            $producto->marca = $marca;
            $altaInventario = AltaInventario::where('id_producto', $producto->id)
                ->where('id_sucursal', $sucursal)
                ->orderBy('created_at', 'desc')->first();
            $producto->cantidad = $altaInventario !== null ? $altaInventario->cantidad_nueva : "0";
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
        $sucursal = SucursalService::getSucursalActiva();

        $request->validate(
            [
                'nombre' => 'required|string|max:255',
                'id_clasificacion' => 'required|integer|min:1',
                'id_marca' => 'required|integer|min:1',
                'precio_unitario' => 'required|numeric|min:0.01',
                'precio_ieps' => 'required|numeric|min:0.01',
                'ieps' => 'required|numeric|min:0',
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
                'precio_unitario.required' => 'El precio de compra es requerido.',
                'precio_unitario.numeric' => 'El precio de compra debe ser un número.',
                'precio_unitario.min' => 'El precio de compra debe ser mayor a 0.',
                'precio_ieps.required' => 'El precio con IEPS es requerido.',
                'precio_ieps.numeric' => 'El precio con IEPS debe ser un número.',
                'precio_ieps.min' => 'El precio con IEPS debe ser mayor a 0.',
                'ieps.required' => 'Debe ingresar un valor de IEPS.',
                'ieps.numeric' => 'El IEPS debe ser un número válido.',
                'ieps.min' => 'El IEPS debe ser mayor o igual a 0.',
                'tamano.required' => 'El tamaño del producto es requerido.',
                'tamano.string' => 'El tamaño debe ser un texto válido.',
                'tamano.max' => 'El tamaño no puede exceder 100 caracteres.',
            ]
        );

        $data['id_usuario'] = Auth::user()->id;
        $data['cantidad'] = 0;
        $request->merge($data);
        $producto = Producto::create($request->all());

        $altaInventario = new AltaInventario();
        $altaInventario->cantidad_actual = 0;
        $altaInventario->cantidad_nueva = 0;
        $altaInventario->id_usuario = Auth::user()->id;
        $altaInventario->id_producto = $producto->id;
        $altaInventario->id_sucursal = $sucursal;
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
        $sucursal = SucursalService::getSucursalActiva();

        $altaInventario = AltaInventario::where('id_producto', $inventario->id)
            ->where('id_sucursal', $sucursal)
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
        $request->validate(
            [
                'nombre' => 'required|string|max:255',
                'id_clasificacion' => 'required|integer|min:1',
                'id_marca' => 'required|integer|min:1',
                'precio_unitario' => 'required|numeric|min:0.01',
                'tamano' => 'required|string|max:100',
                'precio_ieps' => 'required|numeric|min:0.01',
                'ieps' => 'required|numeric|min:0',
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
                'precio_unitario.required' => 'El precio de compra es requerido.',
                'precio_unitario.numeric' => 'El precio de compra debe ser un número.',
                'precio_unitario.min' => 'El precio de compra debe ser mayor a 0.',
                'precio_ieps.required' => 'El precio con IEPS es requerido.',
                'precio_ieps.numeric' => 'El precio con IEPS debe ser un número.',
                'precio_ieps.min' => 'El precio con IEPS debe ser mayor a 0.',
                'ieps.required' => 'Debe ingresar un valor de IEPS.',
                'ieps.numeric' => 'El IEPS debe ser un número válido.',
                'ieps.min' => 'El IEPS debe ser mayor o igual a 0.',
                'tamano.required' => 'El tamaño del producto es requerido.',
                'tamano.string' => 'El tamaño debe ser un texto válido.',
                'tamano.max' => 'El tamaño no puede exceder 100 caracteres.',
            ]
        );

        $catProducto = Producto::find($request->id);
        $catProducto->nombre = $request->nombre;
        $catProducto->id_clasificacion = $request->id_clasificacion;
        $catProducto->id_marca = $request->id_marca;
        $catProducto->precio_unitario = $request->precio_unitario;
        $catProducto->ieps = $request->ieps;
        $catProducto->precio_ieps = $request->precio_ieps;
        $catProducto->tamano = $request->tamano;
        $catProducto->ingrediente_activo = $request->ingrediente_activo;
        $catProducto->barcode = $request->barcode;
        $catProducto->id_usuario = Auth::user()->id;
        $catProducto->save();

        return redirect()->route('solucion.index', [
            'id_producto' => $request->id
        ]);
    }

    public function show(Producto $inventario)
    {
        $sucursal = SucursalService::getSucursalActiva();

        $altaInventario = AltaInventario::where('id_producto', $inventario->id)
            ->where('id_sucursal', $sucursal)
            ->orderBy('created_at', 'desc')->first();
        $inventario->cantidad = $altaInventario !== null ? $altaInventario->cantidad_nueva : "0";
        return Inertia::render('Inventario/AddInventario', [
            'producto' => $inventario
        ]);
    }

    public function addInventario(Request $request)
    {
        $sucursal = SucursalService::getSucursalActiva();

        $request->validate(['cantidad' => 'required|numeric|gt:0']);

        $catProducto = Producto::find($request->id);

        $actualStock = AltaInventario::where('id_producto', $request->id)
            ->where('id_sucursal', $sucursal)
            ->orderBy('created_at', 'desc')->first();

        $altaInventario = new AltaInventario();
        $cantidad = $actualStock !== null ? $actualStock->cantidad_nueva : 0;
        $altaInventario->cantidad_actual = $cantidad;
        $altaInventario->cantidad_nueva = $cantidad + $request->cantidad;
        $altaInventario->id_usuario = Auth::user()->id;
        $altaInventario->id_producto = $catProducto->id;
        $altaInventario->id_sucursal = $sucursal;
        $altaInventario->save();

        return redirect()->route('inventario.index', [
            'id_producto' => $request->id
        ]);
    }

    public function resetInventario(Producto $producto)
    {
        $sucursal = SucursalService::getSucursalActiva();

        $actualStock = AltaInventario::where('id_producto', $producto->id)
            ->where('id_sucursal', $sucursal)
            ->orderBy('created_at', 'desc')
            ->first();

        $cantidad = $actualStock !== null ? $actualStock->cantidad_nueva : 0;

        $altaInventario = new AltaInventario();
        $altaInventario->cantidad_actual = $cantidad;
        $altaInventario->cantidad_nueva = 0;
        $altaInventario->id_usuario = Auth::user()->id;
        $altaInventario->id_producto = $producto->id;
        $altaInventario->id_sucursal = $sucursal;
        $altaInventario->save();

        return redirect()->route('inventario.index');
    }
}
