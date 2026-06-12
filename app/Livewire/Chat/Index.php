<?php

namespace App\Livewire\Chat;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Message;
use App\Models\Conversation;
use App\Events\MessageSent;
use App\Events\MessageDelivered;
use App\Events\MessageRead;
use App\Events\MessageDeleted;
use App\Events\ConversationUpdated;
use App\Events\PendingRequestUpdated;
use App\Events\UserTyping;

class Index extends Component
{
    use WithFileUploads;

    /*
    |--------------------------------------------------------------------------
    | PUBLIC STATE
    |--------------------------------------------------------------------------
    | ALL collection-type properties are stored as plain arrays (not Eloquent
    | Collections). Livewire's validate() calls array_merge() on every public
    | property — if any property is a Collection, it throws:
    |   "array_merge(): Argument #1 must be of type array, Collection given"
    |
    | Fix: always end ->get()->all() or ->values()->all() so Livewire only
    | ever sees plain PHP arrays here.
    |--------------------------------------------------------------------------
    */

    public array $conversations   = [];
    public array $messages        = [];
    public array $searchResults   = [];
    public array $pendingRequests = [];
    public array $sentRequests    = [];

    public ?Conversation $selectedConversation   = null;
    public ?int          $selectedConversationId = null;
    public               $selectedRequest        = null;

    /** Text body being composed */
    public string $body = '';

    /** Livewire temp-upload file */
    public $attachment = null;

    /** Forward modal ─────────────────────── */
    public bool   $showForwardModal    = false;
    public ?int   $forwardingMessageId = null;
    public string $forwardSearch       = '';
    public string $forwardExtraText    = '';

    /**
     * Plain array — avoids Livewire's array_merge crash.
     * Shape: [['id' => int, 'other_name' => string, 'other_initial' => string], …]
     */
    public array $forwardTargets = [];

    /** Reply state ───────────────────────── */
    public ?int   $replyingToMessageId  = null;

    /**
     * Plain array — safe for Livewire serialization.
     * Shape: ['id', 'sender_name', 'body', 'type']
     */
    public array  $replyingToPreview   = [];

    public string $search       = '';
    public bool   $showRequests = false;

    /** 'empty' | 'chat' | 'request-preview' | 'sent-requests' */
    public string $activeScreen = 'empty';

    /*
    |--------------------------------------------------------------------------
    | LISTENERS
    |--------------------------------------------------------------------------
    */

    protected $listeners = [
        'refreshPendingData'      => 'refreshPendingData',
        'refreshConversationData' => 'refreshConversationData',
        'markConversationRead'    => 'markConversationRead',
        'refreshSidebarForConv'   => 'refreshSidebarForConv',
    ];

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE
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
    | Always call ->all() at the end so the result is a plain PHP array,
    | never an Eloquent Collection.
    |--------------------------------------------------------------------------
    */

    public function loadConversations(): void
    {
        $userId = auth()->id();

        $this->conversations = Conversation::query()
            ->where('status', 'accepted')
            ->where(function ($q) use ($userId) {
                $q->where('user_one_id', $userId)
                  ->orWhere('user_two_id', $userId);
            })
            ->with(['userOne', 'userTwo', 'latestMessage.sender'])
            ->latest('last_message_at')
            ->get()
            ->all();          // <── plain array, never Collection
    }

    public function loadPendingRequests(): void
    {
        $this->pendingRequests = Conversation::query()
            ->where('status', 'pending')
            ->where('user_two_id', auth()->id())
            ->with(['userOne'])
            ->latest()
            ->get()
            ->all();          // <── plain array
    }

    public function loadSentRequests(): void
    {
        $this->sentRequests = Conversation::query()
            ->where('status', 'pending')
            ->where('user_one_id', auth()->id())
            ->with(['userTwo'])
            ->latest()
            ->get()
            ->all();          // <── plain array
    }

    /*
    |--------------------------------------------------------------------------
    | REALTIME REFRESH
    |--------------------------------------------------------------------------
    */

    public function refreshPendingData(): void
    {
        $this->loadPendingRequests();
        $this->loadSentRequests();
        $this->clearSentRequestsScreenIfEmpty();
    }

    public function refreshConversationData(): void
    {
        $this->loadConversations();
        $this->loadPendingRequests();
        $this->loadSentRequests();
        $this->clearSentRequestsScreenIfEmpty();

        // If a conversation is currently open, re-hydrate it from DB so its
        // status and participant data stay fresh (e.g. after a request is accepted).
        if ($this->selectedConversationId) {
            $fresh = Conversation::with(['userOne', 'userTwo'])
                ->find($this->selectedConversationId);

            if ($fresh) {
                $this->selectedConversation = $fresh;
            }
        }
    }

    /**
     * Called from JS when a message.sent arrives for a conversation that is
     * NOT currently open — refreshes sidebar preview + unread counts only.
     */
    public function refreshSidebarForConv(int $conversationId): void
    {
        $this->loadConversations();
    }

    private function clearSentRequestsScreenIfEmpty(): void
    {
        if ($this->activeScreen === 'sent-requests' && empty($this->sentRequests)) {
            $this->activeScreen = 'empty';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SEARCH
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
            ->where(fn($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term))
            ->limit(8)
            ->get()
            ->all();          // <── plain array
    }

    public function clearSearch(): void
    {
        $this->search        = '';
        $this->searchResults = [];
    }

    /*
    |--------------------------------------------------------------------------
    | CONVERSATIONS
    |--------------------------------------------------------------------------
    */

    public function startConversation(int $userId): void
    {
        $authId       = auth()->id();
        $conversation = $this->findConversationBetween($authId, $userId)
                     ?? $this->createConversation($authId, $userId);

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

    public function openExistingConversation(int $userId): void
    {
        $conversation = $this->findConversationBetween(auth()->id(), $userId);
        if ($conversation) $this->openChat($conversation->id);
        $this->clearSearch();
    }

    public function selectConversation(int $conversationId): void
    {
        $this->selectedConversation   = Conversation::with(['userOne', 'userTwo'])
            ->findOrFail($conversationId);
        $this->selectedConversationId = $conversationId;

        $this->messages = Message::withTrashed()
            ->where('conversation_id', $conversationId)
            ->with(['sender', 'forwardedFrom.sender', 'replyTo.sender'])
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values()
            ->all();          // <── plain array

        $this->selectedRequest = null;
        $this->showRequests    = false;
        $this->activeScreen    = 'chat';

        $this->markMessagesDelivered($conversationId);
        $this->markMessagesRead($conversationId);
        $this->loadConversations();
    }

    /**
     * Called from JS after every Livewire commit, passing the array of user IDs
     * currently online (from the presence channel Set).
     *
     * Marks any of MY outgoing messages as delivered if the recipient is online right now.
     * This is the WhatsApp rule: double grey tick = recipient's device is connected.
     */
    public function markDeliveredForOnlineRecipients(array $onlineUserIds): void
    {
        if (empty($onlineUserIds)) return;

        $now = now();
        $updatedMessageIds = [];

        // Find all accepted conversations where the OTHER user is currently online.
        $conversations = Conversation::where('status', 'accepted')
            ->where(function ($q) use ($onlineUserIds) {
                $q->where('user_one_id', auth()->id())
                  ->whereIn('user_two_id', $onlineUserIds);
            })
            ->orWhere(function ($q) use ($onlineUserIds) {
                $q->where('user_two_id', auth()->id())
                  ->whereIn('user_one_id', $onlineUserIds);
            })
            ->get(['id', 'user_one_id', 'user_two_id']);

        foreach ($conversations as $conv) {
            $undelivered = Message::where('conversation_id', $conv->id)
                ->where('sender_id', auth()->id())
                ->whereNull('delivered_at')
                ->get(['id', 'conversation_id', 'sender_id']);

            foreach ($undelivered as $msg) {
                $msg->update(['delivered_at' => $now]);
                $updatedMessageIds[] = $msg->id;
                // No toOthers() — sender needs this on their own user.{id} channel.
                broadcast(new MessageDelivered(
                    $msg->id,
                    $msg->conversation_id,
                    $now->toISOString(),
                    auth()->id()
                ));
            }
        }

        // Sync the in-memory messages array so Livewire re-renders show the
        // correct delivered status and don't overwrite the tick JS just set.
        if (!empty($updatedMessageIds)) {
            foreach ($this->messages as $i => $msg) {
                if (in_array($msg->id, $updatedMessageIds)) {
                    $this->messages[$i]->delivered_at = $now;
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | MARK DELIVERED / READ
    |--------------------------------------------------------------------------
    */

    private function markMessagesDelivered(int $conversationId): void
    {
        $now = now();

        $undelivered = Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('delivered_at')
            ->get();

        foreach ($undelivered as $msg) {
            $msg->update(['delivered_at' => $now]);
            // No toOthers() — the original sender (msg->sender_id) receives this
            // on their user.{id} channel to update tick icons even if they
            // have switched to a different conversation.
            broadcast(new MessageDelivered($msg->id, $conversationId, $now->toISOString(), $msg->sender_id));
        }
    }

    private function markMessagesRead(int $conversationId): void
    {
        $now = now();

        // Fetch the sender IDs before bulk-updating so we can broadcast per-sender.
        $unread = Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->get(['id', 'sender_id']);

        if ($unread->isEmpty()) return;

        $senderIds = $unread->pluck('sender_id')->unique();

        Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => $now, 'is_read' => true]);

        // Broadcast one MessageRead per unique sender so each sender's user channel fires.
        // No toOthers() — the original sender needs to receive this to flip their tick to blue.
        foreach ($senderIds as $senderId) {
            broadcast(new MessageRead($conversationId, auth()->id(), $now->toISOString(), $senderId));
        }
    }

    /**
     * Called from JS after message.read fires on the open conversation.
     * Flips all MY sent messages to read status in the in-memory array.
     */
    public function markConversationRead(int $conversationId, string $readAt): void
    {
        if ((int) $conversationId !== (int) $this->selectedConversationId) return;

        foreach ($this->messages as $i => $msg) {
            if ($msg->sender_id === auth()->id() && ! $msg->read_at) {
                $this->messages[$i]->read_at = $readAt;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | REQUEST ACTIONS
    |--------------------------------------------------------------------------
    */

    public function openRequest(int $requestId): void
    {
        $this->selectedRequest      = Conversation::with(['userOne'])->findOrFail($requestId);
        $this->selectedConversation = null;
        $this->activeScreen         = 'request-preview';
    }

    public function acceptRequest(int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Set last_message_at so this conversation sorts to the top of the
        // sidebar (loadConversations orders by latest('last_message_at')).
        $conversation->update([
            'status'          => 'accepted',
            'last_message_at' => now(),
        ]);

        $senderId   = $conversation->user_one_id;
        $receiverId = $conversation->user_two_id;

        $this->broadcastPendingUpdate($senderId, $receiverId);
        $this->broadcastConversationUpdate($conversation, $senderId, $receiverId);

        $this->loadConversations();
        $this->loadPendingRequests();
        $this->loadSentRequests();

        $this->selectedRequest = null;
        $this->openChat($conversation->id);
    }

    public function rejectRequest(int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        $this->broadcastPendingUpdate($conversation->user_one_id, $conversation->user_two_id);
        $conversation->delete();

        $this->loadPendingRequests();
        $this->loadSentRequests();

        $this->selectedRequest = null;
        $this->activeScreen    = 'empty';
    }

    /*
    |--------------------------------------------------------------------------
    | MESSAGING
    |--------------------------------------------------------------------------
    */

    public function sendMessage(): void
    {
        if (! $this->selectedConversation) return;
        if (trim($this->body) === '' && ! $this->attachment) return;

        $type     = 'text';
        $filePath = null;

        if ($this->attachment) {
            // Inline validation — 5 MB limit
            $this->validate([
                'attachment' => [
                    'required',
                    'file',
                    'max:5120',
                    'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx',
                ],
            ]);

            $mime = $this->attachment->getMimeType();
            $type = str_starts_with($mime, 'image/') ? 'image' : 'file';

            $filePath         = $this->attachment->store('chat-files', 'public');
            $this->attachment = null;
        }

        $message = Message::create([
            'conversation_id' => $this->selectedConversation->id,
            'sender_id'       => auth()->id(),
            'body'            => trim($this->body),
            'type'            => $type,
            'file_path'       => $filePath,
            'reply_to_id'     => $this->replyingToMessageId,
        ]);

        $message->load('sender', 'forwardedFrom.sender', 'replyTo.sender');

        $this->messages[] = $message;

        $this->selectedConversation->update(['last_message_at' => now()]);
        $this->loadConversations();

        broadcast(new MessageSent($message))->toOthers();

        $this->body = '';
        $this->cancelReply();
    }

    /**
     * Append a message received via WebSocket.
     * If the chat is open → mark delivered + read immediately.
     * If chat is NOT open → only refresh sidebar (preview + unread count).
     */
    public function appendMessage(array $messageData): void
    {
        $incomingConvId = (int) ($messageData['conversation_id'] ?? 0);

        // ── Sidebar-only update (conversation not currently open) ──────────
        if ($incomingConvId !== (int) $this->selectedConversationId) {
            $this->loadConversations();
            return;
        }

        // ── Active conversation: append + mark read ────────────────────────
        $message = Message::withTrashed()
            ->with(['sender', 'forwardedFrom.sender', 'replyTo.sender'])
            ->find($messageData['id']);

        if (! $message) return;

        foreach ($this->messages as $existing) {
            if ($existing->id === $message->id) return; // deduplicate
        }

        $this->messages[] = $message;

        $now = now();
        $message->update(['delivered_at' => $now, 'read_at' => $now, 'is_read' => true]);
        // No toOthers() so sender receives tick update on their user channel.
        broadcast(new MessageDelivered($message->id, $message->conversation_id, $now->toISOString(), $message->sender_id));
        broadcast(new MessageRead($message->conversation_id, auth()->id(), $now->toISOString(), $message->sender_id));

        $this->loadConversations();
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE MESSAGE
    |--------------------------------------------------------------------------
    */

    public function deleteMessage(int $messageId): void
    {
        $message = Message::where('id', $messageId)
            ->where('sender_id', auth()->id())
            ->firstOrFail();

        $conversationId = $message->conversation_id;

        if ($message->file_path) {
            Storage::disk('public')->delete($message->file_path);
        }

        $message->delete(); // soft delete

        broadcast(new MessageDeleted($messageId, $conversationId))->toOthers();

        foreach ($this->messages as $i => $msg) {
            if ($msg->id === $messageId) {
                $this->messages[$i]->deleted_at = now();
                break;
            }
        }

        $this->loadConversations();
    }

    /** Called from JS when the other user deletes a message. */
    public function handleRemoteDelete(int $messageId): void
    {
        foreach ($this->messages as $i => $msg) {
            if ($msg->id === $messageId) {
                $this->messages[$i]->deleted_at = now();
                break;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | FORWARD MESSAGE
    |--------------------------------------------------------------------------
    */

    public function openForwardModal(int $messageId): void
    {
        $this->forwardingMessageId = $messageId;
        $this->showForwardModal    = true;
        $this->forwardSearch       = '';
        $this->forwardExtraText    = '';
        $this->forwardTargets      = [];
        $this->loadForwardTargets();
    }

    public function closeForwardModal(): void
    {
        $this->showForwardModal    = false;
        $this->forwardingMessageId = null;
        $this->forwardExtraText    = '';
    }

    public function updatedForwardSearch(): void
    {
        $this->loadForwardTargets();
    }

    /**
     * Builds $forwardTargets as a PLAIN ARRAY so Livewire never tries
     * to serialize Eloquent Collections through array_merge.
     */
    private function loadForwardTargets(): void
    {
        $userId = auth()->id();
        $term   = mb_strlen(trim($this->forwardSearch)) >= 1
            ? '%' . trim($this->forwardSearch) . '%'
            : null;

        $query = Conversation::query()
            ->where('status', 'accepted')
            ->where(fn($q) => $q->where('user_one_id', $userId)->orWhere('user_two_id', $userId))
            ->with(['userOne', 'userTwo']);

        if ($term) {
            $query->where(function ($q) use ($term, $userId) {
                $q->whereHas('userOne', fn($sq) => $sq->where('id', '!=', $userId)->where('name', 'like', $term))
                  ->orWhereHas('userTwo', fn($sq) => $sq->where('id', '!=', $userId)->where('name', 'like', $term));
            });
        }

        $this->forwardTargets = $query->limit(10)->get()->map(function ($conv) {
            $other = $conv->otherUser();
            return [
                'id'            => $conv->id,
                'other_name'    => $other->name,
                'other_initial' => strtoupper(substr($other->name, 0, 1)),
            ];
        })->toArray();
    }

    /**
     * Forward message to a target conversation.
     */
    public function forwardTo(int $targetConversationId): void
    {
        if (! $this->forwardingMessageId) return;

        $original  = Message::withTrashed()->findOrFail($this->forwardingMessageId);
        $extraText = trim($this->forwardExtraText);

        $body = $original->deleted_at
            ? $extraText
            : ($extraText ? $extraText . "\n" . $original->body : $original->body);

        $newMessage = Message::create([
            'conversation_id'   => $targetConversationId,
            'sender_id'         => auth()->id(),
            'body'              => $body,
            'type'              => $original->deleted_at ? 'text' : $original->type,
            'file_path'         => $original->deleted_at ? null : $original->file_path,
            'forwarded_from_id' => $original->id,
        ]);

        $newMessage->load('sender', 'forwardedFrom.sender');

        $conv = Conversation::findOrFail($targetConversationId);
        $conv->update(['last_message_at' => now()]);

        broadcast(new MessageSent($newMessage))->toOthers();

        if ((int) $targetConversationId === (int) $this->selectedConversationId) {
            $this->messages[] = $newMessage;
        }

        $this->loadConversations();
        $this->closeForwardModal();
    }

    /*
    |--------------------------------------------------------------------------
    | REPLY TO MESSAGE
    |--------------------------------------------------------------------------
    */

    /**
     * Set the reply context. Called from Blade via wire:click="setReply(id)".
     * Builds a plain-array preview so Blade can render the strip above the input.
     */
    public function setReply(int $messageId): void
    {
        // Verify the message belongs to the currently open conversation.
        $message = Message::withTrashed()
            ->with('sender')
            ->where('conversation_id', $this->selectedConversationId)
            ->find($messageId);

        if (! $message) return;

        $this->replyingToMessageId = $messageId;
        $this->replyingToPreview   = [
            'id'          => $message->id,
            'sender_name' => $message->sender?->name ?? 'Unknown',
            'type'        => $message->type,
            // Show a sensible label even for deleted / media messages
            'body'        => $message->deleted_at
                ? 'This message was deleted'
                : ($message->type === 'image'
                    ? '📷 Image'
                    : ($message->type === 'file'
                        ? '📎 ' . $message->fileName()
                        : \Illuminate\Support\Str::limit($message->body, 80))),
        ];
    }

    /**
     * Cancel / dismiss the reply context.
     * Called from the ✕ button in the reply preview strip, or after sending.
     */
    public function cancelReply(): void
    {
        $this->replyingToMessageId = null;
        $this->replyingToPreview   = [];
    }

    /*
    |--------------------------------------------------------------------------
    | TYPING
    |--------------------------------------------------------------------------
    */

    public function broadcastTyping(bool $isTyping): void
    {
        if (! $this->selectedConversation) return;

        $recipientId = $this->selectedConversation->user_one_id === auth()->id()
            ? $this->selectedConversation->user_two_id
            : $this->selectedConversation->user_one_id;

        broadcast(new UserTyping(
            $this->selectedConversation->id,
            auth()->id(),
            auth()->user()->name,
            $isTyping,
            $recipientId,
        ))->toOthers();
    }

    /*
    |--------------------------------------------------------------------------
    | SIDEBAR TOGGLES
    |--------------------------------------------------------------------------
    */

    public function toggleRequests(): void
    {
        $this->showRequests = ! $this->showRequests;

        if (! $this->showRequests && $this->activeScreen === 'request-preview') {
            $this->selectedRequest = null;
            $this->activeScreen    = 'empty';
        }
    }

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

    private function findConversationBetween(int $userA, int $userB): ?Conversation
    {
        return Conversation::query()
            ->where(fn($q) => $q->where('user_one_id', $userA)->where('user_two_id', $userB))
            ->orWhere(fn($q) => $q->where('user_one_id', $userB)->where('user_two_id', $userA))
            ->first();
    }

    private function createConversation(int $authId, int $userId): Conversation
    {
        $targetUser          = User::findOrFail($userId);
        $isAdminConversation = auth()->user()->is_admin || $targetUser->is_admin;

        $conversation = Conversation::create([
            'user_one_id' => $authId,
            'user_two_id' => $userId,
            'status'      => $isAdminConversation ? 'accepted' : 'pending',
        ]);

        if (! $isAdminConversation) {
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => $authId,
                'body'            => 'Hi ' . $targetUser->name,
                'type'            => 'text',
            ]);
        }

        $this->broadcastPendingUpdate($authId, $userId);
        $this->broadcastConversationUpdate($conversation, $authId, $userId);

        return $conversation;
    }

    private function openChat(int $conversationId): void
    {
        $this->activeScreen = 'chat';
        $this->selectConversation($conversationId);
    }

    private function broadcastPendingUpdate(int ...$userIds): void
    {
        foreach ($userIds as $userId) {
            broadcast(new PendingRequestUpdated($userId));
        }
    }

    private function broadcastConversationUpdate(Conversation $conversation, int ...$userIds): void
    {
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

