<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sucursales;
use Illuminate\Support\Facades\Log;

class SucursalesApiController extends Controller
{
    public function index()
    {
        $sucursales = Sucursales::with('empresa')->get();
        Log::debug(json_encode($sucursales));
        return response()->json($sucursales);
    }
}
