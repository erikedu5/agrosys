<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSucursalSelection
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Solo aplicar a usuarios autenticados
        if (!$user) {
            return $next($request);
        }

        // Si es administrador de empresa
        if ($user->tipo === 'adminEmpresa') {
            // Si no hay sucursal en sesión y tampoco tiene sucursal por defecto,
            // redirigir a selección de sucursal
            if (!session('sucursal_activa') && !$user->id_sucursal) {
                // Excluir las rutas de selección de sucursal para evitar bucle infinito
                $excludedRoutes = [
                    'sucursal.selection',
                    'sucursal.select',
                    'sucursal.change',
                    'logout'
                ];

                if (!in_array($request->route()->getName(), $excludedRoutes)) {
                    return redirect()->route('sucursal.selection');
                }
            }
        }

        return $next($request);
    }
}
