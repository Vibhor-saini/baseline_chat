<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use Livewire\Component;

class PendingCount extends Component
{
    public $count = 0;

    protected $listeners = [
        'echo:pending-requests,pending.request.updated' => 'loadCount',
    ];

    public function mount()
    {
        $this->loadCount();
    }

    public function loadCount()
    {
        $this->count = Conversation::query()

            ->where('status', 'pending')

            ->where(function ($query) {

                $query->where('user_one_id', auth()->id())

                    ->orWhere('user_two_id', auth()->id());
            })

            ->count();
    }

    public function render()
    {
        return view('livewire.chat.pending-count');
    }
}