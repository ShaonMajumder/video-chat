<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticateWithCookie
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $cookieToken = $request->cookie('token');
            $bearerToken = $request->bearerToken();

            if (! $cookieToken && ! $bearerToken) {
                return $this->unauthorizedResponse($request, 'Unauthorized: No token found');
            }

            $token = $cookieToken ? Crypt::decryptString($cookieToken) : $bearerToken;
            $user = JWTAuth::setToken($token)->authenticate();

            if (!$user) {
                return $this->unauthorizedResponse($request, 'Unauthorized: Invalid token');
            }

            // Presence / private channel authorization resolves the current user
            // from Laravel's auth manager, not only from the request instance.
            Auth::setUser($user);
            $request->attributes->set('user', $user);
            $request->setUserResolver(static fn () => $user);
        } catch (JWTException $e) {
            Log::warning('JWT authentication failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            return $this->unauthorizedResponse($request, 'Unauthorized: Token error');
        } catch (\Exception $e) {
            Log::error('Authentication middleware exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            return $this->unauthorizedResponse($request, 'Unauthorized: Unexpected error');
        }

        return $next($request);
    }

    protected function unauthorizedResponse(Request $request, string $message): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => $message], Response::HTTP_UNAUTHORIZED);
        }

        return redirect()->route('login');
    }
}
