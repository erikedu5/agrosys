<?php

namespace App\Http\Middleware;

use App\Services\Pos\PosAccess;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class PosToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->user()->currentAccessToken();
        abort_unless($token instanceof PersonalAccessToken && $token->can('pos:access') && $token->pos_device_id, 403, 'Se requiere un token POS.');
        app(PosAccess::class)->checkUser($request->user());

        return $next($request);
    }
}
