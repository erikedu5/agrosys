<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PosApi
{
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');
        abort_unless(config('pos.enabled'), 404);

        return $next($request)->header('Cache-Control', 'no-store');
    }
}
