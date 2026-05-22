<?php

namespace App\Livewire\Chat;

use App\Events\ConversationUpdated;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Livewire\Component;
use App\Events\PendingRequestUpdated;

class Index extends Component
{
    public $conversations = [];

    public ?Conversation $selectedConversation = null;

    public $messages = [];

    public string $body = '';

    public string $search = '';

    public $searchResults = [];

    public $pendingRequests = [];

    public $sentRequests = [];

    public $selectedRequest = null;

    public bool $showRequests = false;
    public $showSentRequests = false;
    public ?int $selectedConversationId = null;

    public string $activeScreen = 'empty';

    public function mount()
    {
        $this->loadConversations();

        $this->loadPendingRequests();

        $this->loadSentRequests();
        if (request()->screen === 'pending') {

            $this->showSentRequests = true;

            $this->activeScreen = 'sent-requests';
        }
    }

    protected $listeners = [
        'echo:pending-requests,pending.request.updated' => 'refreshPendingData',
        'echo:conversations,conversation.updated' => 'refreshConversationData',
    ];

    public function refreshPendingData()
    {
        $this->loadPendingRequests();

        $this->loadSentRequests();
    }

    public function refreshConversationData()
    {
        $this->loadConversations();

        $this->loadPendingRequests();

        $this->loadSentRequests();

        if (
            $this->activeScreen === 'sent-requests'
            && $this->sentRequests->count() === 0
        ) {

            $this->showSentRequests = false;

            $this->activeScreen = 'empty';
        }

        if ($this->selectedConversationId) {

            $conversation = Conversation::find($this->selectedConversationId);

            if ($conversation && $conversation->status === 'accepted') {

                $this->selectConversation($conversation->id);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LOADERS
    |--------------------------------------------------------------------------
    */

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

    public function loadPendingRequests()
    {
        $this->pendingRequests = Conversation::query()

            ->where('status', 'pending')

            ->where('user_two_id', auth()->id())

            ->with(['userOne', 'userTwo'])

            ->latest()

            ->get();
    }

    public function loadSentRequests()
    {
        $this->sentRequests = Conversation::query()

            ->where('status', 'pending')

            ->where('user_one_id', auth()->id())

            ->with(['userTwo'])

            ->latest()

            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */

    public function updatedSearch()
    {
        if (strlen($this->search) < 2) {

            $this->searchResults = [];

            return;
        }

        $this->searchResults = User::query()

            ->where('id', '!=', auth()->id())

            ->where(function ($query) {

                $query->where('name', 'like', '%' . $this->search . '%')

                    ->orWhere('email', 'like', '%' . $this->search . '%');
            })

            ->limit(8)

            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | START CONVERSATION
    |--------------------------------------------------------------------------
    */

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

            $targetUser = User::findOrFail($userId);

            $isAdminConversation =
                auth()->user()->is_admin
                || $targetUser->is_admin;

            $conversation = Conversation::create([
                'user_one_id' => $authId,
                'user_two_id' => $userId,
                'status' => $isAdminConversation ? 'accepted' : 'pending',
            ]);

            broadcast(new PendingRequestUpdated($conversation))->toOthers();

            // auto welcome message
            if (!$isAdminConversation) {

                Message::create([

                    'conversation_id' => $conversation->id,

                    'sender_id' => $authId,

                    'body' => 'Hi ' . $targetUser->name,
                ]);
            }
        }

        $this->loadPendingRequests();

        $this->loadSentRequests();

        $this->loadConversations();

        $conversation->refresh();

        broadcast(new ConversationUpdated($conversation))->toOthers();

        $this->selectedConversationId = $conversation->id;

        $this->search = '';

        $this->searchResults = [];

        // already accepted
        if ($conversation->status === 'accepted') {

            $this->selectConversation($conversation->id);

            return;
        }

        // pending request screen
        $this->selectedConversation = null;

        $this->selectedRequest = null;

        $this->activeScreen = 'sent-requests';

        // $this->dispatch('$refresh');
    }

    /*
    |--------------------------------------------------------------------------
    | OPEN EXISTING CHAT
    |--------------------------------------------------------------------------
    */

    public function openExistingConversation($userId)
    {
        $conversation = Conversation::query()

            ->where(function ($query) use ($userId) {

                $query->where('user_one_id', auth()->id())
                    ->where('user_two_id', $userId);
            })

            ->orWhere(function ($query) use ($userId) {

                $query->where('user_one_id', $userId)
                    ->where('user_two_id', auth()->id());
            })

            ->first();

        if ($conversation) {

            $this->selectConversation($conversation->id);
        }

        $this->search = '';

        $this->searchResults = [];
    }

    /*
    |--------------------------------------------------------------------------
    | SELECT CHAT
    |--------------------------------------------------------------------------
    */

    public function selectConversation($conversationId)
    {
        $this->selectedConversation = Conversation::findOrFail($conversationId);

        $this->selectedConversationId = $conversationId;

        $this->messages = Message::query()

            ->where('conversation_id', $conversationId)

            ->with('sender')

            ->latest()

            ->take(50)

            ->get()

            ->reverse()

            ->values();

        $this->selectedRequest = null;

        $this->activeScreen = 'chat';

        // $this->dispatch('$refresh');
    }

    /*
    |--------------------------------------------------------------------------
    | SEND MESSAGE
    |--------------------------------------------------------------------------
    */

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

        $this->messages[] = $message;

        $this->selectedConversation->update([

            'last_message_at' => now(),
        ]);

        broadcast(new MessageSent($message))->toOthers();

        $this->body = '';
    }

    /*
    |--------------------------------------------------------------------------
    | APPEND MESSAGE
    |--------------------------------------------------------------------------
    */

    public function appendMessage($messageData)
    {
        $message = Message::with('sender')

            ->find($messageData['id']);

        if (!$message) {

            return;
        }

        foreach ($this->messages as $existing) {

            if ($existing->id === $message->id) {

                return;
            }
        }

        $this->messages[] = $message;
    }

    /*
    |--------------------------------------------------------------------------
    | REQUESTS
    |--------------------------------------------------------------------------
    */

    public function toggleRequests()
    {
        $this->showRequests = !$this->showRequests;

        if (!$this->showRequests) {

            $this->selectedRequest = null;

            $this->activeScreen = 'empty';
        }
    }

    public function openRequest($requestId)
    {
        $this->selectedRequest = Conversation::with(['userOne'])
            ->findOrFail($requestId);

        $this->selectedConversation = null;

        $this->activeScreen = 'request-preview';
    }

    public function openSentRequests()
    {
        $this->activeScreen = 'sent-requests';

        $this->selectedConversation = null;

        $this->selectedRequest = null;

        $this->loadSentRequests();
    }

    /*
    |--------------------------------------------------------------------------
    | ACCEPT REQUEST
    |--------------------------------------------------------------------------
    */

    public function acceptRequest($conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        $conversation->update([
            'status' => 'accepted',
        ]);

        $this->selectedRequest = null;

        $this->showSentRequests = false;

        $this->loadPendingRequests();

        $this->loadSentRequests();

        $this->loadConversations();

        broadcast(new ConversationUpdated($conversation))->toOthers();

        broadcast(new PendingRequestUpdated($conversation))->toOthers();

        $this->selectConversation($conversation->id);
        $this->showSentRequests = false;
    }

    /*
    |--------------------------------------------------------------------------
    | REJECT REQUEST
    |--------------------------------------------------------------------------
    */

    public function rejectRequest($conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        $conversation->delete();

        $this->selectedRequest = null;

        $this->showSentRequests = false;

        $this->loadPendingRequests();

        $this->loadSentRequests();

        $this->loadConversations();

        broadcast(new PendingRequestUpdated($conversation))->toOthers();

        // $this->dispatch('$refresh');
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        return view('livewire.chat.index');
    }
}
