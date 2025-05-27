<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }
            $encryptedToken = Crypt::encrypt($token);

            $cookie = cookie(
                'token',
                $encryptedToken,
                60,             // 60 minutes
                '/',            // path
                null,           // domain
                false,          // secure: false for HTTP on localhost
                true,           // HttpOnly
                false,          // raw
                'Lax'           // SameSite policy (or 'Strict', 'None')
            );

            return response()->json([
                'message' => 'Login successful',
                'data' => [
                    'token' => $encryptedToken
                ]
            ])->cookie($cookie);
        } catch (JWTException $e) {
             return response()->json(['message' => 'Could not create token'], 500);
        }
    }

    public function me(Request $request)
    {
        $receiver = $request->receiver;
        if($receiver){
            $userCacheKey = "user:{$receiver}";
            $receiverUserData = Cache::store('redis')->get($userCacheKey);
            if (!$receiverUserData) {
                $receiverUserData = User::find($receiver);
                Cache::store('redis')->put($userCacheKey, $receiverUserData, now()->addHours(24));
            }
        } else {
            return response()->json(['error' => 'Receiver not found'], 404);
        }
        
        return response()->json([
            'user' => JWTAuth::user(),
            'receiver' => $receiverUserData,
        ]);
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Successfully logged out'])
                            ->cookie('jwt', null, -1);
            // return response()->json(['message' => 'Logged out'])->withCookie(cookie()->forget('token'));
        } catch (JWTException $e) {
            return response()->json(['message' => 'Logout failed'], 500);
        }
    }
}
