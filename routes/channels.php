<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('message-box.{userId}', function ($user, $userId) {
    return $user && (int) $user->id === (int) $userId;
});

Broadcast::channel('presence.online', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});
