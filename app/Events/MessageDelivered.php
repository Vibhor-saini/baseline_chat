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

    public function __construct(int $messageId, int $conversationId, string $deliveredAt)
    {
        $this->messageId      = $messageId;
        $this->conversationId = $conversationId;
        $this->deliveredAt    = $deliveredAt;
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.' . $this->conversationId)];
    }

    public function broadcastAs(): string
    {
        return 'message.delivered';
    }

    public function broadcastWith(): array
    {
        return [
            'messageId'   => $this->messageId,
            'deliveredAt' => $this->deliveredAt,
        ];
    }
}
