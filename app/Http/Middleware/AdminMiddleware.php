<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required. Please login to continue.'
            ], 401);
        }

        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Administrator privileges required.'
            ], 403);
        }

        return $next($request);
    }
}
