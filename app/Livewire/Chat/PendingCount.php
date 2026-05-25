<?php

namespace App\Livewire\Chat;

use Livewire\Component;
use App\Models\Conversation;

/**
 * A tiny component that only tracks the pending-request badge count.
 * Mounted in the nav rail and refreshed via JS whenever a broadcast fires.
 */
class PendingCount extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->refreshCount();
    }

    /**
     * Recalculate the count. Called from JS after realtime events.
     */
    public function refreshCount(): void
    {
        $this->count = Conversation::query()
            ->where('status', 'pending')
            ->where('user_two_id', auth()->id())
            ->count();
    }

    public function render()
    {
        return view('livewire.chat.pending-count');
    }
}