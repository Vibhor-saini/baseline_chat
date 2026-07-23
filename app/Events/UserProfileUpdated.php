<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class UserProfileUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $userId,
        public readonly string $avatarUrl,
        public readonly string $status      = 'available',
        public readonly string $name        = '',
        public readonly string $statusQuote = '',
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('profile-updates')];
    }

    public function broadcastAs(): string
    {
        return 'profile.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'userId'      => $this->userId,
            'avatarUrl'   => $this->avatarUrl,
            'status'      => $this->status,
            'name'        => $this->name,
            'statusQuote' => $this->statusQuote,
        ];
    }
}
