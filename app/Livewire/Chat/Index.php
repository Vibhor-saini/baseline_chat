<?php

namespace App\Livewire\Chat;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\MessageReaction;
use App\Events\MessageSent;
use App\Events\MessageDelivered;
use App\Events\MessageRead;
use App\Events\MessageDeleted;
use App\Events\MessageEdited;
use App\Events\MessageReactionUpdated;
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
    public array $favorites       = [];
    public array $favoriteConvIds = [];  // plain int[] — survives Livewire dehydration
    
    // Section toggle states
    public bool $showFavorites = true;
    public bool $showChats = true;
    
    #[Locked]
    public array $messages        = [];
    public array $searchResults   = [];
    public array $pendingRequests = [];
    public array $sentRequests    = [];

    // Sidebar conversation search
    public string $conversationSearch = '';
    
    // Unread filter
    #[Url(as: 'unread')]
    public bool $showUnreadOnly = false;

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
     * Preview data for the message being forwarded, shown in the modal card.
     * Shape: ['sender_name', 'sender_initial', 'body', 'type', 'sent_at']
     */
    public array $forwardingMessagePreview = [];

    /**
     * The selected recipient conversation for forwarding.
     * Shape: ['id', 'other_name', 'other_initial', 'avatar_url'] or []
     */
    public array $forwardSelectedTarget = [];

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

    /** Per-conversation draft bodies — keyed by conversation ID */
    public array $draftBodies = [];

    /** Edit message state ─────────────────── */
    public ?int   $editingMessageId = null;
    public string $editBody         = '';

    /** Files tab state ─────────────────── */
    public string $activeTab = 'chat';
    public array  $conversationFiles = [];
    public array  $selectedFileIds = [];
    
    /** Photos tab state ─────────────────── */
    public array  $conversationPhotos = [];
    public array  $selectedPhotoIds = [];

    /** Message search within conversation ── */
    public string $msgSearch        = '';
    public array  $msgSearchResults = [];

    /** Load more messages ── */
    public bool $hasMoreMessages    = false;
    public ?int $oldestLoadedMsgId  = null;

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

        // Auto-open conversation if conversation ID is in URL (for popup windows)
        if (request()->has('conversation')) {
            $conversationId = (int) request()->get('conversation');
            $conversation = Conversation::find($conversationId);
            
            // Verify user has access to this conversation
            if ($conversation && 
                ($conversation->user_one_id === auth()->id() || $conversation->user_two_id === auth()->id())) {
                $this->selectConversation($conversationId);
            }
        }
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

        $query = Conversation::query()
            ->where('status', 'accepted')
            ->where(function ($q) use ($userId) {
                $q->where('user_one_id', $userId)
                  ->orWhere('user_two_id', $userId);
            })
            // Exclude conversations deleted by THIS user
            ->whereNotExists(function ($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('conversation_user')
                    ->whereColumn('conversation_user.conversation_id', 'conversations.id')
                    ->where('conversation_user.user_id', $userId)
                    ->where('conversation_user.is_deleted', true);
            });

        // Apply search filter if search term exists
        if (trim($this->conversationSearch) !== '') {
            $searchTerm = '%' . trim($this->conversationSearch) . '%';

            $query->where(function ($q) use ($searchTerm, $userId) {
                // Search by other user's name
                $q->whereHas('userOne', function ($userQuery) use ($searchTerm, $userId) {
                    $userQuery->where('id', '!=', $userId)
                              ->where('name', 'like', $searchTerm);
                })
                ->orWhereHas('userTwo', function ($userQuery) use ($searchTerm, $userId) {
                    $userQuery->where('id', '!=', $userId)
                              ->where('name', 'like', $searchTerm);
                })
                // Search in message content
                ->orWhereHas('messages', function ($msgQuery) use ($searchTerm) {
                    $msgQuery->where('body', 'like', $searchTerm)
                             ->whereNull('deleted_at');
                });
            });
        }

        $allConversations = $query
            ->with(['userOne', 'userTwo', 'latestMessage.sender'])
            ->withCount(['messages as unread_count' => function ($q) use ($userId) {
                $q->where('sender_id', '!=', $userId)
                  ->whereNull('read_at');
            }])
            ->latest('last_message_at')
            ->get()
            ->all();          // <── plain array, never Collection

        // Load favorite IDs and sort_order for this user — both as plain scalar arrays
        // so they survive Livewire dehydration/rehydration without being wiped by find().
        $pivotRows = DB::table('conversation_user')
            ->where('user_id', $userId)
            ->get(['conversation_id', 'is_favorite', 'sort_order']);

        $this->favoriteConvIds = $pivotRows
            ->where('is_favorite', true)
            ->pluck('conversation_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        // Build sort_order map: conv_id => sort_order (null if not set)
        $sortOrderMap = $pivotRows->pluck('sort_order', 'conversation_id')
            ->map(fn ($v) => $v !== null ? (int) $v : null)
            ->all();

        // Separate favorites and regular chats
        $favorites = [];
        $regular   = [];

        foreach ($allConversations as $conv) {
            $conv->_sort_order = $sortOrderMap[$conv->id] ?? null;
            if (in_array($conv->id, $this->favoriteConvIds, true)) {
                $favorites[] = $conv;
            } else {
                $regular[] = $conv;
            }
        }

        // Sort each section: user-defined sort_order first, then by last_message_at
        $sortFn = function ($a, $b) {
            $aSort = $a->_sort_order;
            $bSort = $b->_sort_order;

            // Both have custom sort → compare numerically
            if ($aSort !== null && $bSort !== null) return $aSort <=> $bSort;
            // Only one has custom sort → sorted one goes first
            if ($aSort !== null) return -1;
            if ($bSort !== null) return 1;
            // Neither sorted → keep last_message_at desc (already from query)
            return 0;
        };

        usort($favorites, $sortFn);
        usort($regular,   $sortFn);

        $this->favorites = $favorites;
        $this->conversations = $regular;

        // Filter by unread if checkbox is checked
        if ($this->showUnreadOnly) {
            $this->favorites = array_values(array_filter($this->favorites, function ($conv) {
                return isset($conv->unread_count) && $conv->unread_count > 0;
            }));
            $this->conversations = array_values(array_filter($this->conversations, function ($conv) {
                return isset($conv->unread_count) && $conv->unread_count > 0;
            }));
        }

        // Post-process: Hide latestMessage if it's before the user's deletion point
        // Single bulk query instead of one query per conversation (fixes N+1)
        $allConvIds   = array_column(array_merge($this->favorites, $this->conversations), 'id');
        $deletionMap  = DB::table('conversation_user')
            ->where('user_id', $userId)
            ->whereIn('conversation_id', $allConvIds)
            ->whereNotNull('last_seen_message_id')
            ->pluck('last_seen_message_id', 'conversation_id')
            ->all();

        foreach (array_merge($this->favorites, $this->conversations) as $conv) {
            $lastSeenId = $deletionMap[$conv->id] ?? null;
            if ($lastSeenId && $conv->latestMessage && $conv->latestMessage->id <= $lastSeenId) {
                $conv->setRelation('latestMessage', null);
            }
        }
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

        $term    = '%' . trim($this->search) . '%';
        $authId  = auth()->id();

        $users = User::query()
            ->where('id', '!=', $authId)
            ->where(fn($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term))
            ->limit(8)
            ->get();

        // Pre-load all conversations between auth user and found users in ONE query
        $userIds = $users->pluck('id')->all();
        $convMap = Conversation::query()
            ->where(function ($q) use ($authId, $userIds) {
                $q->where('user_one_id', $authId)->whereIn('user_two_id', $userIds);
            })
            ->orWhere(function ($q) use ($authId, $userIds) {
                $q->where('user_two_id', $authId)->whereIn('user_one_id', $userIds);
            })
            ->get(['id', 'user_one_id', 'user_two_id', 'status'])
            ->keyBy(fn($c) => $c->user_one_id === $authId ? $c->user_two_id : $c->user_one_id);

        // Attach conversation data to each user result
        // Privacy: Only show emails to admins (S-3 security fix)
        $this->searchResults = $users->map(fn($u) => [
            'id'       => $u->id,
            'name'     => $u->name,
            'email'    => auth()->user()->is_admin ? $u->email : null, // Hide for non-admins
            'is_admin' => $u->is_admin,
            'profile_image' => $u->profile_image, // Add profile image
            'conv'     => $convMap->has($u->id) ? [
                'id'         => $convMap[$u->id]->id,
                'status'     => $convMap[$u->id]->status,
                'user_one_id'=> $convMap[$u->id]->user_one_id,
                'user_two_id'=> $convMap[$u->id]->user_two_id,
            ] : null,
        ])->all();
    }

    public function clearSearch(): void
    {
        $this->search        = '';
        $this->searchResults = [];
    }

    /**
     * Update conversation list when sidebar search changes.
     */
    public function updatedConversationSearch(): void
    {
        $this->loadConversations();
    }

    /**
     * Update conversation list when unread filter changes.
     */
    public function updatedShowUnreadOnly(): void
    {
        $this->loadConversations();
    }

    /**
     * Clear sidebar conversation search.
     */
    public function clearConversationSearch(): void
    {
        $this->conversationSearch = '';
        $this->loadConversations();
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
        // ── Authorization: user must be a participant ──────────────────────
        $authId = auth()->id();
        abort_unless(
            Conversation::where('id', $conversationId)
                ->where(fn($q) => $q->where('user_one_id', $authId)->orWhere('user_two_id', $authId))
                ->exists(),
            403
        );

        // ── Un-delete conversation if user is opening a deleted one ────────
        // This happens when user searches and opens a previously deleted conversation
        DB::table('conversation_user')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $authId)
            ->where('is_deleted', true)
            ->update([
                'is_deleted' => false,
                'deleted_at' => null,
                'updated_at' => now(),
            ]);

        // ── Save current draft before switching ───────────────────────────
        if ($this->selectedConversationId && trim($this->body) !== '') {
            $this->draftBodies[(string) $this->selectedConversationId] = $this->body;
        } elseif ($this->selectedConversationId) {
            // Body was cleared — remove any stored draft for this conversation
            unset($this->draftBodies[(string) $this->selectedConversationId]);
        }

        $this->selectedConversation   = Conversation::with(['userOne', 'userTwo'])
            ->findOrFail($conversationId);
        $this->selectedConversationId = $conversationId;

        // ── Restore draft for the new conversation ────────────────────────
        $this->body = $this->draftBodies[(string) $conversationId] ?? '';

        // Check if user has deleted this conversation before (filter old messages)
        $deletionRecord = DB::table('conversation_user')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $authId)
            ->whereNotNull('last_seen_message_id') // ← Check if there was a deletion, regardless of current status
            ->first();

        $messageQuery = Message::withTrashed()
            ->where('conversation_id', $conversationId);

        // If user had deleted conversation, only show messages AFTER deletion
        if ($deletionRecord && $deletionRecord->last_seen_message_id) {
            $messageQuery->where('id', '>', $deletionRecord->last_seen_message_id);
        }

        $this->messages = $messageQuery
            ->with(['sender', 'forwardedFrom.sender', 'replyTo.sender', 'reactions.user'])
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values()
            ->all();          // <── plain array

        // Track whether older messages exist for "load more" trigger
        $this->oldestLoadedMsgId = ! empty($this->messages) ? $this->messages[0]->id : null;
        
        if ($this->oldestLoadedMsgId) {
            $hasMoreQuery = Message::withTrashed()
                ->where('conversation_id', $conversationId)
                ->where('id', '<', $this->oldestLoadedMsgId);
            
            // If user had deleted conversation, don't count messages before deletion
            if ($deletionRecord && $deletionRecord->last_seen_message_id) {
                $hasMoreQuery->where('id', '>', $deletionRecord->last_seen_message_id);
            }
            
            $this->hasMoreMessages = $hasMoreQuery->exists();
        } else {
            $this->hasMoreMessages = false;
        }

        $this->selectedRequest = null;
        $this->showRequests    = false;
        $this->activeScreen    = 'chat';

        $this->markMessagesDelivered($conversationId);
        $this->markMessagesRead($conversationId);
        $this->loadConversations();
        $this->loadConversationFiles($conversationId);
        $this->loadConversationPhotos($conversationId);
        $this->dispatch('scroll-to-bottom');
    }

    /**
     * Set active tab and clear selections when switching
     */
    public function setActiveTab(string $tab): void
    {
        // Clear selections when switching tabs
        $this->selectedFileIds = [];
        $this->selectedPhotoIds = [];
        $this->activeTab = $tab;

        // Load data for the new tab if needed
        if ($this->selectedConversationId) {
            if ($tab === 'files') {
                $this->loadConversationFiles($this->selectedConversationId);
            } elseif ($tab === 'photos') {
                $this->loadConversationPhotos($this->selectedConversationId);
            }
        }
    }

    /**
     * Load all files (images & attachments) from the selected conversation
     */
    public function loadConversationFiles(int $conversationId): void
    {
        $authId = auth()->id();
        
        // Get deletion record to filter old files (same as messages)
        $deletionRecord = DB::table('conversation_user')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $authId)
            ->whereNotNull('last_seen_message_id')
            ->first();

        // Only show 'file' type messages, NOT 'image' type
        $fileQuery = Message::where('conversation_id', $conversationId)
            ->where('type', 'file') // Changed from whereIn to where
            ->whereNotNull('file_path');

        // Filter files sent after deletion
        if ($deletionRecord && $deletionRecord->last_seen_message_id) {
            $fileQuery->where('id', '>', $deletionRecord->last_seen_message_id);
        }

        $files = $fileQuery
            ->with('sender:id,name')
            ->latest()
            ->get();

        $this->conversationFiles = $files->map(function ($message) {
            $filePath = $message->file_path;
            $fileName = $message->file_name ?? basename($filePath);
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $fileSize = 0;

            try {
                if (Storage::disk('public')->exists($filePath)) {
                    $fileSize = Storage::disk('public')->size($filePath);
                }
            } catch (\Exception $e) {
                // Ignore file size errors
            }

            return [
                'id' => $message->id,
                'name' => $fileName,
                'extension' => strtolower($extension),
                'size' => $fileSize,
                'size_formatted' => $this->formatFileSize($fileSize),
                'url' => Storage::url($filePath),
                'type' => $message->type,
                'sent_at' => $message->created_at->format('M j, Y'),
                'sender_name' => $message->sender->name ?? 'Unknown',
            ];
        })->all();
    }

    /**
     * Format file size in human-readable format
     */
    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * Download selected files as a ZIP
     */
    public function downloadSelectedFiles(): void
    {
        if (empty($this->selectedFileIds)) {
            return;
        }

        // For now, we'll just trigger individual downloads via JavaScript
        // A proper implementation would create a ZIP file on the server
        $this->dispatch('download-files', fileIds: $this->selectedFileIds);
    }

    /**
     * Clear file selection
     */
    public function clearSelection(): void
    {
        $this->selectedFileIds = [];
    }

    /**
     * Delete selected files permanently from database and storage
     */
    public function deleteSelectedFiles(): void
    {
        \Log::info('[DeleteFiles] Called with IDs:', $this->selectedFileIds);
        
        if (empty($this->selectedFileIds)) {
            \Log::warning('[DeleteFiles] No files selected');
            return;
        }

        $authId = auth()->id();
        \Log::info('[DeleteFiles] Auth ID:', ['auth_id' => $authId]);

        // Get messages that user owns
        $messages = Message::whereIn('id', $this->selectedFileIds)
            ->where('sender_id', $authId)
            ->where('type', 'file')
            ->get();

        \Log::info('[DeleteFiles] Found messages:', ['count' => $messages->count()]);

        if ($messages->isEmpty()) {
            \Log::warning('[DeleteFiles] No messages found or not owned by user');
            return;
        }

        $conversationId = $this->selectedConversationId;

        // Delete each message
        foreach ($messages as $message) {
            \Log::info('[DeleteFiles] Deleting message:', ['id' => $message->id, 'file' => $message->file_path]);
            
            // Delete file from storage
            if ($message->file_path) {
                try {
                    Storage::disk('public')->delete($message->file_path);
                    \Log::info('[DeleteFiles] File deleted from storage:', ['path' => $message->file_path]);
                } catch (\Exception $e) {
                    \Log::error('[DeleteFiles] Storage delete failed:', ['error' => $e->getMessage()]);
                }
            }

            // Broadcast deletion
            broadcast(new MessageDeleted(
                $message->id,
                $message->conversation_id,
                $message->sender_id
            ));

            // Delete from database
            $message->forceDelete();
            \Log::info('[DeleteFiles] Message force deleted from DB');
        }

        // Clear selection
        $this->selectedFileIds = [];

        // Reload everything
        if ($conversationId) {
            $this->loadConversationFiles($conversationId);
            $this->loadConversationPhotos($conversationId);
            $this->selectConversation($conversationId);
        }
        
        \Log::info('[DeleteFiles] Completed successfully');
    }

    /**
     * Load all photos (image messages) from the selected conversation
     */
    public function loadConversationPhotos(int $conversationId): void
    {
        $authId = auth()->id();
        
        // Get deletion record to filter old photos
        $deletionRecord = DB::table('conversation_user')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $authId)
            ->whereNotNull('last_seen_message_id')
            ->first();

        $photoQuery = Message::where('conversation_id', $conversationId)
            ->where('type', 'image')
            ->whereNotNull('file_path');

        // Filter photos sent after deletion
        if ($deletionRecord && $deletionRecord->last_seen_message_id) {
            $photoQuery->where('id', '>', $deletionRecord->last_seen_message_id);
        }

        $photos = $photoQuery
            ->with('sender:id,name')
            ->latest()
            ->get();

        $this->conversationPhotos = $photos->map(function ($message) {
            $filePath = $message->file_path;
            $fileName = $message->file_name ?? basename($filePath);

            return [
                'id' => $message->id,
                'message_id' => $message->id, // For lightbox navigation
                'name' => $fileName,
                'url' => Storage::url($filePath),
                'thumbnail_url' => Storage::url($filePath), // For now, same as full image
                'sent_at' => $message->created_at->format('M j, Y'),
                'sender_name' => $message->sender->name ?? 'Unknown',
            ];
        })->all();
    }

    /**
     * Download selected photos
     */
    public function downloadSelectedPhotos(): void
    {
        if (empty($this->selectedPhotoIds)) {
            return;
        }

        $this->dispatch('download-files', fileIds: $this->selectedPhotoIds);
    }

    /**
     * Clear photo selection
     */
    public function clearPhotoSelection(): void
    {
        $this->selectedPhotoIds = [];
    }

    /**
     * Delete selected photos permanently from database and storage
     */
    public function deleteSelectedPhotos(): void
    {
        \Log::info('[DeletePhotos] Called with IDs:', $this->selectedPhotoIds);
        
        if (empty($this->selectedPhotoIds)) {
            \Log::warning('[DeletePhotos] No photos selected');
            return;
        }

        $authId = auth()->id();
        \Log::info('[DeletePhotos] Auth ID:', ['auth_id' => $authId]);

        // Get messages that user owns
        $messages = Message::whereIn('id', $this->selectedPhotoIds)
            ->where('sender_id', $authId)
            ->where('type', 'image')
            ->get();

        \Log::info('[DeletePhotos] Found messages:', ['count' => $messages->count()]);

        if ($messages->isEmpty()) {
            \Log::warning('[DeletePhotos] No messages found or not owned by user');
            return;
        }

        $conversationId = $this->selectedConversationId;

        // Delete each message
        foreach ($messages as $message) {
            \Log::info('[DeletePhotos] Deleting message:', ['id' => $message->id, 'file' => $message->file_path]);
            
            // Delete file from storage
            if ($message->file_path) {
                try {
                    Storage::disk('public')->delete($message->file_path);
                    \Log::info('[DeletePhotos] File deleted from storage:', ['path' => $message->file_path]);
                } catch (\Exception $e) {
                    \Log::error('[DeletePhotos] Storage delete failed:', ['error' => $e->getMessage()]);
                }
            }

            // Broadcast deletion
            broadcast(new MessageDeleted(
                $message->id,
                $message->conversation_id,
                $message->sender_id
            ));

            // Delete from database
            $message->forceDelete();
            \Log::info('[DeletePhotos] Message force deleted from DB');
        }

        // Clear selection
        $this->selectedPhotoIds = [];

        // Reload everything
        if ($conversationId) {
            $this->loadConversationPhotos($conversationId);
            $this->loadConversationFiles($conversationId);
            $this->selectConversation($conversationId);
        }
        
        \Log::info('[DeletePhotos] Completed successfully');
    }

    /**
     * Mark conversation as unread by clearing read_at on recent messages.
     * Useful for reminding user to reply later.
     */
    public function markAsUnread(int $conversationId): void
    {
        // Verify user has access
        $authId = auth()->id();
        $conversation = Conversation::where('id', $conversationId)
            ->where(fn($q) => $q->where('user_one_id', $authId)->orWhere('user_two_id', $authId))
            ->first();

        if (!$conversation) return;

        // Get the other user ID (message sender)
        $otherUserId = $conversation->user_one_id === $authId 
            ? $conversation->user_two_id 
            : $conversation->user_one_id;

        // Mark only the LAST message from the other user as unread
        // (Microsoft Teams style - just 1 message for reminder)
        Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $authId)
            ->whereNotNull('read_at')
            ->latest()
            ->take(1)
            ->update(['read_at' => null]);

        // Close the current conversation (switch to empty state)
        // This mimics Microsoft Teams behavior
        if ($this->selectedConversationId === $conversationId) {
            $this->selectedConversation = null;
            $this->selectedConversationId = null;
            $this->messages = [];
            $this->body = '';
            $this->activeScreen = 'chat';
        }

        // Refresh sidebar to show unread badge
        $this->loadConversations();

        // Broadcast to the message sender to refresh their sidebar
        // This updates their tick from blue to grey
        $this->broadcastConversationUpdate($conversation, $otherUserId);

        // Close the menu via JS
        $this->dispatch('close-conv-menu', conversationId: $conversationId);
    }

    /**
     * Delete conversation for CURRENT USER only (not both users).
     * Uses conversation_user pivot table to track per-user deletion.
     */
    public function deleteConversation(int $conversationId): void
    {
        // Verify user has access
        $authId = auth()->id();
        $conversation = Conversation::where('id', $conversationId)
            ->where(fn($q) => $q->where('user_one_id', $authId)->orWhere('user_two_id', $authId))
            ->first();

        if (!$conversation) return;

        // Get the last message ID before deletion
        $lastMessageId = Message::where('conversation_id', $conversationId)->max('id');

        // Mark as deleted for THIS user only (not both users)
        DB::table('conversation_user')->updateOrInsert(
            [
                'conversation_id' => $conversationId,
                'user_id' => $authId,
            ],
            [
                'is_deleted' => true,
                'deleted_at' => now(),
                'last_seen_message_id' => $lastMessageId, // Store last message ID
                'updated_at' => now(),
            ]
        );

        // Close the conversation if it's currently selected
        if ($this->selectedConversationId === $conversationId) {
            $this->selectedConversation = null;
            $this->selectedConversationId = null;
            $this->messages = [];
            $this->body = '';
            $this->activeScreen = 'chat';
        }

        // Refresh sidebar (deleted conversation won't appear for THIS user)
        $this->loadConversations();

        // Close the menu
        $this->dispatch('close-conv-menu', conversationId: $conversationId);
    }

    /**
     * Toggle favorite status for a conversation.
     */
    public function toggleFavorite(int $conversationId): void
    {
        $authId = auth()->id();
        
        // Verify user has access to this conversation
        $conversation = Conversation::where('id', $conversationId)
            ->where(fn($q) => $q->where('user_one_id', $authId)->orWhere('user_two_id', $authId))
            ->first();

        if (!$conversation) return;

        // Get current favorite status
        $record = DB::table('conversation_user')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $authId)
            ->first();

        $currentStatus = $record ? $record->is_favorite : false;

        // Toggle the favorite status
        DB::table('conversation_user')->updateOrInsert(
            [
                'conversation_id' => $conversationId,
                'user_id' => $authId,
            ],
            [
                'is_favorite' => !$currentStatus,
                'updated_at' => now(),
            ]
        );

        // Refresh conversations to update the UI
        $this->loadConversations();

        // Close the menu
        $this->dispatch('close-conv-menu', conversationId: $conversationId);
    }

    /**
     * Persist a user-defined conversation order.
     *
     * Called from JS after drag-and-drop reorder.
     * $orderedIds: flat array of conversation IDs in the new order,
     *              covering BOTH favorites and regular (each section ordered independently).
     * We write a sort_order value (0-based index) per section so the two sections
     * stay separate while still being individually sortable.
     *
     * @param  array<int>  $favIds      Favorite conv IDs in new order
     * @param  array<int>  $regularIds  Regular conv IDs in new order
     */
    public function reorderConversations(array $favIds, array $regularIds): void
    {
        $authId = auth()->id();

        // Build [conv_id => sort_order] maps for each section
        $updates = [];

        foreach ($favIds as $idx => $convId) {
            $updates[(int) $convId] = $idx;
        }
        foreach ($regularIds as $idx => $convId) {
            $updates[(int) $convId] = $idx;
        }

        if (empty($updates)) return;

        // Verify all conversations belong to this user (security check)
        $validIds = DB::table('conversations')
            ->where('status', 'accepted')
            ->where(fn ($q) => $q->where('user_one_id', $authId)->orWhere('user_two_id', $authId))
            ->whereIn('id', array_keys($updates))
            ->pluck('id')
            ->all();

        foreach ($validIds as $convId) {
            // UPDATE only — never overwrite is_favorite here (same-section reorder)
            $affected = DB::table('conversation_user')
                ->where('conversation_id', $convId)
                ->where('user_id', $authId)
                ->update(['sort_order' => $updates[$convId], 'updated_at' => now()]);

            if ($affected === 0) {
                // Row doesn't exist yet — safe to insert with is_favorite=false
                // because this path is only reached for same-section reorder
                DB::table('conversation_user')->insertOrIgnore([
                    'conversation_id' => $convId,
                    'user_id'         => $authId,
                    'sort_order'      => $updates[$convId],
                    'is_favorite'     => false,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }

        // Re-load so $favorites/$conversations reflect new order
        $this->loadConversations();
    }

    /**
     * Called when a conversation is dragged ACROSS sections (regular ↔ favourites).
     * Sets is_favorite + sort_order for ALL conversations in one DB transaction
     * so the two values can never get out of sync.
     */
    public function reorderWithFavoriteToggle(
        int   $conversationId,
        bool  $makeFavorite,
        array $favIds,
        array $regularIds
    ): void {
        $authId = auth()->id();

        $exists = Conversation::where('id', $conversationId)
            ->where('status', 'accepted')
            ->where(fn ($q) => $q->where('user_one_id', $authId)
                                 ->orWhere('user_two_id', $authId))
            ->exists();

        if (! $exists) return;

        // Build full update map: conv_id → [sort_order, is_favorite]
        // This writes BOTH columns together so is_favorite is never left stale.
        $allUpdates = [];
        foreach ($favIds as $idx => $convId) {
            $allUpdates[(int) $convId] = ['sort_order' => $idx, 'is_favorite' => true];
        }
        foreach ($regularIds as $idx => $convId) {
            $allUpdates[(int) $convId] = ['sort_order' => $idx, 'is_favorite' => false];
        }

        // Security: only update convs this user owns
        $validIds = DB::table('conversations')
            ->where('status', 'accepted')
            ->where(fn ($q) => $q->where('user_one_id', $authId)->orWhere('user_two_id', $authId))
            ->whereIn('id', array_keys($allUpdates))
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($validIds, $allUpdates, $authId) {
            foreach ($validIds as $convId) {
                DB::table('conversation_user')->updateOrInsert(
                    ['conversation_id' => $convId, 'user_id' => $authId],
                    [
                        'sort_order'  => $allUpdates[$convId]['sort_order'],
                        'is_favorite' => $allUpdates[$convId]['is_favorite'],
                        'updated_at'  => now(),
                    ]
                );
            }
        });

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
        // NOTE: the orWhere must be nested inside a single where() closure to
        // prevent it from escaping the outer `status = 'accepted'` constraint.
        $conversations = Conversation::where('status', 'accepted')
            ->where(function ($q) use ($onlineUserIds) {
                $q->where(function ($inner) use ($onlineUserIds) {
                    $inner->where('user_one_id', auth()->id())
                          ->whereIn('user_two_id', $onlineUserIds);
                })->orWhere(function ($inner) use ($onlineUserIds) {
                    $inner->where('user_two_id', auth()->id())
                          ->whereIn('user_one_id', $onlineUserIds);
                });
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
        // Authorization: only the recipient (user_two_id) can view this request
        $conversation = Conversation::with(['userOne'])->findOrFail($requestId);
        abort_unless($conversation->user_two_id === auth()->id(), 403);

        $this->selectedRequest      = $conversation;
        $this->selectedConversation = null;
        $this->activeScreen         = 'request-preview';
    }

    public function acceptRequest(int $conversationId): void
    {
        // Authorization: only the recipient (user_two_id) can accept
        $conversation = Conversation::findOrFail($conversationId);
        abort_unless($conversation->user_two_id === auth()->id(), 403);

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
        // Authorization: only the recipient (user_two_id) can reject
        $conversation = Conversation::findOrFail($conversationId);
        abort_unless($conversation->user_two_id === auth()->id(), 403);

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

            $mime     = $this->attachment->getMimeType();
            $type     = str_starts_with($mime, 'image/') ? 'image' : 'file';
            $origName = $this->attachment->getClientOriginalName();

            $filePath         = $this->attachment->store('chat-files', 'public');
            $this->attachment = null;
        }

        $message = Message::create([
            'conversation_id' => $this->selectedConversation->id,
            'sender_id'       => auth()->id(),
            'body'            => trim($this->body),
            'type'            => $type,
            'file_path'       => $filePath,
            'file_name'       => $origName ?? null,
            'reply_to_id'     => $this->replyingToMessageId,
        ]);

        $message->load('sender', 'forwardedFrom.sender', 'replyTo.sender');

        $this->messages[] = $message;

        $this->selectedConversation->update(['last_message_at' => now()]);

        // Un-delete conversation for recipient if they had deleted it
        $recipientId = $this->selectedConversation->user_one_id === auth()->id()
            ? $this->selectedConversation->user_two_id
            : $this->selectedConversation->user_one_id;

        DB::table('conversation_user')
            ->where('conversation_id', $this->selectedConversation->id)
            ->where('user_id', $recipientId)
            ->where('is_deleted', true)
            ->update([
                'is_deleted' => false,
                'deleted_at' => null,
            ]);

        $this->loadConversations();

        broadcast(new MessageSent($message))->toOthers();

        $this->body = '';
        $this->cancelReply();

        // Draft sent — remove from store
        unset($this->draftBodies[(string) $this->selectedConversation->id]);

        // Refresh files and photos list if image/file was sent
        if ($type === 'image') {
            $this->loadConversationPhotos($this->selectedConversation->id);
        }
        if ($type === 'file' || $type === 'image') {
            $this->loadConversationFiles($this->selectedConversation->id);
        }

        $this->dispatch('message-sent');
    }

    /**
     * Send a file/photo to any accepted conversation by dropping it on the
     * sidebar. Called from JS drag-and-drop: the file is uploaded via Livewire's
     * standard WithFileUploads pipeline into $dropAttachment, then this method
     * stores it and creates the Message — without switching the active conversation.
     */
    public $dropAttachment = null;

    public function sendDroppedFile(int $conversationId): void
    {
        $authId = auth()->id();

        // ── Authorization: caller must be a participant ────────────────────
        $conversation = Conversation::where('id', $conversationId)
            ->where('status', 'accepted')
            ->where(fn ($q) => $q->where('user_one_id', $authId)
                                 ->orWhere('user_two_id', $authId))
            ->first();

        if (! $conversation) {
            $this->dispatch('drop-send-error', message: 'Conversation not found.');
            return;
        }

        if (! $this->dropAttachment) {
            $this->dispatch('drop-send-error', message: 'No file received.');
            return;
        }

        // ── Validate ───────────────────────────────────────────────────────
        $this->validate([
            'dropAttachment' => [
                'required',
                'file',
                'max:5120',
                'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,zip,rar,txt,pptx',
            ],
        ]);

        $mime     = $this->dropAttachment->getMimeType();
        $type     = str_starts_with($mime, 'image/') ? 'image' : 'file';
        $origName = $this->dropAttachment->getClientOriginalName();
        $filePath = $this->dropAttachment->store('chat-files', 'public');
        $this->dropAttachment = null;

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $authId,
            'body'            => '',
            'type'            => $type,
            'file_path'       => $filePath,
            'file_name'       => $origName,
            'reply_to_id'     => null,
        ]);

        $message->load('sender', 'forwardedFrom.sender', 'replyTo.sender');

        $conversation->update(['last_message_at' => now()]);

        // Un-delete conversation for recipient if they had deleted it
        $recipientId = $conversation->user_one_id === $authId
            ? $conversation->user_two_id
            : $conversation->user_one_id;

        DB::table('conversation_user')
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $recipientId)
            ->where('is_deleted', true)
            ->update(['is_deleted' => false, 'deleted_at' => null]);

        // If the dropped-on conversation is the currently open one, append it live
        if ((int) $this->selectedConversationId === $conversation->id) {
            $this->messages[] = $message;
            if ($type === 'image') $this->loadConversationPhotos($conversation->id);
            if ($type !== 'text')  $this->loadConversationFiles($conversation->id);
            $this->dispatch('message-sent');
        }

        $this->loadConversations();
        broadcast(new MessageSent($message))->toOthers();

        $this->dispatch('drop-send-success',
            conversationId: $conversationId,
            fileName: basename($filePath),
            fileType: $type
        );
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

        // ── Active conversation: append message ────────────────────────────
        $message = Message::withTrashed()
            ->with(['sender', 'forwardedFrom.sender', 'replyTo.sender'])
            ->find($messageData['id']);

        if (! $message) return;

        foreach ($this->messages as $existing) {
            if ($existing->id === $message->id) return; // deduplicate
        }

        $this->messages[] = $message;                                                                                         

        $now = now();

        // Always mark as delivered (recipient's device received it)
        if (! $message->delivered_at) {
            $message->update(['delivered_at' => $now]);
            broadcast(new MessageDelivered(
                $message->id,
                $message->conversation_id,
                $now->toISOString(),
                $message->sender_id
            ));
        }

        // Only mark as READ if the chat window is actually open and visible.
        // activeScreen === 'chat' means the user is looking at this conversation.
        if ($this->activeScreen === 'chat') {
            $message->update(['read_at' => $now, 'is_read' => true]);
            broadcast(new MessageRead(
                $message->conversation_id,
                auth()->id(),
                $now->toISOString(),
                $message->sender_id
            ));
        }

        // Do NOT call loadConversations() here — it triggers a full sidebar
        // re-render that wipes JS-managed unread badges on other conversations.
        // JS (updateSidebarForNewMessage) already updated the preview text and
        // the active conversation never shows a badge anyway.
        // Only bump last_message_at in the in-memory conversation so sidebar
        // ordering stays correct after the next natural loadConversations() call.
        if ($this->selectedConversation) {
            $this->selectedConversation->last_message_at = $now;
        }

        // Refresh files/photos if incoming message has attachment
        if ($message->type === 'image') {
            $this->loadConversationPhotos($incomingConvId);
        }
        if ($message->type === 'file' || $message->type === 'image') {
            $this->loadConversationFiles($incomingConvId);
        }

        $this->dispatch('scroll-to-bottom');
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

        // Resolve the recipient so MessageDeleted can dual-broadcast to both
        // chat.{id} (active conversation) and user.{id} (any other screen).
        $conversation = Conversation::find($conversationId);
        $recipientId  = $conversation
            ? ($conversation->user_one_id === auth()->id()
                ? $conversation->user_two_id
                : $conversation->user_one_id)
            : 0;

        broadcast(new MessageDeleted($messageId, $conversationId, $recipientId))->toOthers();

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
    | EDIT MESSAGE
    |--------------------------------------------------------------------------
    */

    /** Called from JS to enter edit mode for a message. */
    public function startEdit(int $messageId): void
    {
        $message = Message::where('id', $messageId)
            ->where('sender_id', auth()->id())
            ->where('type', 'text')
            ->whereNull('deleted_at')
            ->first();

        if (! $message) return;

        $this->editingMessageId = $messageId;
        $this->editBody         = $message->body ?? '';
    }

    /** Save the edited message body. */
    public function updateMessage(): void
    {
        if (! $this->editingMessageId) return;

        $message = Message::where('id', $this->editingMessageId)
            ->where('sender_id', auth()->id())
            ->where('type', 'text')
            ->whereNull('deleted_at')
            ->firstOrFail();

        $newBody = trim($this->editBody);
        if ($newBody === '') return; // don't allow empty

        $now = now();
        $message->update([
            'body'      => $newBody,
            'edited_at' => $now,
        ]);

        // Update in-memory array so the UI reflects changes immediately
        foreach ($this->messages as $i => $msg) {
            if ($msg->id === $this->editingMessageId) {
                $this->messages[$i]->body      = $newBody;
                $this->messages[$i]->edited_at = $now;
                break;
            }
        }

        // Broadcast edit to the other participant in realtime
        $conversation = $this->selectedConversation;
        if ($conversation) {
            $recipientId = $conversation->user_one_id === auth()->id()
                ? $conversation->user_two_id
                : $conversation->user_one_id;

            broadcast(new MessageEdited(
                $message->id,
                $message->conversation_id,
                $newBody,
                $now->toISOString(),
                $recipientId,
            ))->toOthers();
        }

        $this->cancelEdit();
    }

    /** Cancel edit mode without saving. */
    public function cancelEdit(): void
    {
        $this->editingMessageId = null;
        $this->editBody         = '';
    }

    /*
    |--------------------------------------------------------------------------
    | LOAD MORE (older messages)
    |--------------------------------------------------------------------------
    */

    /**
     * Called from JS IntersectionObserver when the top sentinel enters view.
     * Prepends 30 messages older than the current oldest loaded message.
     * Dispatches 'prepend-messages' with the new messages so JS can insert
     * them into the DOM without triggering a full Livewire re-render (which
     * would scroll the window back to bottom).
     */
    public function loadOlderMessages(): void
    {
        if (! $this->selectedConversationId || ! $this->oldestLoadedMsgId) return;

        $authId = auth()->id();

        // Check if user has deleted this conversation before (filter old messages)
        $deletionRecord = DB::table('conversation_user')
            ->where('conversation_id', $this->selectedConversationId)
            ->where('user_id', $authId)
            ->whereNotNull('last_seen_message_id') // ← Check if there was a deletion, regardless of current status
            ->first();

        $olderQuery = Message::withTrashed()
            ->where('conversation_id', $this->selectedConversationId)
            ->where('id', '<', $this->oldestLoadedMsgId);

        // If user had deleted conversation, don't load messages before deletion
        if ($deletionRecord && $deletionRecord->last_seen_message_id) {
            $olderQuery->where('id', '>', $deletionRecord->last_seen_message_id);
        }

        $older = $olderQuery
            ->with(['sender', 'forwardedFrom.sender', 'replyTo.sender', 'reactions.user'])
            ->latest()
            ->take(30)
            ->get()
            ->reverse()
            ->values();

        if ($older->isEmpty()) {
            $this->hasMoreMessages  = false;
            $this->dispatch('no-more-messages');
            return;
        }

        // Prepend to in-memory messages array
        $this->messages = array_merge($older->all(), $this->messages);

        // Update tracking state
        $this->oldestLoadedMsgId = $older->first()->id;
        
        $hasMoreQuery = Message::withTrashed()
            ->where('conversation_id', $this->selectedConversationId)
            ->where('id', '<', $this->oldestLoadedMsgId);
        
        // If user had deleted conversation, don't count messages before deletion
        if ($deletionRecord && $deletionRecord->last_seen_message_id) {
            $hasMoreQuery->where('id', '>', $deletionRecord->last_seen_message_id);
        }
        
        $this->hasMoreMessages = $hasMoreQuery->exists();

        // Dispatch to JS with serializable payload
        $payload = $older->map(fn($m) => [
            'id'           => $m->id,
            'html_id'      => 'msg-' . $m->id,
        ])->values()->all();

        $this->dispatch('prepend-messages', anchorId: $older->last()->id, hasMore: $this->hasMoreMessages);
    }

    /*
    |--------------------------------------------------------------------------
    | MESSAGE SEARCH
    |--------------------------------------------------------------------------
    */

    /**
     * Search messages within the currently open conversation.
     * Called via wire:model.live on the search input.
     * Results are plain arrays so Livewire never serializes Eloquent objects.
     */
    public function updatedMsgSearch(): void
    {
        $term = trim($this->msgSearch);

        if (mb_strlen($term) < 2 || ! $this->selectedConversationId) {
            $this->msgSearchResults = [];
            $this->dispatch('msg-search-results', term: '', results: []);
            return;
        }

        $authId = auth()->id();

        // Check if user has deleted this conversation before (to filter old messages)
        $deletionRecord = DB::table('conversation_user')
            ->where('conversation_id', $this->selectedConversationId)
            ->where('user_id', $authId)
            ->whereNotNull('last_seen_message_id')
            ->first();

        $messageQuery = Message::withTrashed()
            ->where('conversation_id', $this->selectedConversationId)
            ->where('type', 'text')
            ->whereNull('deleted_at')
            ->where('body', 'like', '%' . $term . '%');

        // If user had deleted conversation, only search messages AFTER deletion
        if ($deletionRecord && $deletionRecord->last_seen_message_id) {
            $messageQuery->where('id', '>', $deletionRecord->last_seen_message_id);
        }

        $results = $messageQuery
            ->with('sender:id,name,profile_image')
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn($m) => [
                'id'          => $m->id,
                'body'        => $m->body,
                'sender_name' => $m->sender?->name ?? 'Unknown',
                'sender_id'   => $m->sender_id,
                'is_mine'     => $m->sender_id === auth()->id(),
                'avatar_url'  => $m->sender?->profile_image
                    ? \Illuminate\Support\Facades\Storage::url($m->sender->profile_image)
                    : '',
                'avatar_color' => \App\Helpers\AvatarColor::gradient($m->sender_id), // Add avatar color
                'raw_date'    => $m->created_at->toISOString(),
                'created_at'  => $m->created_at->setTimezone('Asia/Kolkata')->format('M j, g:i A'),
                'date_short'  => $m->created_at->setTimezone('Asia/Kolkata')->format('d-m'),
            ])
            ->values()
            ->all();

        $this->msgSearchResults = $results;
        $this->dispatch('msg-search-results', term: $term, results: $results);
    }

    public function clearMsgSearch(): void
    {
        $this->msgSearch        = '';
        $this->msgSearchResults = [];
    }

    /**
     * Load messages around a specific message ID so JS can scroll to it.
     * Fetches 50 messages ending at the target message.
     */
    public function loadMessageContext(int $messageId): void
    {
        if (! $this->selectedConversationId) return;

        // Verify message belongs to current conversation
        $target = Message::withTrashed()
            ->where('id', $messageId)
            ->where('conversation_id', $this->selectedConversationId)
            ->first();

        if (! $target) return;

        // Load 50 messages up to and including the target
        $this->messages = Message::withTrashed()
            ->where('conversation_id', $this->selectedConversationId)
            ->where('id', '<=', $messageId)
            ->with(['sender', 'forwardedFrom.sender', 'replyTo.sender', 'reactions.user'])
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | EMOJI REACTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Toggle a reaction on a message.
     * If the user already reacted with this emoji → remove it.
     * If not → add it.
     * Then reload the reactions for that message in the in-memory array.
     */
    public function toggleReaction(int $messageId, string $emoji): void
    {
        if (! $this->selectedConversationId) return;

        // C-6: Verify message belongs to the currently open conversation
        abort_unless(
            Message::where('id', $messageId)
                ->where('conversation_id', $this->selectedConversationId)
                ->exists(),
            403
        );

        $emoji = mb_substr(trim($emoji), 0, 10); // sanitise length
        if ($emoji === '') return;

        $userId   = auth()->id();
        $existing = MessageReaction::where('message_id', $messageId)
            ->where('user_id', $userId)
            ->where('emoji', $emoji)
            ->first();

        if ($existing) {
            // Same emoji tapped again → toggle off (unreact)
            $existing->delete();
        } else {
            // Different emoji or no reaction yet:
            // Remove any previous reaction by this user on this message (one reaction per user)
            MessageReaction::where('message_id', $messageId)
                ->where('user_id', $userId)
                ->delete();

            MessageReaction::create([
                'message_id' => $messageId,
                'user_id'    => $userId,
                'emoji'      => $emoji,
            ]);
        }

        // Refresh the reactions on the in-memory message
        foreach ($this->messages as $i => $msg) {
            if ($msg->id === $messageId) {
                $updatedReactions = MessageReaction::where('message_id', $messageId)
                    ->with('user:id,name')
                    ->get();

                $this->messages[$i]->setRelation('reactions', $updatedReactions);

                $reactionsPayload = $updatedReactions->map(fn($r) => [
                    'id'        => $r->id,
                    'emoji'     => $r->emoji,
                    'user_id'   => $r->user_id,
                    'user_name' => $r->user->name ?? '',
                ])->values()->toArray();

                // Update the sender's own DOM immediately via a JS event
                // (reactions container uses wire:ignore so Livewire won't morph it)
                $this->dispatch('reactions-updated', messageId: $messageId, reactions: $reactionsPayload);

                // Broadcast to the other participants
                broadcast(new MessageReactionUpdated(
                    $messageId,
                    $this->selectedConversationId,
                    $reactionsPayload
                ))->toOthers();

                break;
            }
        }
    }

    public function openForwardModal(int $messageId): void
    {
        $this->forwardingMessageId    = $messageId;
        $this->showForwardModal       = true;
        $this->forwardSearch          = '';
        $this->forwardExtraText       = '';
        $this->forwardTargets         = [];
        $this->forwardSelectedTarget  = [];
        $this->forwardingMessagePreview = [];

        $msg = Message::withTrashed()->with('sender')->find($messageId);
        if ($msg) {
            $this->forwardingMessagePreview = [
                'sender_name'    => $msg->sender->name ?? 'Unknown',
                'sender_initial' => strtoupper(substr($msg->sender->name ?? '?', 0, 1)),
                'body'           => $msg->deleted_at ? '' : ($msg->body ?? ''),
                'type'           => $msg->type ?? 'text',
                'sent_at'        => $msg->created_at->format('n/j/Y g:i A'),
            ];
        }
        // Do NOT call loadForwardTargets() here — list is hidden until user types
    }

    public function closeForwardModal(): void
    {
        $this->showForwardModal         = false;
        $this->forwardingMessageId      = null;
        $this->forwardExtraText         = '';
        $this->forwardingMessagePreview = [];
        $this->forwardSelectedTarget    = [];
        $this->forwardTargets           = [];
        $this->forwardSearch            = '';
    }

    public function updatedForwardSearch(): void
    {
        $this->loadForwardTargets();
    }

    /**
     * Select a conversation as the forward recipient — does NOT send yet.
     */
    public function selectForwardTarget(int $conversationId): void
    {
        foreach ($this->forwardTargets as $target) {
            if ((int) $target['id'] === $conversationId) {
                $this->forwardSelectedTarget = $target;
                break;
            }
        }
        // Clear search + results after selection
        $this->forwardSearch  = '';
        $this->forwardTargets = [];
    }

    /**
     * Remove the currently selected recipient chip.
     */
    public function removeForwardTarget(): void
    {
        $this->forwardSelectedTarget = [];
    }

    /**
     * Builds $forwardTargets as a PLAIN ARRAY so Livewire never tries
     * to serialize Eloquent Collections through array_merge.
     */
    private function loadForwardTargets(): void
    {
        $userId = auth()->id();
        $trimmed = trim($this->forwardSearch);

        // Don't show anything until the user has typed at least 1 character
        if (mb_strlen($trimmed) < 1) {
            $this->forwardTargets = [];
            return;
        }

        $term = '%' . $trimmed . '%';

        $query = Conversation::query()
            ->where('status', 'accepted')
            ->where(fn($q) => $q->where('user_one_id', $userId)->orWhere('user_two_id', $userId))
            ->with(['userOne', 'userTwo']);

        $query->where(function ($q) use ($term, $userId) {
            $q->whereHas('userOne', fn($sq) => $sq->where('id', '!=', $userId)->where('name', 'like', $term))
              ->orWhereHas('userTwo', fn($sq) => $sq->where('id', '!=', $userId)->where('name', 'like', $term));
        });

        $this->forwardTargets = $query->limit(10)->get()->map(function ($conv) {
            $other = $conv->otherUser();
            return [
                'id'            => $conv->id,
                'other_name'    => $other->name,
                'other_initial' => strtoupper(substr($other->name, 0, 1)),
                'avatar_url'    => $other->profile_image ? Storage::url($other->profile_image) : null,
            ];
        })->toArray();
    }

    /**
     * Forward message to a target conversation.
     */
    public function forwardTo(int $targetConversationId): void
    {
        if (! $this->forwardingMessageId) return;

        // Use the explicitly selected target if available, fallback to param
        $convId = !empty($this->forwardSelectedTarget)
            ? (int) $this->forwardSelectedTarget['id']
            : $targetConversationId;

        $authId = auth()->id();

        // C-5: Verify source message belongs to a conversation the user is in
        $original = Message::withTrashed()->findOrFail($this->forwardingMessageId);
        abort_unless(
            Conversation::where('id', $original->conversation_id)
                ->where(fn($q) => $q->where('user_one_id', $authId)->orWhere('user_two_id', $authId))
                ->exists(),
            403
        );

        // C-4: Verify target conversation belongs to the user
        abort_unless(
            Conversation::where('id', $convId)
                ->where(fn($q) => $q->where('user_one_id', $authId)->orWhere('user_two_id', $authId))
                ->exists(),
            403
        );

        $extraText = trim($this->forwardExtraText);

        // Single message: optional text in body, original file forwarded
        $newMessage = Message::create([
            'conversation_id'   => $convId,
            'sender_id'         => auth()->id(),
            'body'              => $extraText ?: ($original->deleted_at ? null : $original->body),
            'type'              => $original->deleted_at ? 'text' : $original->type,
            'file_path'         => $original->deleted_at ? null : $original->file_path,
            'forwarded_from_id' => $original->id,
        ]);

        $newMessage->load('sender', 'forwardedFrom.sender');

        $conv = Conversation::findOrFail($convId);
        $conv->update(['last_message_at' => now()]);
        broadcast(new MessageSent($newMessage))->toOthers();

        if ((int) $convId === (int) $this->selectedConversationId) {
            $this->messages[] = $newMessage;
        }

        $this->loadConversations();
        $this->closeForwardModal();
    }

    /**
     * Forward a specific message directly to a conversation by drag-and-drop.
     * No modal — called directly from JS when user drops a message bubble onto a sidebar conv.
     */
    public function forwardMessageDirect(int $messageId, int $targetConversationId): void
    {
        $authId = auth()->id();

        $original = Message::withTrashed()->find($messageId);
        if (! $original) return;

        // Verify source conversation access
        abort_unless(
            Conversation::where('id', $original->conversation_id)
                ->where(fn($q) => $q->where('user_one_id', $authId)->orWhere('user_two_id', $authId))
                ->exists(),
            403
        );

        // Verify target conversation access
        $targetConv = Conversation::where('id', $targetConversationId)
            ->where('status', 'accepted')
            ->where(fn($q) => $q->where('user_one_id', $authId)->orWhere('user_two_id', $authId))
            ->first();

        if (! $targetConv) {
            $this->dispatch('drop-send-error', message: 'Conversation not found.');
            return;
        }

        $newMessage = Message::create([
            'conversation_id'   => $targetConv->id,
            'sender_id'         => $authId,
            'body'              => $original->deleted_at ? '' : ($original->body ?? ''),
            'type'              => $original->deleted_at ? 'text' : $original->type,
            'file_path'         => $original->deleted_at ? null : $original->file_path,
            'forwarded_from_id' => $original->id,
        ]);

        $newMessage->load('sender', 'forwardedFrom.sender');
        $targetConv->update(['last_message_at' => now()]);

        // Un-delete for recipient
        $recipientId = $targetConv->user_one_id === $authId
            ? $targetConv->user_two_id
            : $targetConv->user_one_id;
        DB::table('conversation_user')
            ->where('conversation_id', $targetConv->id)
            ->where('user_id', $recipientId)
            ->where('is_deleted', true)
            ->update(['is_deleted' => false, 'deleted_at' => null]);

        if ((int) $targetConv->id === (int) $this->selectedConversationId) {
            $this->messages[] = $newMessage;
            $this->dispatch('message-sent');
        }

        $this->loadConversations();
        broadcast(new MessageSent($newMessage))->toOthers();

        $this->dispatch('drop-send-success',
            conversationId: $targetConv->id,
            fileName: $original->type !== 'text' ? basename($original->file_path ?? '') : '',
            fileType: $original->type
        );
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

        $avatarColor = \App\Helpers\AvatarColor::gradient(auth()->id());
        
        // Debug log
        \Illuminate\Support\Facades\Log::info('[Typing] Broadcasting with color:', [
            'userId' => auth()->id(),
            'userName' => auth()->user()->name,
            'avatarColor' => $avatarColor,
            'isTyping' => $isTyping,
        ]);

        broadcast(new UserTyping(
            $this->selectedConversation->id,
            auth()->id(),
            auth()->user()->name,
            auth()->user()->profile_image
                ? \Illuminate\Support\Facades\Storage::url(auth()->user()->profile_image)
                : '',
            $avatarColor, // Add avatar color
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

    /**
     * Called when the user taps the mobile back button.
     * Sets activeScreen to 'empty' so incoming messages are NOT marked as read
     * until the user actually re-opens the conversation.
     */
    public function closeChatView(): void
    {
        $this->activeScreen = 'empty';
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

