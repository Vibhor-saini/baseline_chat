<?php

namespace App\Livewire\Chat;

use Livewire\Component;
use App\Models\User;
use App\Models\Message;
use App\Models\Conversation;
use App\Events\MessageSent;
use App\Events\ConversationUpdated;
use App\Events\PendingRequestUpdated;

class Index extends Component
{
    /*
    |--------------------------------------------------------------------------
    | PUBLIC STATE
    |--------------------------------------------------------------------------
    */

    public $conversations    = [];
    public $messages         = [];
    public $searchResults    = [];
    public $pendingRequests  = [];
    public $sentRequests     = [];

    public ?Conversation $selectedConversation = null;
    public $selectedConversationId             = null;
    public $selectedRequest                    = null;

    public string $body   = '';
    public string $search = '';

    public bool $showRequests     = false;
    public bool $showSentRequests = false;

    /**
     * Single source of truth for which screen is visible.
     * Possible values: 'empty' | 'chat' | 'request-preview' | 'sent-requests'
     */
    public string $activeScreen = 'empty';

    /*
    |--------------------------------------------------------------------------
    | LIVEWIRE LISTENERS
    | These are called from JS via component.call(...)
    |--------------------------------------------------------------------------
    */

    protected $listeners = [
        'refreshPendingData'      => 'refreshPendingData',
        'refreshConversationData' => 'refreshConversationData',
    ];

    /*
    |--------------------------------------------------------------------------
    | MOUNT
    |--------------------------------------------------------------------------
    */

    public function mount()
    {
        $this->loadConversations();
        $this->loadPendingRequests();
        $this->loadSentRequests();
    }

    /*
    |--------------------------------------------------------------------------
    | LOADERS — Each loads only what it owns
    |--------------------------------------------------------------------------
    */

    /**
     * Load accepted conversations for the sidebar.
     */
    public function loadConversations(): void
    {
        $this->conversations = Conversation::query()
            ->where('status', 'accepted')
            ->where(function ($q) {
                $q->where('user_one_id', auth()->id())
                  ->orWhere('user_two_id', auth()->id());
            })
            ->with(['userOne', 'userTwo'])
            ->latest('last_message_at')
            ->get();
    }

    /**
     * Load requests received by the current user (they are user_two).
     */
    public function loadPendingRequests(): void
    {
        $this->pendingRequests = Conversation::query()
            ->where('status', 'pending')
            ->where('user_two_id', auth()->id())
            ->with(['userOne'])
            ->latest()
            ->get();
    }

    /**
     * Load requests sent by the current user (they are user_one).
     */
    public function loadSentRequests(): void
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
    | REALTIME REFRESH CALLBACKS
    | Called from JS when a broadcast event fires on the private user channel.
    |--------------------------------------------------------------------------
    */

    /**
     * Refresh pending + sent request lists only.
     * Used when a request is sent or rejected.
     */
    public function refreshPendingData(): void
    {
        $this->loadPendingRequests();
        $this->loadSentRequests();

        // If user is on the sent-requests screen but has no sent requests
        // anymore (e.g. all were accepted/rejected), return to empty state.
        if (
            $this->activeScreen === 'sent-requests'
            && count($this->sentRequests) === 0
        ) {
            $this->showSentRequests = false;
            $this->activeScreen     = 'empty';
        }
    }

    /**
     * Refresh conversations + pending data.
     * Used when a request is accepted — conversation moves to sidebar.
     */
    public function refreshConversationData(): void
    {
        $this->loadConversations();
        $this->loadPendingRequests();
        $this->loadSentRequests();

        // If sent-requests screen is open but list is now empty, go to empty.
        if (
            $this->activeScreen === 'sent-requests'
            && count($this->sentRequests) === 0
        ) {
            $this->showSentRequests = false;
            $this->activeScreen     = 'empty';
        }

        // If a conversation was selected and it's now accepted, open it.
        if ($this->selectedConversationId) {
            $conversation = Conversation::find($this->selectedConversationId);

            if ($conversation && $conversation->status === 'accepted') {
                $this->selectConversation($conversation->id);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */

    public function updatedSearch(): void
    {
        if (strlen($this->search) < 2) {
            $this->searchResults = [];
            return;
        }

        $this->searchResults = User::query()
            ->where('id', '!=', auth()->id())
            ->where(function ($q) {
                $q->where('name',  'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->limit(8)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | START CONVERSATION / SEND REQUEST
    |--------------------------------------------------------------------------
    */

    public function startConversation(int $userId): void
    {
        $authId = auth()->id();

        // Check if a conversation already exists in either direction.
        $conversation = Conversation::query()
            ->where(function ($q) use ($authId, $userId) {
                $q->where('user_one_id', $authId)
                  ->where('user_two_id', $userId);
            })
            ->orWhere(function ($q) use ($authId, $userId) {
                $q->where('user_one_id', $userId)
                  ->where('user_two_id', $authId);
            })
            ->first();

        // --- Create a new conversation / request ---
        if (! $conversation) {
            $targetUser          = User::findOrFail($userId);
            $isAdminConversation = auth()->user()->is_admin || $targetUser->is_admin;

            $conversation = Conversation::create([
                'user_one_id' => $authId,
                'user_two_id' => $userId,
                'status'      => $isAdminConversation ? 'accepted' : 'pending',
            ]);

            // Auto-send a greeting for non-admin (pending) requests.
            if (! $isAdminConversation) {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => $authId,
                    'body'            => 'Hi ' . $targetUser->name,
                ]);
            }

            // Broadcast to both participants so their UIs update instantly.
            $this->broadcastPendingUpdate($authId, $userId);
            $this->broadcastConversationUpdate($conversation, $authId, $userId);
        }

        // --- Reset search UI ---
        $this->search        = '';
        $this->searchResults = [];

        // --- Refresh local state ---
        $this->loadConversations();
        $this->loadPendingRequests();
        $this->loadSentRequests();

        // --- Navigate to correct screen ---
        if ($conversation->status === 'accepted') {
            $this->activeScreen    = 'chat';
            $this->showSentRequests = false;
            $this->selectedRequest  = null;
            $this->selectConversation($conversation->id);
        } else {
            // Request was sent — show the sender their pending requests screen.
            $this->selectedConversation = null;
            $this->selectedRequest       = null;
            $this->showSentRequests      = true;
            $this->activeScreen          = 'sent-requests';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | OPEN EXISTING CONVERSATION (from search)
    |--------------------------------------------------------------------------
    */

    public function openExistingConversation(int $userId): void
    {
        $authId = auth()->id();

        $conversation = Conversation::query()
            ->where(function ($q) use ($authId, $userId) {
                $q->where('user_one_id', $authId)
                  ->where('user_two_id', $userId);
            })
            ->orWhere(function ($q) use ($authId, $userId) {
                $q->where('user_one_id', $userId)
                  ->where('user_two_id', $authId);
            })
            ->first();

        if ($conversation) {
            $this->selectConversation($conversation->id);
        }

        $this->search        = '';
        $this->searchResults = [];
    }

    /*
    |--------------------------------------------------------------------------
    | SELECT CONVERSATION (open chat window)
    |--------------------------------------------------------------------------
    */

    public function selectConversation(int $conversationId): void
    {
        $this->selectedConversation    = Conversation::with(['userOne', 'userTwo'])
            ->findOrFail($conversationId);
        $this->selectedConversationId  = $conversationId;

        $this->messages = Message::query()
            ->where('conversation_id', $conversationId)
            ->with('sender')
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values();

        $this->selectedRequest  = null;
        $this->showSentRequests = false;
        $this->activeScreen     = 'chat';
    }

    /*
    |--------------------------------------------------------------------------
    | OPEN REQUEST PREVIEW (receiver views incoming request)
    |--------------------------------------------------------------------------
    */

    public function openRequest(int $requestId): void
    {
        $this->selectedRequest      = Conversation::with(['userOne'])->findOrFail($requestId);
        $this->selectedConversation = null;
        $this->showSentRequests     = false;
        $this->activeScreen         = 'request-preview';
    }

    /*
    |--------------------------------------------------------------------------
    | ACCEPT REQUEST
    |--------------------------------------------------------------------------
    */

    public function acceptRequest(int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        $conversation->update(['status' => 'accepted']);

        $senderId   = $conversation->user_one_id;
        $receiverId = $conversation->user_two_id;

        // Broadcast so both sides refresh their conversation lists instantly.
        $this->broadcastPendingUpdate($senderId, $receiverId);
        $this->broadcastConversationUpdate($conversation, $senderId, $receiverId);

        // Refresh local state for the receiver (the one accepting).
        $this->loadConversations();
        $this->loadPendingRequests();
        $this->loadSentRequests();

        // Open the chat immediately for the accepter.
        $this->selectedRequest  = null;
        $this->showSentRequests = false;
        $this->activeScreen     = 'chat';
        $this->selectConversation($conversation->id);
    }

    /*
    |--------------------------------------------------------------------------
    | REJECT REQUEST
    |--------------------------------------------------------------------------
    */

    public function rejectRequest(int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        $senderId   = $conversation->user_one_id;
        $receiverId = $conversation->user_two_id;

        $conversation->delete();

        // Broadcast so the sender's pending list updates instantly.
        $this->broadcastPendingUpdate($senderId, $receiverId);

        // Refresh local state for the receiver.
        $this->loadPendingRequests();
        $this->loadSentRequests();

        // Return receiver to empty screen.
        $this->selectedRequest  = null;
        $this->showSentRequests = false;
        $this->activeScreen     = 'empty';
    }

    /*
    |--------------------------------------------------------------------------
    | SEND MESSAGE
    |--------------------------------------------------------------------------
    */

    public function sendMessage(): void
    {
        if (! $this->selectedConversation || trim($this->body) === '') {
            return;
        }

        $message = Message::create([
            'conversation_id' => $this->selectedConversation->id,
            'sender_id'       => auth()->id(),
            'body'            => $this->body,
        ]);

        $message->load('sender');

        $this->messages[] = $message;

        $this->selectedConversation->update(['last_message_at' => now()]);

        broadcast(new MessageSent($message))->toOthers();

        $this->body = '';
    }

    /*
    |--------------------------------------------------------------------------
    | APPEND REALTIME MESSAGE (called from JS on broadcast)
    |--------------------------------------------------------------------------
    */

    public function appendMessage(array $messageData): void
    {
        $message = Message::with('sender')->find($messageData['id']);

        if (! $message) return;

        // Prevent duplicates.
        foreach ($this->messages as $existing) {
            if ($existing->id === $message->id) return;
        }

        $this->messages[] = $message;
    }

    /*
    |--------------------------------------------------------------------------
    | TOGGLE REQUESTS SIDEBAR SECTION
    |--------------------------------------------------------------------------
    */

    public function toggleRequests(): void
    {
        $this->showRequests = ! $this->showRequests;

        if (! $this->showRequests) {
            $this->selectedRequest = null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | OPEN SENT REQUESTS SCREEN
    |--------------------------------------------------------------------------
    */

    public function openSentRequests(): void
    {
        $this->loadSentRequests();

        $this->showSentRequests     = true;
        $this->selectedConversation = null;
        $this->selectedRequest       = null;
        $this->activeScreen          = 'sent-requests';
    }

    /*
    |--------------------------------------------------------------------------
    | BROADCAST HELPERS — DRY wrappers around repeated broadcast calls
    |--------------------------------------------------------------------------
    */

    /**
     * Notify both users that pending request data has changed.
     */
    private function broadcastPendingUpdate(int ...$userIds): void
    {
        foreach ($userIds as $userId) {
            broadcast(new PendingRequestUpdated($userId));
        }
    }

    /**
     * Notify both users that a conversation has been created or updated.
     */
    private function broadcastConversationUpdate(
        Conversation $conversation,
        int ...$userIds
    ): void {
        foreach ($userIds as $userId) {
            broadcast(new ConversationUpdated($conversation, $userId));
        }
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