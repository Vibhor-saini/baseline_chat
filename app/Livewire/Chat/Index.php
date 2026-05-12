<?php

namespace App\Livewire\Chat;

use App\Events\MessageSent;
use App\Models\Message;
use Livewire\Component;
use App\Models\User;
use App\Models\Conversation;

class Index extends Component
{
    public $conversations = [];

    public ?Conversation $selectedConversation = null;

    public $messages = [];

    public string $body = '';

    public string $search = '';

    public $searchResults = [];

    public $selectedConversationId = null;

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
        $this->selectedConversationId = $this->selectedConversation->id;
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


    public function updatedSearch()
    {
        if (strlen($this->search) < 2) {

            $this->searchResults = [];

            return;
        }

        $this->searchResults = \App\Models\User::query()

            ->where('id', '!=', auth()->id())

            ->where(function ($query) {

                $query->where('name', 'like', '%' . $this->search . '%')

                    ->orWhere('email', 'like', '%' . $this->search . '%');
            })

            ->limit(8)

            ->get();
    }


    public function startConversation($userId)
{
    $authId = auth()->id();

    $conversation = Conversation::query()

        ->where(function ($query) use ($authId, $userId) {

            $query->where('user_one_id', $authId)
                  ->where('user_two_id', $userId);

        })

        ->orWhere(function ($query) use ($authId, $userId) {

            $query->where('user_one_id', $userId)
                  ->where('user_two_id', $authId);

        })

        ->first();

    if (!$conversation) {

        $conversation = Conversation::create([
            'user_one_id' => $authId,
            'user_two_id' => $userId,
        ]);

    }

    $this->conversations = auth()->user()
        ->conversations()
        ->latest()
        ->get();

    $this->selectConversation($conversation->id);

    $this->search = '';

    $this->searchResults = [];
}

    public function render()
    {
        return view('livewire.chat.index');
    }
}
