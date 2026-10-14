<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $expectedKey = config('services.external_api.key');
        $providedKey = $request->header('X-API-KEY') ?? $request->query('api_key');

        if (!$expectedKey || $providedKey !== $expectedKey) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
