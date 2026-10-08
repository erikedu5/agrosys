<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

use App\Http\Controllers\Api\SucursalesApiController;
use App\Http\Controllers\Api\PedidoController as ApiPedidoController;
use App\Http\Controllers\Api\SucursalProductosController;

Route::middleware('api.key')->get('/sucursales', [SucursalesApiController::class, 'index']);

Route::middleware('api.key')->get('/sucursales/{sucursal}/productos', [SucursalProductosController::class, 'index']);

Route::post('/pedidos', [ApiPedidoController::class, 'store']);


Route::prefix('v1/pos')->name('pos.')->middleware(\App\Http\Middleware\PosApi::class)->group(function () {
    Route::get('/health', function () {
        \Illuminate\Support\Facades\DB::select('select 1');
        return response()->json(['status' => 'ok', 'serverTime' => now()->toIso8601String()]);
    })->name('health');
    Route::post('/auth/login', [\App\Http\Controllers\Api\PosAuthController::class, 'login'])->middleware('throttle:pos-login')->name('login');
    Route::post('/auth/verify-2fa', [\App\Http\Controllers\Api\PosAuthController::class, 'verify'])->middleware('throttle:pos-2fa')->name('verify');
    Route::middleware(['auth:sanctum', \App\Http\Middleware\PosToken::class])->group(function () {
        Route::post('/auth/context', [\App\Http\Controllers\Api\PosAuthController::class, 'context'])->name('context');
        Route::post('/auth/logout', [\App\Http\Controllers\Api\PosAuthController::class, 'logout'])->name('logout');
        Route::get('/branches', [\App\Http\Controllers\Api\PosDeviceController::class, 'branches'])->name('branches');
        Route::post('/devices/activate', [\App\Http\Controllers\Api\PosDeviceController::class, 'activate'])->name('activate');
        Route::post('/devices/renew', [\App\Http\Controllers\Api\PosDeviceController::class, 'renew'])->name('renew');
        Route::post('/devices/revoke', [\App\Http\Controllers\Api\PosDeviceController::class, 'revoke'])->name('revoke');
        Route::post('/device/heartbeat', [\App\Http\Controllers\Api\PosDeviceController::class, 'heartbeat'])->name('heartbeat');
        Route::post('/sync/push', [\App\Http\Controllers\Api\PosSyncController::class, 'push'])->name('push');
        Route::get('/operations/{operationId}', [\App\Http\Controllers\Api\PosSyncController::class, 'operation'])->whereUuid('operationId')->name('operation');
        Route::post('/bootstrap', [\App\Http\Controllers\Api\PosCatalogController::class, 'bootstrap'])->name('bootstrap');
        Route::post('/sync/pull', [\App\Http\Controllers\Api\PosCatalogController::class, 'pull'])->name('pull');
    });
});
