<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $conversationId;
    public int $userId;
    public string $status;

    public function __construct(Conversation $conversation, int $userId)
    {
        // Store only primitives to avoid serialization issues with Eloquent models.
        $this->conversationId = $conversation->id;
        $this->userId         = $userId;
        $this->status         = $conversation->status;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'conversationId' => $this->conversationId,
            'userId'         => $this->userId,
            'status'         => $this->status,
        ];
    }
}