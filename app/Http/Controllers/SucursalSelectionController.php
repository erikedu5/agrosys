<?php

namespace App\Http\Controllers;

use App\Models\Sucursales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SucursalSelectionController extends Controller
{
    /**
     * Mostrar la vista de selección de sucursal para administradores de empresa
     */
    public function index()
    {
        $user = Auth::user();

        // Solo permitir acceso a administradores de empresa
        if ($user->tipo !== 'adminEmpresa') {
            return redirect()->route('dashboard');
        }

        // Obtener todas las sucursales de la empresa del usuario
        $sucursales = Sucursales::where('id_empresa', $user->id_empresa)
            ->select('id', 'nombre', 'direccion', 'telefono', 'es_matriz')
            ->get();

        return Inertia::render('SucursalSelection', [
            'sucursales' => $sucursales,
            'empresa' => $user->empresa->nombre ?? 'Mi Empresa'
        ]);
    }
    /**
     * Seleccionar una sucursal específica
     */
    public function select(Request $request)
    {
        $user = Auth::user();

        // Validar que el usuario es administrador de empresa
        if ($user->tipo !== 'adminEmpresa') {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $request->validate([
            'sucursal_id' => 'required|exists:sucursales,id'
        ]);

        $sucursalId = $request->sucursal_id;

        // Verificar que la sucursal pertenece a la empresa del usuario
        $sucursal = Sucursales::where('id', $sucursalId)
            ->where('id_empresa', $user->id_empresa)
            ->first();

        if (!$sucursal) {
            return response()->json(['error' => 'Sucursal no encontrada o no autorizada'], 404);
        }

        // Actualizar la sucursal actual del usuario en la sesión
        session(['sucursal_activa' => $sucursalId]);
        session(['sucursal_nombre' => $sucursal->nombre]);

        return response()->json([
            'success' => true,
            'redirect' => route('dashboard'),
            'message' => "Sucursal '{$sucursal->nombre}' seleccionada correctamente"
        ]);
    }

    /**
     * Obtener la sucursal activa del usuario
     */
    public function getActiveSucursal()
    {
        $user = Auth::user();

        if ($user->tipo === 'superAdmin') {
            // Los super admins pueden ver todas las sucursales
            return $user->id_sucursal;
        }

        if ($user->tipo === 'adminEmpresa') {
            // Los admin de empresa usan la sucursal de la sesión
            return session('sucursal_activa', null);
        }

        // Usuarios normales usan su sucursal asignada
        return $user->id_sucursal;
    }

    /**
     * Cambiar sucursal activa (para admin de empresa)
     */
    public function change(Request $request)
    {
        $user = Auth::user();

        if ($user->tipo !== 'adminEmpresa') {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $request->validate([
            'sucursal_id' => 'required|exists:sucursales,id'
        ]);

        $sucursalId = $request->sucursal_id;

        // Verificar que la sucursal pertenece a la empresa del usuario
        $sucursal = Sucursales::where('id', $sucursalId)
            ->where('id_empresa', $user->id_empresa)
            ->first();

        if (!$sucursal) {
            return response()->json(['error' => 'Sucursal no encontrada'], 404);
        }

        // Actualizar la sesión
        session(['sucursal_activa' => $sucursalId]);
        session(['sucursal_nombre' => $sucursal->nombre]);

        // Si es una petición AJAX/fetch retornamos JSON
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'sucursal' => $sucursal,
                'message' => "Cambiado a sucursal '{$sucursal->nombre}'"
            ]);
        }

        // Si es una petición normal de Inertia, redirigimos
        return redirect()->back()->with('success', "Cambiado a sucursal '{$sucursal->nombre}'");
    }
}
