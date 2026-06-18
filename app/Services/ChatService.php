<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

class ChatService
{
    public function sendWelcomeMessage(User $newUser): void
    {
        $admin = User::where('role', 'admin')->first();
        if (! $admin) {
            return;
        }

        $conversation = Conversation::create(['type' => 'direct']);
        $conversation->users()->attach([$admin->id, $newUser->id]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $admin->id,
            'body' => "Welcome to the team, {$newUser->name}! Let me know if you need any help getting started.",
        ]);

        broadcast(new MessageSent($message));
    }
}
