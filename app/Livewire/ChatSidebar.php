<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ChatSidebar extends Component
{
    public $activeConversationId;

    public function selectConversation($id)
    {
        if (! auth()->user()->isMemberOfConversation($id)) {
            return;
        }

        $this->activeConversationId = $id;
        $this->dispatch('conversationSelected', $id);
    }

    public function render()
    {
        return view('livewire.chat-sidebar', [
            'conversations' => Auth::user()->conversations()
                ->with(['users'])
                ->with(['messages' => fn ($q) => $q->latest()->limit(1)])
                ->withCount(['messages as unread_count' => function ($query) {
                    $query->where('is_read', false)
                        ->where('sender_id', '!=', Auth::id());
                }])
                ->get()
                ->sortByDesc(fn ($conversation) => $conversation->messages->first()?->created_at ?? $conversation->created_at)
                ->values(),
        ]);
    }
}
