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

    public function __construct(int $conversationId, int $readByUserId, string $readAt)
    {
        $this->conversationId = $conversationId;
        $this->readByUserId   = $readByUserId;
        $this->readAt         = $readAt;
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.' . $this->conversationId)];
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
