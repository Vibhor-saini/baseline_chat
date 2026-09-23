<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $messageId;
    public int $conversationId;
    public int $recipientId;

    public function __construct(int $messageId, int $conversationId, int $recipientId)
    {
        $this->messageId      = $messageId;
        $this->conversationId = $conversationId;
        $this->recipientId    = $recipientId;
    }

    public function broadcastOn(): array
    {
        return [
            // Recipient is actively viewing this conversation
            new PrivateChannel('chat.' . $this->conversationId),
            // Recipient has a different chat open — still needs to update sidebar/preview
            new PrivateChannel('user.' . $this->recipientId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'messageId'      => $this->messageId,
            'conversationId' => $this->conversationId,
        ];
    }
}
