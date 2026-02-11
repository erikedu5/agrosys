<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Rutas que no requieren verificación de suscripción
     */
    protected $except = [
        'registro',
        'registro.store',
        'login',
        'logout',
        'password/*',
        'suscripcion/*',
        'stripe/webhook',
        'two-factor-challenge',
        'user/two-factor-authentication',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si la ruta está exenta
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        // Verificar si el usuario está autenticado
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Solo aplicar a usuarios de tipo adminEmpresa y empleados
        // Los superAdmin no necesitan verificación
        if ($user->tipo === 'superAdmin') {
            return $next($request);
        }

        // Verificar que tenga empresa asignada
        if (!$user->id_empresa) {
            return $next($request);
        }

        $empresa = $user->empresa;

        if (!$empresa) {
            return $next($request);
        }

        // Verificar si la empresa tiene suscripción activa o trial
        if (!$empresa->hasActiveSubscription()) {
            // Actualizar el bloqueo si no está ya bloqueado
            if (!$empresa->ventas_bloqueadas) {
                $empresa->ventas_bloqueadas = true;
                $empresa->motivo_bloqueo = 'Tu suscripción ha vencido. Por favor, renueva tu suscripción para continuar usando el sistema.';
                $empresa->save();
            }

            // Redirigir a la página de suscripción vencida
            return redirect()->route('suscripcion.expired');
        }

        // Si la suscripción está activa pero el bloqueo está activado, desactivarlo
        if ($empresa->ventas_bloqueadas) {
            $empresa->ventas_bloqueadas = false;
            $empresa->motivo_bloqueo = null;
            $empresa->save();
        }

        return $next($request);
    }
}
