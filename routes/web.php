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

Route::resource('/venta', App\Http\Controllers\VentaController::class)
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin']);

Route::get('/ticket/{venta}', [App\Http\Controllers\VentaController::class, 'ticket'])
->name("ticket")
->middleware(['auth:sanctum', 'hasRoles:vendedor-admin']);

Route::resource('/inventario', App\Http\Controllers\ProductoController::class)
->middleware(['auth:sanctum', 'hasRoles:inventario-admin']);

Route::post('/inventario/addInventario', [App\Http\Controllers\ProductoController::class, 'addInventario'])
->name('inventario.addInventario')
->middleware(['auth:sanctum', 'hasRoles:inventario-admin']);

Route::resource('/solucion', App\Http\Controllers\SolucionEnfermedadController::class)
->middleware(['auth:sanctum', 'hasRoles:inventario-admin']);

Route::resource('/clasificacion', App\Http\Controllers\CatClasificacionController::class)
->middleware(['auth:sanctum', 'hasRoles:inventario-admin']);

Route::resource('/enfermedad', App\Http\Controllers\CatEnfermedadesController::class)
->middleware(['auth:sanctum', 'hasRoles:inventario-admin']);

Route::resource('/tipoFlor', App\Http\Controllers\CatTipoFlorController::class)
->middleware(['auth:sanctum', 'hasRoles:inventario-admin']);

Route::resource('/marca', App\Http\Controllers\CatMarcaController::class)
->middleware(['auth:sanctum', 'hasRoles:inventario-admin']);

Route::resource('/cliente', \App\Http\Controllers\ClientesController::class)
->middleware(['auth:sanctum', 'hasRoles:vendedor-admin']);

Route::resource('/empresa', \App\Http\Controllers\EmpresaController::class)
->middleware(['auth:sanctum', 'hasRoles:admin']);

Route::resource('/usuario', \App\Http\Controllers\UsuarioController::class)
->middleware(['auth:sanctum', 'hasRoles:admin']);

Route::get('/reporte', [App\Http\Controllers\ReporteController::class, 'index'])
->name("reporte")
->middleware(['auth:sanctum', 'hasRoles:inventario-vendedor-admin']);

Route::get('/reporte/venta', [App\Http\Controllers\ReporteController::class, 'venta'])
->name("reporte.venta")
->middleware(['auth:sanctum', 'hasRoles:vendedor-admin']);

Route::get('/reporte/inventario', [App\Http\Controllers\ReporteController::class, 'inventario'])
->name("reporte.inventario")
->middleware(['auth:sanctum', 'hasRoles:inventario-admin']);

Route::resource('/abono', \App\Http\Controllers\AbonoCuentaController::class)
->middleware(['auth:sanctum', 'hasRoles:vendedor-admin']);