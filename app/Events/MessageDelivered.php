<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDelivered implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int    $messageId;
    public int    $conversationId;
    public string $deliveredAt;
    public int    $senderUserId;   // the original message sender — needs the tick update

    public function __construct(int $messageId, int $conversationId, string $deliveredAt, int $senderUserId)
    {
        $this->messageId      = $messageId;
        $this->conversationId = $conversationId;
        $this->deliveredAt    = $deliveredAt;
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
        return 'message.delivered';
    }

    public function broadcastWith(): array
    {
        return [
            'messageId'      => $this->messageId,
            'conversationId' => $this->conversationId,
            'deliveredAt'    => $this->deliveredAt,
        ];
    }
}
