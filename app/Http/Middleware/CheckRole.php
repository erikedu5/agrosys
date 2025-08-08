<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role): Response
    {
        // Requiere usuario autenticado antes de validar roles
        if (!$request->user()) {
            abort(401, 'No autenticado.');
        }

        $roles = explode('-', $role);
        $hasRole = false;

        foreach ($roles as $rol) {
            if ($request->user()->tipo === $rol) {
                $hasRole = true;
                break;
            }
        }
        if (!$hasRole) {
            abort(403, 'No tienes permiso para acceder a esta página.');
        }
        return $next($request);
    }
}
