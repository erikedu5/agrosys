<?php

namespace App\Services;

use App\Models\Sucursales;
use Illuminate\Support\Facades\Auth;

class SucursalService
{
    /**
     * Obtener la sucursal activa para el usuario actual
     */
    public static function getSucursalActiva()
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        // Super administradores usan su sucursal asignada
        if ($user->tipo === 'superAdmin') {
            return $user->id_sucursal;
        }

        // Administradores de empresa usan la sucursal de la sesión, 
        // o su sucursal por defecto si no hay una seleccionada
        if ($user->tipo === 'adminEmpresa') {
            return session('sucursal_activa', $user->id_sucursal);
        }

        // Usuarios normales (empleados, vendedores) usan su sucursal asignada
        return $user->id_sucursal;
    }

    /**
     * Obtener el objeto completo de la sucursal activa
     */
    public static function getSucursalActivaCompleta()
    {
        $sucursalId = self::getSucursalActiva();

        if (!$sucursalId) {
            return null;
        }

        return Sucursales::find($sucursalId);
    }

    /**
     * Obtener todas las sucursales que puede ver el usuario
     */
    public static function getSucursalesDisponibles()
    {
        $user = Auth::user();

        if (!$user) {
            return collect();
        }

        // Super administradores ven todas las sucursales
        if ($user->tipo === 'superAdmin') {
            return Sucursales::all();
        }

        // Administradores de empresa ven solo las de su empresa
        if ($user->tipo === 'adminEmpresa' && $user->id_empresa) {
            return Sucursales::where('id_empresa', $user->id_empresa)->get();
        }

        // Usuarios normales solo ven su sucursal
        return Sucursales::where('id', $user->id_sucursal)->get();
    }

    /**
     * Verificar si el usuario puede acceder a una sucursal específica
     */
    public static function puedeAccederSucursal($sucursalId)
    {
        $user = Auth::user();

        if (!$user || !$sucursalId) {
            return false;
        }

        // Super administradores pueden acceder a cualquier sucursal
        if ($user->tipo === 'superAdmin') {
            return true;
        }

        // Administradores de empresa solo pueden acceder a sucursales de su empresa
        if ($user->tipo === 'adminEmpresa' && $user->id_empresa) {
            $sucursal = Sucursales::find($sucursalId);
            return $sucursal && $sucursal->id_empresa === $user->id_empresa;
        }

        // Usuarios normales solo pueden acceder a su sucursal asignada
        return $user->id_sucursal === $sucursalId;
    }

    /**
     * Obtener nombre de la sucursal activa
     */
    public static function getNombreSucursalActiva()
    {
        $user = Auth::user();

        if (!$user) {
            return '';
        }

        if ($user->tipo === 'adminEmpresa') {
            return session('sucursal_nombre', 'Sin sucursal seleccionada');
        }

        $sucursal = self::getSucursalActivaCompleta();
        return $sucursal ? $sucursal->nombre : '';
    }

    /**
     * Aplicar filtro de sucursal a una consulta
     */
    public static function aplicarFiltroSucursal($query, $campoSucursal = 'id_sucursal')
    {
        $sucursalActiva = self::getSucursalActiva();

        if ($sucursalActiva) {
            return $query->where($campoSucursal, $sucursalActiva);
        }

        return $query;
    }
}
