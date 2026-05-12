<?php

namespace App\Livewire\Chat;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use Livewire\Component;

class Index extends Component
{
    public $conversations = [];

    public ?Conversation $selectedConversation = null;

    public $messages = [];

    public string $body = '';

    public function mount()
    {
        $this->loadConversations();
    }

    public function loadConversations()
    {
        $this->conversations = Conversation::query()
            ->where('user_one_id', auth()->id())
            ->orWhere('user_two_id', auth()->id())
            ->latest('last_message_at')
            ->with(['userOne', 'userTwo'])
            ->get();
    }


    public function selectConversation($conversationId)
    {
        $this->selectedConversation = Conversation::findOrFail($conversationId);

        $this->messages = Message::query()
            ->where('conversation_id', $conversationId)
            ->with('sender')
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values();

        $this->dispatch('$refresh');
    }

    public function sendMessage()
    {
        if (!$this->selectedConversation || trim($this->body) === '') {
            return;
        }

        $message = Message::create([

            'conversation_id' => $this->selectedConversation->id,

            'sender_id' => auth()->id(),

            'body' => $this->body,

        ]);

        $message->load('sender');

        // optimistic update
        $this->messages[] = $message;

        $this->selectedConversation->update([
            'last_message_at' => now()
        ]);

        broadcast(new MessageSent($message))->toOthers();

        $this->body = '';
    }

    public function appendMessage($messageData)
    {
        $message = Message::with('sender')
            ->find($messageData['id']);

        if (!$message) {
            return;
        }

        // prevent duplicates

        foreach ($this->messages as $existing) {

            if ($existing->id === $message->id) {
                return;
            }
        }

        $this->messages[] = $message;
    }

    public function render()
    {
        return view('livewire.chat.index');
    }
}
