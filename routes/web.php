<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/', function() {
    return Inertia::render('Auth/Login');
});


Route::get('/dashboard', [App\Http\Controllers\MainController::class, 'index'])
->name('dashboard')
->middleware('auth:sanctum');
Route::get('/venta/devoluciones', [App\Http\Controllers\DevolucionController::class, 'index'])
    ->name('venta.devolucion.index')
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin']);

Route::get('/venta/{venta}/devolucion', [App\Http\Controllers\DevolucionController::class, 'create'])
    ->name('venta.devolucion.create')
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin'])
    ->whereNumber('venta');

Route::post('/venta/{venta}/devolucion', [App\Http\Controllers\DevolucionController::class, 'store'])
    ->name('venta.devolucion.store')
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin'])
    ->whereNumber('venta');

Route::resource('/venta', App\Http\Controllers\VentaController::class)
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin']);

Route::get('/ticket/{venta}', [App\Http\Controllers\VentaController::class, 'ticket'])
->name("ticket")
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin'])
    ->whereNumber('venta');

Route::resource('/inventario', App\Http\Controllers\ProductoController::class)
->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin']);

Route::post('/inventario/addInventario', [App\Http\Controllers\ProductoController::class, 'addInventario'])
->name('inventario.addInventario')
->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin']);

Route::resource('/solucion', App\Http\Controllers\SolucionEnfermedadController::class)
->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin']);

Route::resource('/clasificacion', App\Http\Controllers\CatClasificacionController::class)
->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin']);

Route::resource('/enfermedad', App\Http\Controllers\CatEnfermedadesController::class)
->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin']);

Route::resource('/tipoFlor', App\Http\Controllers\CatTipoFlorController::class)
->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin']);

Route::resource('/marca', App\Http\Controllers\CatMarcaController::class)
->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin']);

Route::resource('/cliente', \App\Http\Controllers\ClientesController::class)
->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin']);

Route::resource('/empresa', \App\Http\Controllers\EmpresaController::class)
->middleware(['auth:sanctum', 'hasRoles:superAdmin']);

Route::resource('/usuario', \App\Http\Controllers\UsuarioController::class)
->middleware(['auth:sanctum', 'hasRoles:admin-superAdmin']);

Route::resource('/sucursal', \App\Http\Controllers\SucursalController::class)
->middleware(['auth:sanctum', 'hasRoles:admin-superAdmin']);

Route::get('/reporte', [App\Http\Controllers\ReporteController::class, 'index'])
->name("reporte")
->middleware(['auth:sanctum', 'hasRoles:inventario-vendedor-admin-superAdmin']);

Route::get('/reporte/venta', [App\Http\Controllers\ReporteController::class, 'venta'])
->name("reporte.venta")
->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin']);

Route::get('/reporte/inventario', [App\Http\Controllers\ReporteController::class, 'inventario'])
->name("reporte.inventario")
->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin']);

Route::get('/reporte/ventaPorProductoMarca', [App\Http\Controllers\ReporteController::class, 'ventaPorProductoMarca'])
->name("reporte.ventaPorProductoMarca")
->middleware('auth:sanctum', 'hasRoles:vendedor-admin-superAdmin');

// Versiones para impresión térmica (80mm)
Route::get('/reporte/venta-ticket', [App\Http\Controllers\ReporteController::class, 'ventaTicket'])
->name('reporte.ventaTicket')
->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin']);

Route::get('/reporte/inventario-ticket', [App\Http\Controllers\ReporteController::class, 'inventarioTicket'])
->name('reporte.inventarioTicket')
->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin']);

Route::get('/reporte/ventaPorProductoMarca-ticket', [App\Http\Controllers\ReporteController::class, 'ventaPorProductoMarcaTicket'])
->name('reporte.ventaPorProductoMarcaTicket')
->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin']);

Route::resource('/compra', \App\Http\Controllers\ComprasController::class)
->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin']);

Route::get('/facturas/index', [\App\Http\Controllers\FacturaController::class, 'index'])
->name("facturas.index")
->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin']);


Route::put('/facturas/update', [\App\Http\Controllers\FacturaController::class, 'update'])
->name("facturas.update")
->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin']);

Route::get('/pedidos/index', [\App\Http\Controllers\PedidoController::class, 'index'])
    ->name('pedidos.index')
    ->middleware('auth:sanctum');

Route::put('/pedidos/{pedido}/completar', [\App\Http\Controllers\PedidoController::class, 'complete'])
    ->name('pedidos.complete')
    ->middleware('auth:sanctum');
