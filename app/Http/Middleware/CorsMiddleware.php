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
        $configuredOrigin = env('APP_FRONTEND_URL');
        $requestOrigin = $request->getSchemeAndHttpHost();
        $origin = $request->headers->get('origin');
        $referer = $request->headers->get('referer');
        $allowedOrigins = array_filter([
            $configuredOrigin,
            $requestOrigin,
        ]);

        if ($origin && !in_array($origin, $allowedOrigins, true)) {
            return response()->json(['message' => 'Blocked: Invalid origin'], 403);
        }

        if ($referer && !$this->startsWithAny($referer, $allowedOrigins)) {
            return response()->json(['message' => 'Blocked: Invalid referer'], 403);
        }
        
        return $next($request);
    }

    protected function startsWithAny(string $value, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
