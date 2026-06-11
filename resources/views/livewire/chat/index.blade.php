{{-- resources/views/livewire/chat/index.blade.php --}}
<div
    class="teams-chat-root"
    data-conversation="{{ $selectedConversationId }}"
    wire:key="chat-root">

{{-- ══════════════════════════════════════════════════════
     TOP BAR
══════════════════════════════════════════════════════ --}}
<div class="teams-topbar-wrapper">
  <div class="teams-topbar">

    <div class="topbar-left">
      <div class="topbar-brand">
        <div class="topbar-brand-icon" aria-hidden="true">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
        </div>
        <h1 class="topbar-title">Baseline Chat</h1>
      </div>
    </div>

    {{-- Global Search --}}
    <div class="topbar-center">
      <div class="global-search-wrap" id="globalSearchWrap">
        <svg class="search-icon global-search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search people…"
               class="global-search-input" id="globalSearchInput" autocomplete="off"
               aria-label="Search people" aria-autocomplete="list" aria-controls="globalSearchResults"
               aria-expanded="{{ !empty($searchResults) ? 'true' : 'false' }}">
        @if($search)
          <button type="button" wire:click="clearSearch" class="search-clear-btn" aria-label="Clear search">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        @endif
        @if(!empty($searchResults))
        <div class="global-search-results" id="globalSearchResults" role="listbox" aria-label="Search results">
          @foreach($searchResults as $user)
            @php
              $existingConv    = auth()->user()->getConversationWith($user->id);
              $alreadyAccepted = $existingConv && $existingConv->status === 'accepted';
              $alreadyPending  = $existingConv && $existingConv->status === 'pending';
              $iSent           = $alreadyPending && $existingConv->user_one_id === auth()->id();
              $iReceived       = $alreadyPending && $existingConv->user_two_id === auth()->id();
              $isAdminConv     = auth()->user()->is_admin || $user->is_admin;
            @endphp
            <div class="global-search-item" role="option">
              <div class="global-search-avatar">{{ strtoupper(substr($user->name,0,1)) }}</div>
              <div class="global-search-info">
                <div class="global-search-name">{{ $user->name }}</div>
                <div class="global-search-email">{{ $user->email }}</div>
              </div>
              <div class="global-search-action">
                @if($alreadyAccepted)
                  <button type="button" wire:click="openExistingConversation({{ $user->id }})" class="search-action-btn search-action-btn--open">Open Chat</button>
                @elseif($iSent)
                  <span class="search-status-badge search-status-badge--pending">Request Sent</span>
                @elseif($iReceived)
                  <button type="button" wire:click="acceptRequest({{ $existingConv->id }})" class="search-action-btn search-action-btn--accept">Accept</button>
                @else
                  <button type="button" wire:click="startConversation({{ $user->id }})" class="search-action-btn">{{ $isAdminConv ? 'Start Chat' : 'Send Request' }}</button>
                @endif
              </div>
            </div>
          @endforeach
        </div>
        @endif
      </div>
    </div>

    {{-- Profile --}}
    <div class="topbar-right">
      <div class="profile-wrap" id="profileWrap">
        <button class="profile-btn" id="profileBtn" aria-haspopup="true" aria-expanded="false" aria-controls="profileDropdown">
          <div class="profile-avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()->name,0,1)) }}<span class="profile-status-dot"></span></div>
          <div class="profile-meta">
            <div class="profile-name">{{ auth()->user()->name }}</div>
            <div class="profile-role">{{ auth()->user()->is_admin ? 'Admin' : 'Member' }}</div>
          </div>
          <svg class="profile-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
        </button>
        <div class="profile-dropdown" id="profileDropdown" role="menu">
          <div class="dropdown-user-card">
            <div class="dropdown-avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}<span class="dropdown-status-dot"></span></div>
            <div class="dropdown-user-info">
              <div class="dropdown-user-name">{{ auth()->user()->name }}</div>
              <div class="dropdown-user-email">{{ auth()->user()->email }}</div>
              <div class="dropdown-user-badge">
                @if(auth()->user()->is_admin)<span class="badge-admin">Admin</span>@else<span class="badge-member">Member</span>@endif
              </div>
            </div>
          </div>
          <div class="dropdown-divider"></div>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="dropdown-item dropdown-item-danger" role="menuitem" type="submit">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
              Sign out
            </button>
          </form>
        </div>
      </div>
    </div>

  </div>
</div>

{{-- ══════════════════════════════════════════════════════
     MAIN LAYOUT
══════════════════════════════════════════════════════ --}}
<div class="teams-layout" id="teamsLayout">

  {{-- ─────────────────── SIDEBAR ─────────────────── --}}
  <aside class="teams-sidebar" id="teamsSidebar">

    <div class="sidebar-header">
      <div class="sidebar-title-row"><h2 class="sidebar-title">Chat</h2></div>
    </div>

    {{-- Sent Requests --}}
    @if($sentRequests && count($sentRequests) > 0)
    <div class="sent-requests-banner">
      <button type="button" wire:click="openSentRequests"
              class="sent-requests-btn {{ $activeScreen === 'sent-requests' ? 'sent-requests-btn-active' : '' }}">
        <div class="sent-req-left">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2L15 22L11 13L2 9L22 2Z"/></svg>
          <span>Pending Requests</span>
        </div>
        <span class="sent-req-badge">{{ count($sentRequests) }}</span>
      </button>
    </div>
    @endif

    {{-- Incoming Requests --}}
    @if($pendingRequests && count($pendingRequests) > 0)
    <div class="request-section">
      <button type="button" class="request-header" wire:click="toggleRequests" aria-expanded="{{ $showRequests ? 'true' : 'false' }}">
        <div class="request-title">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          <span>Requests</span>
        </div>
        <div class="request-header-right">
          <span class="request-count">{{ count($pendingRequests) }}</span>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="transform:{{ $showRequests ? 'rotate(180deg)' : 'rotate(0)' }};transition:transform .2s"><path d="M6 9l6 6 6-6"/></svg>
        </div>
      </button>
      @if($showRequests)
      <div class="request-list" role="list">
        @foreach($pendingRequests as $request)
        <div class="request-item" role="listitem">
          <button type="button" wire:click="openRequest({{ $request->id }})"
                  class="request-toggle {{ ($activeScreen === 'request-preview' && $selectedRequest?->id === $request->id) ? 'request-toggle-active' : '' }}">
            <div class="request-user">
              <div class="request-avatar">{{ strtoupper(substr($request->userOne->name,0,1)) }}</div>
              <div class="request-user-info">
                <div class="request-user-name">{{ $request->userOne->name }}</div>
                <div class="request-user-text">Wants to connect</div>
              </div>
            </div>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
          </button>
        </div>
        @endforeach
      </div>
      @endif
    </div>
    @endif

    {{-- Conversations List --}}
    <div class="conversations-list" role="list">
      @forelse($conversations as $conversation)
        @php
          $other        = $conversation->otherUser();
          $latest       = $conversation->latestMessage;
          $unread       = $conversation->unreadCountFor(auth()->id());
          $isMineLatest = $latest && $latest->sender_id === auth()->id();
        @endphp
        <div wire:click="selectConversation({{ $conversation->id }})"
             class="conv-item {{ ($selectedConversation && $selectedConversation->id === $conversation->id) ? 'conv-active' : '' }}"
             role="listitem" tabindex="0"
             aria-label="Conversation with {{ $other->name }}"
             wire:key="conv-{{ $conversation->id }}">
          <div class="conv-avatar-wrap">
            <div class="conv-avatar">{{ strtoupper(substr($other->name,0,1)) }}</div>
            <span class="presence-dot" data-presence-uid="{{ $other->id }}" aria-hidden="true"></span>
          </div>
          <div class="conv-info">
            <div class="conv-info-top">
              <span class="conv-name">{{ $other->name }}</span>
              <span class="conv-time">{{ $conversation->last_message_at?->diffForHumans(short:true) ?? '' }}</span>
            </div>
            <div class="conv-bottom-row">
              <p class="conv-preview" id="conv-preview-{{ $conversation->id }}">
                @if($latest)
                  @if($isMineLatest)
                    @include('livewire.chat.partials.tick', ['status' => $latest->deliveryStatus()])
                    <span>{{ $latest->deleted_at ? 'This message was deleted' : ($latest->type !== 'text' ? ($latest->type === 'image' ? '📷 Image' : '📎 File') : \Illuminate\Support\Str::limit($latest->body, 30)) }}</span>
                  @else
                    <span>{{ $latest->deleted_at ? 'This message was deleted' : ($latest->type !== 'text' ? ($latest->type === 'image' ? '📷 Image' : '📎 File') : \Illuminate\Support\Str::limit($latest->body, 30)) }}</span>
                  @endif
                @else
                  <span class="conv-preview-placeholder">Click to open chat</span>
                @endif
              </p>
              @if($unread > 0)
                <span class="unread-badge" id="unread-{{ $conversation->id }}">{{ $unread > 99 ? '99+' : $unread }}</span>
              @else
                <span class="unread-badge" id="unread-{{ $conversation->id }}" style="display:none">0</span>
              @endif
            </div>
          </div>
        </div>
      @empty
        <div class="empty-list" role="status">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" opacity=".4"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          <p>No conversations yet</p>
        </div>
      @endforelse
    </div>

  </aside>

  {{-- ─────────────────── MAIN PANEL ─────────────────── --}}
  <main class="teams-main" id="teamsMain">

    {{-- ══ ACTIVE CHAT ══ --}}
    @if($activeScreen === 'chat' && $selectedConversation)

      {{-- Chat Header --}}
      <div class="chat-header">
        <button class="mobile-back-btn" id="mobileBackBtn" title="Back" aria-label="Back to conversations">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        </button>
        <div class="chat-header-avatar">{{ strtoupper(substr($selectedConversation->otherUser()->name,0,1)) }}</div>
        <div class="chat-header-info">
          <h2 class="chat-header-name">{{ $selectedConversation->otherUser()->name }}</h2>
          <span class="chat-header-status" id="chat-header-status"
                data-presence-uid="{{ $selectedConversation->otherUser()->id }}"
                data-last-seen="{{ $selectedConversation->otherUser()->lastSeenText() }}">
            <span class="status-dot" id="chat-header-status-dot"></span>
            <span id="chat-header-status-text">{{ $selectedConversation->otherUser()->lastSeenText() }}</span>
          </span>
        </div>
      </div>

      {{-- Messages Area --}}
      <div class="messages-area" id="messages-container" role="log" aria-live="polite" aria-label="Messages">
        @forelse($messages as $message)
          @php $isMine = $message->sender_id === auth()->id(); @endphp
          <div class="msg-row {{ $isMine ? 'msg-mine' : 'msg-theirs' }}"
               role="article"
               wire:key="msg-{{ $message->id }}"
               id="msg-{{ $message->id }}">

            @if(!$isMine)
              <div class="msg-avatar">{{ strtoupper(substr($message->sender->name,0,1)) }}</div>
            @endif

            <div class="msg-body-wrap">

              {{-- Forwarded label --}}
              @if($message->forwarded_from_id && !$message->deleted_at)
              <div class="fwd-label {{ $isMine ? 'fwd-label--mine' : '' }}">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 9l4-4 4 4"/><path d="M9 5v10a5 5 0 0 0 5 5h5"/></svg>
                Forwarded
              </div>
              @endif

              {{-- Bubble --}}
              <div class="msg-bubble {{ $isMine ? 'bubble-mine' : 'bubble-theirs' }} {{ $message->deleted_at ? 'bubble-deleted' : '' }}">

                @if($message->deleted_at)
                  <span class="deleted-text">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    This message was deleted
                  </span>

                @elseif($message->type === 'image')
                  <a href="{{ $message->fileUrl() }}" target="_blank" class="msg-img-wrap">
                    <img src="{{ $message->fileUrl() }}" alt="Image" class="msg-image" loading="lazy">
                  </a>
                  @if($message->body)<p class="msg-caption">{{ $message->body }}</p>@endif

                @elseif($message->type === 'file')
                  <a href="{{ $message->fileUrl() }}" download class="msg-file-wrap">
                    <span class="msg-file-icon">
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </span>
                    <span class="msg-file-name">{{ $message->fileName() }}</span>
                    <span class="msg-file-dl">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    </span>
                  </a>
                  @if($message->body)<p class="msg-caption">{{ $message->body }}</p>@endif

                @else
                  <span>{{ $message->body }}</span>
                @endif

                {{-- Timestamp + ticks --}}
                @if(!$message->deleted_at)
                <span class="msg-time-wrap">
                  <span class="msg-time">{{ $message->created_at->format('g:i A') }}</span>
                  @if($isMine)
                    <span class="msg-tick" id="tick-{{ $message->id }}">
                      @include('livewire.chat.partials.tick', ['status' => $message->deliveryStatus()])
                    </span>
                  @endif
                </span>
                @endif

              </div>{{-- /bubble --}}

              {{-- Message Actions (hover menu) --}}
              @if(!$message->deleted_at)
              <div class="msg-actions {{ $isMine ? 'msg-actions--mine' : 'msg-actions--theirs' }}">
                <button type="button" class="msg-action-btn" title="Forward"
                        wire:click="openForwardModal({{ $message->id }})">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 9l4-4 4 4"/><path d="M9 5v10a5 5 0 0 0 5 5h5"/></svg>
                </button>
                @if($isMine)
                <button type="button" class="msg-action-btn msg-action-btn--delete" title="Delete"
                        wire:click="deleteMessage({{ $message->id }})"
                        onclick="return confirm('Delete this message?')">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                </button>
                @endif
              </div>
              @endif

            </div>{{-- /msg-body-wrap --}}

            @if($isMine)
              <div class="msg-avatar msg-avatar-mine">{{ strtoupper(substr($message->sender->name,0,1)) }}</div>
            @endif

          </div>{{-- /msg-row --}}
        @empty
          <div class="empty-messages" role="status">
            <div class="empty-messages-icon">💬</div>
            <h3>No messages yet</h3>
            <p>Send a message to start the conversation</p>
          </div>
        @endforelse

        {{-- Typing Indicator --}}
        <div id="typing-indicator-row" class="typing-indicator-row" style="display:none" aria-live="polite">
          <div class="typing-avatar" id="typing-indicator-avatar"></div>
          <div class="typing-bubble">
            <span class="typing-dot"></span>
            <span class="typing-dot"></span>
            <span class="typing-dot"></span>
          </div>
          <span class="typing-label" id="typing-indicator-label"></span>
        </div>

        <div id="scroll-anchor" aria-hidden="true"></div>
      </div>

      {{-- Input Area --}}
      <div class="chat-input-area">
        {{-- Attachment preview --}}
        @if($attachment)
        <div class="attachment-preview">
          @if(str_starts_with($attachment->getMimeType(), 'image/'))
            <img src="{{ $attachment->temporaryUrl() }}" alt="Preview" class="attachment-thumb">
          @else
            <span class="attachment-file-icon">📎</span>
            <span class="attachment-file-name">{{ $attachment->getClientOriginalName() }}</span>
          @endif
          <button type="button" wire:click="$set('attachment', null)" class="attachment-remove" title="Remove">✕</button>
        </div>
        @endif

        <form wire:submit="sendMessage" class="input-form" enctype="multipart/form-data">
          {{-- File attach button --}}
          <label for="fileInput" class="attach-btn" title="Attach file" aria-label="Attach file">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
          </label>
          <input type="file" id="fileInput" wire:model="attachment"
                 accept="image/*,.pdf,.doc,.docx,.xls,.xlsx"
                 class="file-input-hidden" aria-label="Attach file">

          <input type="text" wire:model="body" placeholder="Type a message…"
                 class="message-input" id="messageInput" autocomplete="off" aria-label="Message input">

          <button type="submit" class="send-btn" title="Send" aria-label="Send message">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
          </button>
        </form>
      </div>

    {{-- ══ INCOMING REQUEST PREVIEW ══ --}}
    @elseif($activeScreen === 'request-preview' && $selectedRequest)
      <div class="request-preview-screen">
        <div class="request-preview-card">
          <div class="request-preview-avatar">{{ strtoupper(substr($selectedRequest->userOne->name,0,1)) }}</div>
          <h2 class="request-preview-title">{{ $selectedRequest->userOne->name }} wants to connect</h2>
          <p class="request-preview-message">Accept to start chatting.</p>
          <div class="request-preview-actions">
            <button wire:click="acceptRequest({{ $selectedRequest->id }})" class="request-accept" wire:loading.attr="disabled">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              <span>Accept</span>
              <span wire:loading wire:target="acceptRequest({{ $selectedRequest->id }})">…</span>
            </button>
            <button wire:click="rejectRequest({{ $selectedRequest->id }})" class="request-reject" wire:loading.attr="disabled">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              <span>Decline</span>
            </button>
          </div>
        </div>
      </div>

    {{-- ══ SENT REQUESTS ══ --}}
    @elseif($activeScreen === 'sent-requests')
      <div class="pending-page">
        <div class="pending-page-header">
          <div class="pending-page-icon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2L15 22L11 13L2 9L22 2Z"/></svg></div>
          <div><h2 class="pending-page-title">Pending Requests</h2><p class="pending-page-subtitle">Waiting for others to accept</p></div>
        </div>
        <div class="pending-grid">
          @forelse($sentRequests as $request)
          <div class="pending-card" wire:key="sent-{{ $request->id }}">
            <div class="pending-card-left">
              <div class="pending-avatar">{{ strtoupper(substr($request->userTwo->name,0,1)) }}</div>
              <div class="pending-user-meta">
                <div class="pending-user-name">{{ $request->userTwo->name }}</div>
                <div class="pending-user-email">{{ $request->userTwo->email }}</div>
                <div class="pending-user-time">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                  Sent {{ $request->created_at->diffForHumans() }}
                </div>
              </div>
            </div>
            <div class="pending-status"><span class="pending-status-badge">Awaiting Response</span></div>
          </div>
          @empty
          <div class="pending-empty"><h3>No pending requests</h3><p>When you send requests, they'll appear here.</p></div>
          @endforelse
        </div>
      </div>

    {{-- ══ EMPTY STATE ══ --}}
    @else
      <div class="empty-state" role="status">
        <div class="empty-state-icon"><svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" opacity=".3"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div>
        <h3 class="empty-state-title">Welcome to Chat</h3>
        <p class="empty-state-sub">Select a conversation or search for someone.</p>
      </div>
    @endif

  </main>
</div>

{{-- ══════════════════════════════════════════════════════
     FORWARD MODAL
══════════════════════════════════════════════════════ --}}
@if($showForwardModal)
<div class="modal-backdrop" wire:click.self="closeForwardModal" role="dialog" aria-modal="true" aria-label="Forward message">
  <div class="modal-box">
    <div class="modal-header">
      <h3 class="modal-title">Forward message</h3>
      <button type="button" class="modal-close" wire:click="closeForwardModal" aria-label="Close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

    {{-- Optional extra text --}}
    <div class="modal-extra-text">
      <input type="text" wire:model="forwardExtraText"
             placeholder="Add a message… (optional)"
             class="modal-search-input">
    </div>

    <div class="modal-search">
      <input type="text" wire:model.live.debounce.200ms="forwardSearch"
             placeholder="Search conversations…"
             class="modal-search-input" autofocus>
    </div>

    <div class="modal-list">
      {{-- $forwardTargets is a plain array: ['id', 'other_name', 'other_initial'] --}}
      @forelse($forwardTargets as $target)
        <button type="button" class="modal-conv-item" wire:click="forwardTo({{ $target['id'] }})">
          <div class="modal-conv-avatar">{{ $target['other_initial'] }}</div>
          <div class="modal-conv-name">{{ $target['other_name'] }}</div>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 9l4-4 4 4"/><path d="M9 5v10a5 5 0 0 0 5 5h5"/></svg>
        </button>
      @empty
        <p class="modal-empty">No conversations found.</p>
      @endforelse
    </div>
  </div>
</div>
@endif

</div>{{-- /teams-chat-root --}}
