<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

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
        // Broadcast only to users who share an accepted conversation with this user.
        // This replaces the unauthenticated public channel and prevents profile
        // data from leaking to anyone who knows the channel name.
        $partnerIds = DB::table('conversations')
            ->where('status', 'accepted')
            ->where(function ($q) {
                $q->where('user_one_id', $this->userId)
                  ->orWhere('user_two_id', $this->userId);
            })
            ->get(['user_one_id', 'user_two_id'])
            ->flatMap(fn ($row) => [$row->user_one_id, $row->user_two_id])
            ->unique()
            ->reject(fn ($id) => (int) $id === (int) $this->userId)
            ->values();

        return $partnerIds
            ->map(fn ($id) => new PrivateChannel('user.' . $id))
            ->all();
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
