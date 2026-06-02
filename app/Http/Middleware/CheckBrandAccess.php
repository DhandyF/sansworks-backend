<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckBrandAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Allow admin and operator users full access
        if ($user->role === 'admin' || $user->role === 'operator') {
            return $next($request);
        }

        // Only restrict client users
        if ($user->role === 'client') {
            $brandId = $request->route('id');

            if (!$user->hasBrandAccess($brandId)) {
                return response()->json(['message' => 'Access denied to this brand'], 403);
            }
        }

        return $next($request);
    }
}
