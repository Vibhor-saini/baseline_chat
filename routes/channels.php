<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| PRIVATE USER CHANNEL
| Each user can only subscribe to their own channel.
| Used for: PendingRequestUpdated, ConversationUpdated
|--------------------------------------------------------------------------
*/

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
|--------------------------------------------------------------------------
| PUBLIC CONVERSATION CHANNEL
| Used for: MessageSent
| No auth check — participants are validated at the DB level.
|--------------------------------------------------------------------------
*/

Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    return \App\Models\Conversation::where('id', $conversationId)
        ->where(function ($q) use ($user) {
            $q->where('user_one_id', $user->id)
              ->orWhere('user_two_id', $user->id);
        })
        ->exists();
});