<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $allowedOrigin = env('APP_FRONTEND_URL');
        $origin = $request->headers->get('origin');
        $referer = $request->headers->get('referer');

        if ($origin && $origin !== $allowedOrigin) {
            return response()->json(['message' => 'Blocked: Invalid origin'], 403);
        }

        if ($referer && !str_starts_with($referer, $allowedOrigin)) {
            return response()->json(['message' => 'Blocked: Invalid referer'], 403);
        }
        
        return $next($request);
    }
}
