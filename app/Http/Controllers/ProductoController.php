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
use Illuminate\Validation\Rule;

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
