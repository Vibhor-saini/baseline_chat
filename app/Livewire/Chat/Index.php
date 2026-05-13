<?php

namespace App\Livewire\Chat;

use App\Events\MessageSent;
use App\Models\Message;
use Livewire\Component;
use App\Models\User;
use App\Models\Conversation;
use App\Events\ConversationUpdated;

class Index extends Component
{
    public $conversations = [];

    public ?Conversation $selectedConversation = null;

    public $messages = [];
    public string $body = '';
    public string $search = '';
    public string $requestMessage = '';
    public $searchResults = [];
    public $selectedConversationId = null;
    public $pendingRequests = [];
    // public $openedRequestId = null;
    public $selectedRequest = null;
    public $showRequests = null;
    public function mount()
    {
        $this->loadConversations();
        $this->loadPendingRequests();
    }

    public function loadConversations()
    {
        $this->conversations = Conversation::query()
            ->where('status', 'accepted')
            ->where(function ($query) {
                $query->where('user_one_id', auth()->id())
                    ->orWhere('user_two_id', auth()->id());
            })
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


    // public function startConversation($userId)
    // {
    //     $authId = auth()->id();

    //     $conversation = Conversation::query()

    //         ->where(function ($query) use ($authId, $userId) {

    //             $query->where('user_one_id', $authId)
    //                 ->where('user_two_id', $userId);
    //         })

    //         ->orWhere(function ($query) use ($authId, $userId) {

    //             $query->where('user_one_id', $userId)
    //                 ->where('user_two_id', $authId);
    //         })

    //         ->first();

    //     if (!$conversation) {

    //         $status = 'pending';
    //         if ($authId === 1 || $userId === 1) {
    //             $status = 'accepted';
    //         }

    //         $conversation = Conversation::create([
    //             'user_one_id' => $authId,
    //             'user_two_id' => $userId,
    //             'status' => $status,
    //         ]);
    //     }

    //     $this->conversations = auth()->user()
    //         ->conversations()
    //         ->latest()
    //         ->get();

    //     $this->search = '';
    //     $this->searchResults = [];

    //     if ($conversation->status === 'accepted') {

    //         $this->selectConversation($conversation->id);
    //     }
    // }

    public function startConversation($userId)
    {
        $authId = auth()->id();

        logger('START CONVERSATION', [
            'auth_id' => $authId,
            'target_user_id' => $userId,
        ]);

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

        logger('FOUND CONVERSATION', [
            'conversation' => $conversation,
        ]);

        if (!$conversation) {

            $status = 'pending';

            if ($authId === 1 || $userId === 1) {
                $status = 'accepted';
            }

            logger('CREATING NEW CONVERSATION', [
                'status' => $status,
            ]);

            $conversation = Conversation::create([
                'user_one_id' => $authId,
                'user_two_id' => $userId,
                'status' => $status,
            ]);

            $user = User::findOrFail($userId);

            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $authId,
                'body' => 'Hi ' . $user->name,
            ]);

            $this->loadPendingRequests();
            $this->loadConversations();
            $conversation->refresh();
        }

        logger('FINAL CONVERSATION STATUS', [
            'status' => $conversation->status,
        ]);



        $this->search = '';
        $this->searchResults = [];

        if ($conversation->status === 'accepted') {

            logger('OPENING CHAT WINDOW');

            $this->selectConversation($conversation->id);
        } else {

            logger('PENDING REQUEST CREATED');
        }
    }
    public function loadPendingRequests()
    {
        $this->pendingRequests = Conversation::query()
            ->where('status', 'pending')
            ->where('user_two_id', auth()->id())
            ->with(['userOne', 'userTwo'])
            ->latest()
            ->get();
    }

    // public function toggleRequest($requestId)
    // {
    //     if ($this->openedRequestId === $requestId) {

    //         $this->openedRequestId = null;

    //         return;
    //     }

    //     $this->openedRequestId = $requestId;
    // }

    public function openRequest($requestId)
{
    $this->selectedRequest = Conversation::with(['userOne'])
        ->findOrFail($requestId);

    $this->selectedConversation = null;
}
    public function acceptRequest($conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        $conversation->update([
            'status' => 'accepted',
        ]);

        broadcast(new ConversationUpdated($conversation))->toOthers();
        $this->loadPendingRequests();

        $this->loadConversations();

        $this->selectConversation($conversation->id);
    }

    public function toggleRequests()
{
    $this->showRequests = !$this->showRequests;

    if (!$this->showRequests) {

        $this->selectedRequest = null;
    }
}

    public function render()
    {
        return view('livewire.chat.index');
    }
}
