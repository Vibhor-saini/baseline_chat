<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class MessageReactionUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $messageId,
        public readonly int    $conversationId,
        public readonly array  $reactions,   // serialized reactions for the message
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.' . $this->conversationId)];
    }

    public function broadcastAs(): string
    {
        return 'reaction.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'messageId'      => $this->messageId,
            'conversationId' => $this->conversationId,
            'reactions'      => $this->reactions,
        ];
    }
}
