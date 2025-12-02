<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

Broadcast::channel('chat.{userId}', function ($user, $userId) {
    Log::info('Channel Authentication - ShouldBroadcastNow', [
        'authenticated_user_id' => $user ? $user->id : 'null',
        'requested_channel_user_id' => $userId,
        'authorized' => !is_null($user)
    ]);

    // Allow any authenticated user to subscribe to any chat channel
    // The Vue component will filter messages to show only relevant ones
    return !is_null($user);
});