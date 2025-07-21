<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sucursales;

class SucursalesApiController extends Controller
{
    public function index()
    {
        $sucursales = Sucursales::with('empresa')->get();
        return response()->json($sucursales);
    }
}
