<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int    $conversationId;
    public int    $userId;
    public string $userName;
    public bool   $isTyping;
    public int    $recipientUserId;

    public function __construct(
        int    $conversationId,
        int    $userId,
        string $userName,
        bool   $isTyping,
        int    $recipientUserId,
    ) {
        $this->conversationId  = $conversationId;
        $this->userId          = $userId;
        $this->userName        = $userName;
        $this->isTyping        = $isTyping;
        $this->recipientUserId = $recipientUserId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->conversationId),
            new PrivateChannel('user.' . $this->recipientUserId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.typing';
    }

    public function broadcastWith(): array
    {
        return [
            'conversationId' => $this->conversationId,
            'userId'         => $this->userId,
            'userName'       => $this->userName,
            'isTyping'       => $this->isTyping,
        ];
    }
}
