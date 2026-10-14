<?php

namespace App\Http\Middleware;

use App\Services\SucursalService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureEmpresaSubscription
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Super admins (operadores del sistema) no se bloquean.
        if ($user && $user->tipo === 'superAdmin') {
            return $next($request);
        }

        $empresa = SucursalService::getEmpresaActiva();

        if (!$empresa || $empresa->canAccessApp()) {
            return $next($request);
        }

        $message = 'Suscripcion requerida: tu periodo de prueba ya vencio.';
        if ($empresa->manual_subscription_blocked) {
            $message = 'Acceso bloqueado por administracion. Contacta a soporte para reactivar tu empresa.';
        } elseif ($empresa->hasManualSubscriptionWindow() && !$empresa->hasActiveManualSubscription()) {
            $message = 'Tu suscripcion manual vencio. Contacta a soporte para renovar.';
        }

        // Permitir rutas necesarias aun cuando el trial/suscripcion haya vencido.
        $route = $request->route();
        $isAllowed =
            ($route?->named('subscription.*') ?? false) ||
            ($route?->named('profile.*') ?? false) ||
            ($route?->named('cashier.*') ?? false) ||
            ($route?->named('logout') ?? false) ||
            ($route?->named('manual') ?? false);

        if ($isAllowed) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], 402);
        }

        return redirect()
            ->route('subscription.show')
            ->with('error', $message);
    }
}
