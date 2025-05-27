<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithCookie
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
        try {
            $token = $request->cookie('token') ?? $request->bearerToken();
            $decryptedToken = Crypt::decrypt($token);
            $user = JWTAuth::setToken($decryptedToken)->authenticate();

            if (!$user) {
                return response()->json(['message' => 'Unauthorized: Invalid token'], 401);
            }

            $request->merge(['user' => $user]);
            
        } catch (JWTException $e) {
            Log::warning('JWT authentication failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'url' => $request->fullUrl()
            ]);
            return response()->json(['message' => 'Unauthorized: Token error'], Response::HTTP_UNAUTHORIZED);

        } catch (\Exception $e) {
            Log::error('Authentication middleware exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
                'url' => $request->fullUrl()
            ]);
            return response()->json(['message' => 'Unauthorized: Unexpected error'], Response::HTTP_UNAUTHORIZED);
        }
        
        return $next($request);
    }
}
