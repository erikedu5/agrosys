<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\TransferenciaController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\Subscription\OnboardingController;
use App\Http\Controllers\Subscription\PlanController;
use App\Http\Controllers\Subscription\SubscriptionController;

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


Route::get('/', function () {
    return Inertia::render('Welcome', [
        'desktopDownloads' => [
            'macos' => is_file(public_path('downloads/AgroSys-POS-1.0.0-macOS.dmg')),
            'windows' => is_file(public_path('downloads/AgroSys-POS-Setup.exe')),
        ],
    ]);
});

Route::get('/api/v1/offline/health', [App\Http\Controllers\OfflineController::class, 'health'])
    ->name('offline.health');

Route::get('/api/v1/offline/bootstrap', [App\Http\Controllers\OfflineController::class, 'bootstrap'])
    ->middleware(['auth:sanctum', 'sucursal.selection'])
    ->name('offline.bootstrap');

Route::middleware(['auth:sanctum', 'sucursal.selection'])->prefix('/api/v1/offline')->group(function () {
    Route::post('/sync/push', [App\Http\Controllers\OfflineController::class, 'push'])->name('offline.push');
    Route::get('/sync/pull', [App\Http\Controllers\OfflineController::class, 'pull'])->name('offline.pull');
    Route::get('/operations/{operationId}', [App\Http\Controllers\OfflineController::class, 'operation'])->whereUuid('operationId')->name('offline.operation');
    Route::post('/device/heartbeat', [App\Http\Controllers\OfflineController::class, 'heartbeat'])->name('offline.heartbeat');
});

// Public: planes y legales
Route::get('/planes', [PlanController::class, 'index'])->name('plans.index');
Route::get('/legal/terminos', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/legal/privacidad', [LegalController::class, 'privacy'])->name('legal.privacy');

// Onboarding (trial sin tarjeta): GET accesible aun estando autenticado para mostrar el Paso 2 en la misma pagina.
Route::get('/suscribirse', [OnboardingController::class, 'create'])->name('subscription.onboarding');
Route::post('/suscribirse', [OnboardingController::class, 'store'])
    ->middleware(['guest'])
    ->name('subscription.onboarding.store');

// Auth: administrar suscripcion
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/cuenta/suscripcion', [SubscriptionController::class, 'show'])->name('subscription.show');
    Route::post('/cuenta/suscripcion/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::post('/cuenta/suscripcion/portal', [SubscriptionController::class, 'portal'])->name('subscription.portal');
});


Route::get('/manual', function () {
    return Inertia::render('Manual');
})->middleware('auth:sanctum')->name('manual');

// Rutas para selección de sucursal (Admin de Empresa)
Route::middleware(['auth'])->group(function () {
    Route::get('/sucursal/selection', [App\Http\Controllers\SucursalSelectionController::class, 'index'])
        ->name('sucursal.selection');
    Route::post('/sucursal/select', [App\Http\Controllers\SucursalSelectionController::class, 'select'])
        ->name('sucursal.select');
    Route::post('/sucursal/change', [App\Http\Controllers\SucursalSelectionController::class, 'change'])
        ->name('sucursal.change');
});

Route::get('/dashboard', [App\Http\Controllers\MainController::class, 'index'])
    ->name('dashboard')
    ->middleware(['auth:sanctum', 'sucursal.selection']);
Route::get('/venta/devoluciones/index', [App\Http\Controllers\DevolucionesController::class, 'index'])
    ->name('venta.devoluciones.index')
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin-adminEmpresa', 'sucursal.selection']);

Route::get('/devoluciones/list', [App\Http\Controllers\DevolucionesController::class, 'list'])
    ->name('devoluciones.list')
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin-adminEmpresa', 'sucursal.selection']);

Route::get('/venta/{venta}/devoluciones', [App\Http\Controllers\DevolucionesController::class, 'create'])
    ->name('venta.devoluciones.create')
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin-adminEmpresa', 'sucursal.selection'])
    ->whereNumber('venta');

Route::post('/venta/{venta}/devoluciones', [App\Http\Controllers\DevolucionesController::class, 'store'])
    ->name('venta.devoluciones.store')
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin-adminEmpresa', 'sucursal.selection'])
    ->whereNumber('venta');

Route::resource('/venta', App\Http\Controllers\VentaController::class)
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin-adminEmpresa', 'sucursal.selection']);

Route::get('/ticket/{venta}', [App\Http\Controllers\VentaController::class, 'ticket'])
    ->name("ticket")
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin-adminEmpresa', 'sucursal.selection'])
    ->whereNumber('venta');

Route::get('/ticket/print/{venta}', [App\Http\Controllers\VentaController::class, 'ticketHtml'])
    ->name('venta.ticket.html')
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin-adminEmpresa', 'sucursal.selection']);

Route::get('/ticket/print-last', [App\Http\Controllers\VentaController::class, 'ultimoTicketPorSucursal'])
    ->name('venta.ticket.last')
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin-adminEmpresa', 'sucursal.selection']);

Route::get('/buscar-precio', [App\Http\Controllers\VentaController::class, 'buscarPrecio'])
    ->name('venta.buscar.precio')
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin-adminEmpresa', 'sucursal.selection']);


Route::resource('/inventario', App\Http\Controllers\ProductoController::class)
    ->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin-adminEmpresa', 'sucursal.selection']);

Route::post('/inventario/addInventario', [App\Http\Controllers\ProductoController::class, 'addInventario'])
    ->name('inventario.addInventario')
    ->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin-adminEmpresa', 'sucursal.selection']);

Route::post('/inventario/{producto}/reset', [App\Http\Controllers\ProductoController::class, 'resetInventario'])
    ->name('inventario.reset')
    ->middleware(['auth:sanctum', 'hasRoles:adminEmpresa', 'sucursal.selection'])
    ->whereNumber('producto');

Route::put('/inventario/{producto}/precios', [App\Http\Controllers\ProductoController::class, 'updatePrecios'])
    ->name('inventario.updatePrecios')
    ->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin-adminEmpresa', 'sucursal.selection'])
    ->whereNumber('producto');

Route::get('/inventario/{producto}/cardex', [App\Http\Controllers\ProductoController::class, 'cardex'])
    ->name('inventario.cardex')
    ->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin-adminEmpresa', 'sucursal.selection'])
    ->whereNumber('producto');

Route::resource('/solucion', App\Http\Controllers\SolucionEnfermedadController::class)
    ->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin-adminEmpresa', 'sucursal.selection']);

Route::resource('/clasificacion', App\Http\Controllers\CatClasificacionController::class)
    ->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin-adminEmpresa']);

Route::resource('/enfermedad', App\Http\Controllers\CatEnfermedadesController::class)
    ->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin-adminEmpresa']);

Route::resource('/tipoFlor', App\Http\Controllers\CatTipoFlorController::class)
    ->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin-adminEmpresa']);

Route::resource('/marca', App\Http\Controllers\CatMarcaController::class)
    ->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin-adminEmpresa']);

Route::resource('/cliente', \App\Http\Controllers\ClientesController::class)
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin-adminEmpresa']);

Route::resource('/empresa', \App\Http\Controllers\EmpresaController::class)
    ->middleware(['auth:sanctum', 'hasRoles:superAdmin-adminEmpresa']);

Route::get('/empresa/suscripciones/panel', [EmpresaController::class, 'subscriptions'])
    ->name('empresa.subscriptions.index')
    ->middleware(['auth:sanctum', 'hasRoles:superAdmin']);

Route::put('/empresa/{empresa}/suscripcion-manual', [EmpresaController::class, 'updateSubscription'])
    ->name('empresa.subscriptions.update')
    ->middleware(['auth:sanctum', 'hasRoles:superAdmin']);

Route::put('/empresa/{id}/restore', [\App\Http\Controllers\EmpresaController::class, 'restore'])
    ->name('empresa.restore')
    ->middleware(['auth:sanctum', 'hasRoles:superAdmin']);

Route::resource('/usuario', \App\Http\Controllers\UsuarioController::class)
    ->middleware(['auth:sanctum', 'hasRoles:admin-superAdmin-adminEmpresa']);

Route::put('/usuario/{id}/restore', [\App\Http\Controllers\UsuarioController::class, 'restore'])
    ->name('usuario.restore')
    ->middleware(['auth:sanctum', 'hasRoles:admin-superAdmin-adminEmpresa']);

Route::resource('/sucursal', \App\Http\Controllers\SucursalController::class)
    ->middleware(['auth:sanctum', 'hasRoles:admin-superAdmin-adminEmpresa']);

Route::put('/sucursal/{id}/restore', [\App\Http\Controllers\SucursalController::class, 'restore'])
    ->name('sucursal.restore')
    ->middleware(['auth:sanctum', 'hasRoles:admin-superAdmin-adminEmpresa']);

Route::get('/reporte', [App\Http\Controllers\ReporteController::class, 'index'])
    ->name("reporte")
    ->middleware(['auth:sanctum', 'hasRoles:inventario-vendedor-admin-superAdmin-adminEmpresa']);

Route::get('/reporte/venta', [App\Http\Controllers\ReporteController::class, 'venta'])
    ->name("reporte.venta")
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin-adminEmpresa']);

Route::get('/reporte/ventas-dia', [App\Http\Controllers\ReporteController::class, 'ventasDia'])
    ->name('reporte.ventasDia')
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin-adminEmpresa']);

Route::get('/reporte/inventario', [App\Http\Controllers\ReporteController::class, 'inventario'])
    ->name("reporte.inventario")
    ->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin-adminEmpresa']);

Route::get('/reporte/clientes-adeudo', [App\Http\Controllers\ReporteController::class, 'clientesAdeudo'])
    ->name('reporte.clientesAdeudo')
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin-adminEmpresa']);

Route::get('/reporte/ventaPorProductoMarca', [App\Http\Controllers\ReporteController::class, 'ventaPorProductoMarca'])
    ->name("reporte.ventaPorProductoMarca")
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin-adminEmpresa']);

Route::get('/reporte/ganancias-diarias', [App\Http\Controllers\ReporteController::class, 'gananciasDiarias'])
    ->name('reporte.gananciasDiarias')
    ->middleware(['auth:sanctum', 'hasRoles:adminEmpresa-superAdmin']);

// Versiones para impresión térmica (80mm)
Route::get('/reporte/venta-ticket', [App\Http\Controllers\ReporteController::class, 'ventaTicket'])
    ->name('reporte.ventaTicket')
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin-adminEmpresa']);

Route::get('/reporte/inventario-ticket', [App\Http\Controllers\ReporteController::class, 'inventarioTicket'])
    ->name('reporte.inventarioTicket')
    ->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin-adminEmpresa']);

Route::get('/reporte/aumentos-inventario', [App\Http\Controllers\ReporteController::class, 'reporteAumentosInventario'])
    ->name('reporte.aumentosInventario')
    ->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin-adminEmpresa']);

Route::get('/reporte/ventaPorProductoMarca-ticket', [App\Http\Controllers\ReporteController::class, 'ventaPorProductoMarcaTicket'])
    ->name('reporte.ventaPorProductoMarcaTicket')
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin-adminEmpresa']);

Route::resource('/compra', \App\Http\Controllers\ComprasController::class)
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin-adminEmpresa']);

Route::get('/transferencias/buscar-productos', [TransferenciaController::class, 'buscarProductos'])
    ->name('transferencias.buscarProductos')
    ->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin-adminEmpresa']);

Route::get('/transferencias/{id}/recibo', [TransferenciaController::class, 'generarRecibo'])
    ->name('transferencias.recibo')
    ->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin-adminEmpresa']);

Route::resource('/transferencias', TransferenciaController::class)
    ->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin-adminEmpresa']);

Route::post('/transferencias/{id}/recibir', [TransferenciaController::class, 'recibir'])
    ->name('transferencias.recibir')
    ->middleware(['auth:sanctum', 'hasRoles:inventario-admin-superAdmin-adminEmpresa']);

Route::get('/facturas/index', [\App\Http\Controllers\FacturaController::class, 'index'])
    ->name("facturas.index")
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin-adminEmpresa']);


Route::put('/facturas/update', [\App\Http\Controllers\FacturaController::class, 'update'])
    ->name("facturas.update")
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin-adminEmpresa']);

Route::get('/pedidos/index', [\App\Http\Controllers\PedidoController::class, 'index'])
    ->name('pedidos.index')
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin-adminEmpresa']);

Route::put('/pedidos/{pedido}/completar', [\App\Http\Controllers\PedidoController::class, 'complete'])
    ->name('pedidos.complete')
    ->middleware(['auth:sanctum', 'hasRoles:vendedor-admin-superAdmin-adminEmpresa']);
