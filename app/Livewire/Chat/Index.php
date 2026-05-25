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
    | All UI state lives here. activeScreen is the single source of truth for
    | which panel is visible. Never use URL params for screen state.
    |--------------------------------------------------------------------------
    */

    /** @var \Illuminate\Support\Collection Accepted conversations shown in sidebar */
    public $conversations = [];

    /** @var array Messages for the currently open chat */
    public $messages = [];

    /** @var \Illuminate\Support\Collection Search results from global user search */
    public $searchResults = [];

    /** @var \Illuminate\Support\Collection Requests received by the current user */
    public $pendingRequests = [];

    /** @var \Illuminate\Support\Collection Requests sent by the current user */
    public $sentRequests = [];

    /** @var Conversation|null The currently open conversation */
    public ?Conversation $selectedConversation = null;

    /** @var int|null ID of the selected conversation (used for JS channel binding) */
    public ?int $selectedConversationId = null;

    /** @var Conversation|null The request being previewed (receiver side) */
    public $selectedRequest = null;

    /** @var string The message being typed */
    public string $body = '';

    /** @var string Search query for global user search */
    public string $search = '';

    /** @var bool Whether the incoming-requests accordion is expanded in the sidebar */
    public bool $showRequests = false;

    /**
     * Single source of truth for which screen is visible.
     *
     * Possible values:
     *   'empty'           — default / welcome state
     *   'chat'            — an accepted conversation is open
     *   'request-preview' — receiver is viewing an incoming request
     *   'sent-requests'   — sender is viewing their pending sent requests
     */
    public string $activeScreen = 'empty';

    /*
    |--------------------------------------------------------------------------
    | LIVEWIRE LISTENERS
    | Called from JavaScript via component.call(...)
    |--------------------------------------------------------------------------
    */

    protected $listeners = [
        'refreshPendingData'      => 'refreshPendingData',
        'refreshConversationData' => 'refreshConversationData',
    ];

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE — MOUNT
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->loadConversations();
        $this->loadPendingRequests();
        $this->loadSentRequests();
    }

    /*
    |--------------------------------------------------------------------------
    | DATA LOADERS
    | Each method loads only what it owns. Call only what changed.
    |--------------------------------------------------------------------------
    */

    /**
     * Load accepted conversations for the current user's sidebar.
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
     * Load pending requests sent TO the current user (they are user_two).
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
     * Load pending requests sent BY the current user (they are user_one).
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
    | REALTIME REFRESH — called from JS after a broadcast event fires
    |--------------------------------------------------------------------------
    */

    /**
     * Refresh only the pending/sent-request lists.
     * Triggered by PendingRequestUpdated (request sent, accepted, or rejected).
     */
    public function refreshPendingData(): void
    {
        $this->loadPendingRequests();
        $this->loadSentRequests();

        // If the user is watching sent-requests but all requests are gone, reset.
        $this->clearSentRequestsScreenIfEmpty();
    }

    /**
     * Refresh conversations AND request lists.
     * Triggered by ConversationUpdated (request accepted → conversation appears).
     */
    public function refreshConversationData(): void
    {
        $this->loadConversations();
        $this->loadPendingRequests();
        $this->loadSentRequests();

        $this->clearSentRequestsScreenIfEmpty();

        // If we were waiting for a specific conversation to be accepted, open it now.
        if ($this->selectedConversationId) {
            $conversation = Conversation::find($this->selectedConversationId);

            if ($conversation && $conversation->status === 'accepted') {
                $this->selectConversation($conversation->id);
            }
        }
    }

    /**
     * Helper: if the sent-requests screen is active but the list is now empty,
     * fall back to the empty state automatically.
     */
    private function clearSentRequestsScreenIfEmpty(): void
    {
        if (
            $this->activeScreen === 'sent-requests'
            && $this->sentRequests->isEmpty()
        ) {
            $this->activeScreen = 'empty';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SEARCH — real-time user search from the topbar
    |--------------------------------------------------------------------------
    */

    public function updatedSearch(): void
    {
        if (mb_strlen(trim($this->search)) < 2) {
            $this->searchResults = [];
            return;
        }

        $term = '%' . trim($this->search) . '%';

        $this->searchResults = User::query()
            ->where('id', '!=', auth()->id())
            ->where(function ($q) use ($term) {
                $q->where('name',  'like', $term)
                  ->orWhere('email', 'like', $term);
            })
            ->limit(8)
            ->get();
    }

    /**
     * Close the search dropdown without starting anything.
     */
    public function clearSearch(): void
    {
        $this->search        = '';
        $this->searchResults = [];
    }

    /*
    |--------------------------------------------------------------------------
    | START CONVERSATION / SEND REQUEST
    |--------------------------------------------------------------------------
    */

    /**
     * Called when the user clicks "Send Request" or "Start Chat" from search.
     *
     * Logic:
     *  - If a conversation already exists and is accepted → open it.
     *  - If a conversation already exists and is pending  → show the pending screen.
     *  - If no conversation exists:
     *      * Admin-involved conversations are auto-accepted.
     *      * Normal user → normal user sends a pending request.
     */
    public function startConversation(int $userId): void
    {
        $authId = auth()->id();

        $conversation = $this->findConversationBetween($authId, $userId);

        if (! $conversation) {
            $conversation = $this->createConversation($authId, $userId);
        }

        $this->clearSearch();
        $this->loadConversations();
        $this->loadPendingRequests();
        $this->loadSentRequests();

        if ($conversation->status === 'accepted') {
            $this->openChat($conversation->id);
        } else {
            $this->openSentRequests();
        }
    }

    /**
     * Called when the user clicks "Open Chat" on an already-connected person
     * from the search dropdown.
     */
    public function openExistingConversation(int $userId): void
    {
        $authId       = auth()->id();
        $conversation = $this->findConversationBetween($authId, $userId);

        if ($conversation) {
            $this->openChat($conversation->id);
        }

        $this->clearSearch();
    }

    /*
    |--------------------------------------------------------------------------
    | CONVERSATION SELECTION
    |--------------------------------------------------------------------------
    */

    /**
     * Open a conversation by ID and switch the main panel to chat view.
     */
    public function selectConversation(int $conversationId): void
    {
        $this->selectedConversation   = Conversation::with(['userOne', 'userTwo'])
            ->findOrFail($conversationId);
        $this->selectedConversationId = $conversationId;

        $this->messages = Message::query()
            ->where('conversation_id', $conversationId)
            ->with('sender')
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values();

        $this->selectedRequest  = null;
        $this->showRequests     = false;
        $this->activeScreen     = 'chat';
    }

    /*
    |--------------------------------------------------------------------------
    | REQUEST ACTIONS (RECEIVER SIDE)
    |--------------------------------------------------------------------------
    */

    /**
     * Open the preview panel for an incoming request.
     */
    public function openRequest(int $requestId): void
    {
        $this->selectedRequest      = Conversation::with(['userOne'])->findOrFail($requestId);
        $this->selectedConversation = null;
        $this->activeScreen         = 'request-preview';
    }

    /**
     * Accept an incoming request.
     * Transitions the conversation to accepted and opens the chat immediately.
     */
    public function acceptRequest(int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        $conversation->update(['status' => 'accepted']);

        $senderId   = $conversation->user_one_id;
        $receiverId = $conversation->user_two_id;

        // Notify both sides so their UIs update instantly.
        $this->broadcastPendingUpdate($senderId, $receiverId);
        $this->broadcastConversationUpdate($conversation, $senderId, $receiverId);

        // Refresh local state for the receiver who just accepted.
        $this->loadConversations();
        $this->loadPendingRequests();
        $this->loadSentRequests();

        // Open the accepted chat immediately for the receiver.
        $this->selectedRequest = null;
        $this->openChat($conversation->id);
    }

    /**
     * Reject and delete an incoming request.
     */
    public function rejectRequest(int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        $senderId   = $conversation->user_one_id;
        $receiverId = $conversation->user_two_id;

        $conversation->delete();

        // Notify both sides.
        $this->broadcastPendingUpdate($senderId, $receiverId);

        // Refresh local state for the receiver.
        $this->loadPendingRequests();
        $this->loadSentRequests();

        // Close the preview and return to empty state.
        $this->selectedRequest = null;
        $this->activeScreen    = 'empty';
    }

    /*
    |--------------------------------------------------------------------------
    | MESSAGING
    |--------------------------------------------------------------------------
    */

    /**
     * Send a message in the currently open conversation.
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
        $this->loadConversations(); // Re-sort sidebar by last_message_at.

        broadcast(new MessageSent($message))->toOthers();

        $this->body = '';
    }

    /**
     * Append a message received via broadcast.
     * Called from JavaScript when a MessageSent event fires on the private channel.
     */
    public function appendMessage(array $messageData): void
    {
        $message = Message::with('sender')->find($messageData['id']);

        if (! $message) {
            return;
        }

        // Prevent duplicates (e.g. if the event fires more than once).
        foreach ($this->messages as $existing) {
            if ($existing->id === $message->id) {
                return;
            }
        }

        $this->messages[] = $message;
    }

    /*
    |--------------------------------------------------------------------------
    | SIDEBAR TOGGLES
    |--------------------------------------------------------------------------
    */

    /**
     * Toggle the incoming-requests accordion in the sidebar.
     */
    public function toggleRequests(): void
    {
        $this->showRequests = ! $this->showRequests;

        if (! $this->showRequests) {
            // If the request-preview screen was open, close it.
            if ($this->activeScreen === 'request-preview') {
                $this->selectedRequest = null;
                $this->activeScreen    = 'empty';
            }
        }
    }

    /**
     * Switch to the sent-requests screen.
     * Refreshes the list immediately before rendering.
     */
    public function openSentRequests(): void
    {
        $this->loadSentRequests();

        $this->selectedConversation = null;
        $this->selectedRequest      = null;
        $this->activeScreen         = 'sent-requests';
    }

    /*
    |--------------------------------------------------------------------------
    | PRIVATE HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Find an existing conversation between two users regardless of direction.
     */
    private function findConversationBetween(int $userA, int $userB): ?Conversation
    {
        return Conversation::query()
            ->where(function ($q) use ($userA, $userB) {
                $q->where('user_one_id', $userA)
                  ->where('user_two_id', $userB);
            })
            ->orWhere(function ($q) use ($userA, $userB) {
                $q->where('user_one_id', $userB)
                  ->where('user_two_id', $userA);
            })
            ->first();
    }

    /**
     * Create a new conversation (pending or auto-accepted for admins).
     * Auto-sends a greeting message for pending (non-admin) requests.
     */
    private function createConversation(int $authId, int $userId): Conversation
    {
        $targetUser          = User::findOrFail($userId);
        $isAdminConversation = auth()->user()->is_admin || $targetUser->is_admin;

        $conversation = Conversation::create([
            'user_one_id' => $authId,
            'user_two_id' => $userId,
            'status'      => $isAdminConversation ? 'accepted' : 'pending',
        ]);

        // Automatically send a greeting message for non-admin (pending) requests.
        if (! $isAdminConversation) {
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => $authId,
                'body'            => 'Hi ' . $targetUser->name,
            ]);
        }

        // Notify both participants instantly via WebSocket.
        $this->broadcastPendingUpdate($authId, $userId);
        $this->broadcastConversationUpdate($conversation, $authId, $userId);

        return $conversation;
    }

    /**
     * Switch to the chat screen for a given conversation ID.
     */
    private function openChat(int $conversationId): void
    {
        $this->activeScreen = 'chat';
        $this->selectConversation($conversationId);
    }

    /*
    |--------------------------------------------------------------------------
    | BROADCAST HELPERS — thin wrappers to keep callers DRY
    |--------------------------------------------------------------------------
    */

    /**
     * Notify a set of users that their pending-request list has changed.
     */
    private function broadcastPendingUpdate(int ...$userIds): void
    {
        foreach ($userIds as $userId) {
            broadcast(new PendingRequestUpdated($userId));
        }
    }

    /**
     * Notify a set of users that a conversation was created or updated.
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