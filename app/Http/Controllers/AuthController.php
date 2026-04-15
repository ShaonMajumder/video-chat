<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            $cookie = cookie(
                'token',
                Crypt::encryptString($token),
                60,
                '/',
                null,
                false,
                true,
                false,
                'Lax'
            );

            return response()->json([
                'message' => 'Login successful',
                'redirect' => '/dashboard',
            ])->cookie($cookie);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Could not create token'], 500);
        }
    }

    public function session(Request $request): JsonResponse
    {
        $currentUser = $request->get('user');

        $contacts = User::query()
            ->whereKeyNot($currentUser->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => 'offline',
                ];
            })
            ->values();

        return response()->json([
            'user' => [
                'id' => $currentUser->id,
                'name' => $currentUser->name,
                'email' => $currentUser->email,
            ],
            'contacts' => $contacts,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            if ($token = $request->bearerToken()) {
                JWTAuth::setToken($token)->invalidate();
            } elseif ($cookieToken = $request->cookie('token')) {
                JWTAuth::setToken(Crypt::decryptString($cookieToken))->invalidate();
            }
        } catch (\Throwable $e) {
            // Ignore invalid token state during logout and still clear the cookie.
        }

        return response()->json(['message' => 'Logged out'])
            ->cookie(cookie()->forget('token'));
    }
}
