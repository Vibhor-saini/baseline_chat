<?php

namespace App\Livewire\Chat;

use Livewire\Component;
use App\Models\Conversation;

class PendingCount extends Component
{
    public int $count = 0;

    /*
    |--------------------------------------------------------------------------
    | LISTENERS
    | refreshPendingCount is called from JS via component.call(...)
    | OR via Livewire's event system.
    |--------------------------------------------------------------------------
    */

    protected $listeners = [
        'refreshPendingCount' => 'refreshCount',
    ];

    public function mount(): void
    {
        $this->refreshCount();
    }

    /**
     * Count sent requests (pending, user is sender) waiting for a response.
     */
    public function refreshCount(): void
    {
        $this->count = Conversation::query()
            ->where('status', 'pending')
            ->where('user_one_id', auth()->id())
            ->count();
    }

    public function render()
    {
        return view('livewire.chat.pending-count');
    }
}