<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int    $conversationId;
    public int    $readByUserId;
    public string $readAt;
    public int    $senderUserId;   // the original message sender — needs the blue tick update

    public function __construct(int $conversationId, int $readByUserId, string $readAt, int $senderUserId)
    {
        $this->conversationId = $conversationId;
        $this->readByUserId   = $readByUserId;
        $this->readAt         = $readAt;
        $this->senderUserId   = $senderUserId;
    }

    public function broadcastOn(): array
    {
        return [
            // Conversation channel — catches the sender if they still have the chat open.
            new PrivateChannel('chat.' . $this->conversationId),
            // Sender's private user channel — catches them even if they switched chats.
            new PrivateChannel('user.' . $this->senderUserId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.read';
    }

    public function broadcastWith(): array
    {
        return [
            'conversationId' => $this->conversationId,
            'readByUserId'   => $this->readByUserId,
            'readAt'         => $this->readAt,
        ];
    }
}
