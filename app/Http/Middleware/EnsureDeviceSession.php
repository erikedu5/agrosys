<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureDeviceSession
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('web')->user();
        $version = $user ? $user->newQuery()->whereKey($user->id)->value('device_session_version') : null;
        if ($user && (int) $request->session()->get('device_session_version', 0) !== (int) $version) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return $request->expectsJson()
                ? response()->json(['message' => 'Se cerró esta sesión al entrar desde otro dispositivo. Inicia sesión nuevamente.'], 401)
                : redirect()->route('login');
        }
        if ($user && $request->session()->get('device_session_confirmation_required') && ! $request->isMethod('GET')
            && ! $request->routeIs('device-session.activate', 'logout', 'sucursal.*')) {
            return response()->json(['code' => 'DEVICE_SESSION_CONFIRMATION_REQUIRED', 'message' => 'Confirma el cierre de tus demás sesiones para continuar.'], 409);
        }
        return $next($request);
    }
}
