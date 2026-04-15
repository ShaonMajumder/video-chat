<?php

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Tymon\JWTAuth\Facades\JWTAuth;

if (! function_exists('resolveBroadcastUser')) {
    function resolveBroadcastUser($user)
    {
        if ($user) {
            return $user;
        }

        $request = request();
        $resolved = $request->user() ?: $request->get('user') ?: Auth::user();

        if ($resolved) {
            return $resolved;
        }

        try {
            $cookieToken = $request->cookie('token');
            $bearerToken = $request->bearerToken();
            $token = $cookieToken ? Crypt::decryptString($cookieToken) : $bearerToken;

            return $token ? JWTAuth::setToken($token)->authenticate() : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

Broadcast::channel('message-box.{userId}', function ($user, $userId) {
    $currentUser = resolveBroadcastUser($user);

    return $currentUser && (int) $currentUser->id === (int) $userId;
});

Broadcast::channel('presence.online', function ($user) {
    $currentUser = resolveBroadcastUser($user);

    if (! $currentUser) {
        return false;
    }

    return [
        'id' => $currentUser->id,
        'name' => $currentUser->name,
    ];
});
