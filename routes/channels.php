<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });


// Single Channel per User:
// The current setup assumes each user subscribes to their own message-box.{userId} channel to receive messages. This is fine for one-on-one chats but may need adjustment for group chats or multiple concurrent conversations.
// If users need to receive messages from multiple senders, you might need additional channels or a different naming convention (e.g., conversation.{conversationId}).

Broadcast::channel('message-box.{userId}', function ($user, $userId) {
    if (!$user) {
        Log::warning('Channel authorization failed: No authenticated user', [
            'channel_user_id' => $userId,
        ]);
        return false;
    }

    $authorized = (int) $user->id === (int) $userId;

    $logContext = [
        'user_id' => $user->id,
        'channel_user_id' => $userId,
        'authorized' => $authorized,
    ];

    if (app()->environment('local')) {
        Log::debug('Channel authorization', $logContext);
    } elseif (!$authorized) {
        Log::warning('Channel authorization failed', $logContext);
    }

    return $authorized;
});

Broadcast::channel('presence.online', function ($user) {
    Log::info('Online presence', ['id' => $user->id, 'name' => $user->name]);
    return ['id' => $user->id, 'name' => $user->name];
});
