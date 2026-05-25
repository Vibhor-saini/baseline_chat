{{--
    resources/views/livewire/chat/index.blade.php
    ─────────────────────────────────────────────
    The data-conversation attribute on the root div lets chat.js know
    which channel to subscribe to without any extra Livewire round-trips.
--}}
<div
    class="teams-chat-root"
    data-conversation="{{ $selectedConversationId }}"
    wire:key="chat-root">

    {{-- ═══════════════════════════════════════════════════
         TOP BAR
    ═══════════════════════════════════════════════════ --}}
    <div class="teams-topbar-wrapper">
        <div class="teams-topbar">

            {{-- Left: Logo / App Name --}}
            <div class="topbar-left">
                <div class="topbar-brand">
                    <div class="topbar-brand-icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                        </svg>
                    </div>
                    <h1 class="topbar-title">Baseline Chat</h1>
                </div>
            </div>

            {{-- Center: Global User Search --}}
            <div class="topbar-center">
                <div class="global-search-wrap" id="globalSearchWrap">

                    <svg class="search-icon global-search-icon" width="15" height="15"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>

                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search people..."
                        class="global-search-input"
                        id="globalSearchInput"
                        autocomplete="off"
                        aria-label="Search people"
                        aria-autocomplete="list"
                        aria-controls="globalSearchResults"
                        aria-expanded="{{ !empty($searchResults) ? 'true' : 'false' }}">

                    {{-- Clear button — only when text is present --}}
                    @if($search)
                        <button
                            type="button"
                            wire:click="clearSearch"
                            class="search-clear-btn"
                            aria-label="Clear search">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    @endif

                    {{-- Search results dropdown --}}
                    @if(!empty($searchResults))
                    <div class="global-search-results" id="globalSearchResults"
                         role="listbox" aria-label="Search results">
                        @foreach($searchResults as $user)
                            @php
                                $existingConv    = auth()->user()->getConversationWith($user->id);
                                $alreadyAccepted = $existingConv && $existingConv->status === 'accepted';
                                $alreadyPending  = $existingConv && $existingConv->status === 'pending';

                                // Check direction of the pending request
                                $iSent    = $alreadyPending && $existingConv->user_one_id === auth()->id();
                                $iReceived = $alreadyPending && $existingConv->user_two_id === auth()->id();

                                $isAdminConversation = auth()->user()->is_admin || $user->is_admin;
                            @endphp

                            <div class="global-search-item" role="option">
                                <div class="global-search-avatar" aria-hidden="true">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>

                                <div class="global-search-info">
                                    <div class="global-search-name">{{ $user->name }}</div>
                                    <div class="global-search-email">{{ $user->email }}</div>
                                </div>

                                <div class="global-search-action">
                                    @if($alreadyAccepted)
                                        {{-- Already connected → open the chat --}}
                                        <button
                                            type="button"
                                            wire:click="openExistingConversation({{ $user->id }})"
                                            class="search-action-btn search-action-btn--open">
                                            Open Chat
                                        </button>

                                    @elseif($iSent)
                                        {{-- Current user already sent a request → show status only --}}
                                        <span class="search-status-badge search-status-badge--pending">
                                            Request Sent
                                        </span>

                                    @elseif($iReceived)
                                        {{-- Current user has received a request from this person → accept --}}
                                        <button
                                            type="button"
                                            wire:click="acceptRequest({{ $existingConv->id }})"
                                            class="search-action-btn search-action-btn--accept">
                                            Accept
                                        </button>

                                    @else
                                        {{-- No relationship yet --}}
                                        <button
                                            type="button"
                                            wire:click="startConversation({{ $user->id }})"
                                            class="search-action-btn">
                                            {{ $isAdminConversation ? 'Start Chat' : 'Send Request' }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @endif

                </div>
            </div>

            {{-- Right: Profile & Logout --}}
            <div class="topbar-right">
                <div class="profile-wrap" id="profileWrap">
                    <button
                        class="profile-btn"
                        id="profileBtn"
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-controls="profileDropdown">
                        <div class="profile-avatar" aria-hidden="true">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            <span class="profile-status-dot"></span>
                        </div>
                        <div class="profile-meta">
                            <div class="profile-name">{{ auth()->user()->name }}</div>
                            <div class="profile-role">{{ auth()->user()->is_admin ? 'Admin' : 'Member' }}</div>
                        </div>
                        <svg class="profile-chevron" width="14" height="14" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M6 9l6 6 6-6" />
                        </svg>
                    </button>

                    <div class="profile-dropdown" id="profileDropdown" role="menu">
                        <div class="dropdown-user-card">
                            <div class="dropdown-avatar" aria-hidden="true">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                <span class="dropdown-status-dot"></span>
                            </div>
                            <div class="dropdown-user-info">
                                <div class="dropdown-user-name">{{ auth()->user()->name }}</div>
                                <div class="dropdown-user-email">{{ auth()->user()->email }}</div>
                                <div class="dropdown-user-badge">
                                    @if(auth()->user()->is_admin)
                                        <span class="badge-admin">Admin</span>
                                    @else
                                        <span class="badge-member">Member</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="dropdown-divider"></div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item dropdown-item-danger" role="menuitem" type="submit">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                    <polyline points="16 17 21 12 16 7" />
                                    <line x1="21" y1="12" x2="9" y2="12" />
                                </svg>
                                Sign out
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         MAIN LAYOUT
    ═══════════════════════════════════════════════════ --}}
    <div class="teams-layout" id="teamsLayout">

        {{-- ───────────────────────────────────────────────
             SIDEBAR
        ─────────────────────────────────────────────── --}}
        <aside class="teams-sidebar" id="teamsSidebar">

            <div class="sidebar-header">
                <div class="sidebar-title-row">
                    <h2 class="sidebar-title">Chat</h2>
                </div>
            </div>

            {{-- ── Sent Requests banner (visible when user has outgoing pending requests) ── --}}
            @if($sentRequests && $sentRequests->count() > 0)
            <div class="sent-requests-banner">
                <button
                    type="button"
                    wire:click="openSentRequests"
                    class="sent-requests-btn {{ $activeScreen === 'sent-requests' ? 'sent-requests-btn-active' : '' }}">
                    <div class="sent-req-left">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M22 2L11 13"/>
                            <path d="M22 2L15 22L11 13L2 9L22 2Z"/>
                        </svg>
                        <span>Pending Requests</span>
                    </div>
                    <span class="sent-req-badge">{{ $sentRequests->count() }}</span>
                </button>
            </div>
            @endif

            {{-- ── Incoming Requests Section ── --}}
            @if($pendingRequests && $pendingRequests->count() > 0)
            <div class="request-section">
                <button
                    type="button"
                    class="request-header"
                    wire:click="toggleRequests"
                    aria-expanded="{{ $showRequests ? 'true' : 'false' }}">
                    <div class="request-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                        </svg>
                        <span>Requests</span>
                    </div>
                    <div class="request-header-right">
                        <span class="request-count">{{ $pendingRequests->count() }}</span>
                        <svg
                            width="14" height="14" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" aria-hidden="true"
                            style="transform: {{ $showRequests ? 'rotate(180deg)' : 'rotate(0)' }}; transition: transform .2s">
                            <path d="M6 9l6 6 6-6"/>
                        </svg>
                    </div>
                </button>

                @if($showRequests)
                <div class="request-list" role="list">
                    @foreach($pendingRequests as $request)
                    <div class="request-item" role="listitem">
                        <button
                            type="button"
                            wire:click="openRequest({{ $request->id }})"
                            class="request-toggle {{ ($activeScreen === 'request-preview' && $selectedRequest?->id === $request->id) ? 'request-toggle-active' : '' }}">
                            <div class="request-user">
                                <div class="request-avatar" aria-hidden="true">
                                    {{ strtoupper(substr($request->userOne->name, 0, 1)) }}
                                </div>
                                <div class="request-user-info">
                                    <div class="request-user-name">{{ $request->userOne->name }}</div>
                                    <div class="request-user-text">Wants to connect</div>
                                </div>
                            </div>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M9 18l6-6-6-6"/>
                            </svg>
                        </button>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endif

            {{-- ── Conversations List ── --}}
            <div class="conversations-list" role="list">
                @forelse($conversations as $conversation)
                <div
                    wire:click="selectConversation({{ $conversation->id }})"
                    class="conv-item {{ ($selectedConversation && $selectedConversation->id === $conversation->id) ? 'conv-active' : '' }}"
                    role="listitem"
                    tabindex="0"
                    aria-label="Conversation with {{ $conversation->otherUser()->name }}"
                    wire:key="conv-{{ $conversation->id }}">
                    <div class="conv-avatar-wrap">
                        <div class="conv-avatar" aria-hidden="true">
                            {{ strtoupper(substr($conversation->otherUser()->name, 0, 1)) }}
                        </div>
                        <span class="presence-dot presence-online" aria-hidden="true"></span>
                    </div>
                    <div class="conv-info">
                        <div class="conv-info-top">
                            <span class="conv-name">{{ $conversation->otherUser()->name }}</span>
                            <span class="conv-time">
                                {{ $conversation->last_message_at?->diffForHumans(short: true) ?? 'now' }}
                            </span>
                        </div>
                        <p class="conv-preview">Click to open chat</p>
                    </div>
                </div>
                @empty
                <div class="empty-list" role="status">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5" opacity=".4" aria-hidden="true">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                    </svg>
                    <p>No conversations yet</p>
                </div>
                @endforelse
            </div>

        </aside>

        {{-- ───────────────────────────────────────────────
             MAIN PANEL — driven by $activeScreen
        ─────────────────────────────────────────────── --}}
        <main class="teams-main" id="teamsMain">

            {{-- ══ ACTIVE CHAT ══ --}}
            @if($activeScreen === 'chat' && $selectedConversation)

                {{-- Chat Header --}}
                <div class="chat-header">
                    <button class="mobile-back-btn" id="mobileBackBtn"
                            title="Back" aria-label="Back to conversations">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5M12 5l-7 7 7 7" />
                        </svg>
                    </button>
                    <div class="chat-header-avatar" aria-hidden="true">
                        {{ strtoupper(substr($selectedConversation->otherUser()->name, 0, 1)) }}
                    </div>
                    <div class="chat-header-info">
                        <h2 class="chat-header-name">{{ $selectedConversation->otherUser()->name }}</h2>
                        <span class="chat-header-status">
                            <span class="status-dot" aria-hidden="true"></span> Online
                        </span>
                    </div>
                </div>

                {{-- Messages --}}
                <div class="messages-area" id="messages-container"
                     role="log" aria-live="polite" aria-label="Messages">
                    @forelse($messages as $message)
                        @php $isMine = $message->sender_id === auth()->id(); @endphp
                        <div
                            class="msg-row {{ $isMine ? 'msg-mine' : 'msg-theirs' }}"
                            role="article"
                            wire:key="msg-{{ $message->id }}">
                            @if(!$isMine)
                                <div class="msg-avatar" aria-hidden="true">
                                    {{ strtoupper(substr($message->sender->name, 0, 1)) }}
                                </div>
                            @endif
                            <div class="msg-body-wrap">
                                <div class="msg-meta {{ $isMine ? 'msg-meta-right' : '' }}">
                                    <span class="msg-author">{{ $message->sender->name }}</span>
                                    <span class="msg-timestamp">{{ $message->created_at->format('g:i A') }}</span>
                                </div>
                                <div class="msg-bubble {{ $isMine ? 'bubble-mine' : 'bubble-theirs' }}">
                                    {{ $message->body }}
                                </div>
                            </div>
                            @if($isMine)
                                <div class="msg-avatar msg-avatar-mine" aria-hidden="true">
                                    {{ strtoupper(substr($message->sender->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="empty-messages" role="status">
                            <div class="empty-messages-icon" aria-hidden="true">💬</div>
                            <h3>No messages yet</h3>
                            <p>Send a message to start the conversation</p>
                        </div>
                    @endforelse
                    <div id="scroll-anchor" aria-hidden="true"></div>
                </div>

                {{-- Message Input --}}
                <div class="chat-input-area">
                    <form
                        wire:submit="sendMessage"
                        class="input-form">
                        <input
                            type="text"
                            wire:model="body"
                            placeholder="Type a new message"
                            class="message-input"
                            id="messageInput"
                            autocomplete="off"
                            aria-label="Message input">
                        <button
                            type="submit"
                            class="send-btn"
                            title="Send message"
                            aria-label="Send message">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2">
                                <line x1="22" y1="2" x2="11" y2="13" />
                                <polygon points="22 2 15 22 11 13 2 9 22 2" />
                            </svg>
                        </button>
                    </form>
                </div>

            {{-- ══ INCOMING REQUEST PREVIEW ══ --}}
            @elseif($activeScreen === 'request-preview' && $selectedRequest)

                <div class="request-preview-screen">
                    <div class="request-preview-card">
                        <div class="request-preview-avatar" aria-hidden="true">
                            {{ strtoupper(substr($selectedRequest->userOne->name, 0, 1)) }}
                        </div>
                        <h2 class="request-preview-title">
                            {{ $selectedRequest->userOne->name }} wants to connect
                        </h2>
                        <p class="request-preview-message">
                            Accept to start chatting with {{ $selectedRequest->userOne->name }}.
                        </p>
                        <div class="request-preview-actions">
                            <button
                                wire:click="acceptRequest({{ $selectedRequest->id }})"
                                class="request-accept"
                                wire:loading.attr="disabled">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                                <span>Accept</span>
                                <span wire:loading wire:target="acceptRequest({{ $selectedRequest->id }})">…</span>
                            </button>
                            <button
                                wire:click="rejectRequest({{ $selectedRequest->id }})"
                                class="request-reject"
                                wire:loading.attr="disabled">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <line x1="18" y1="6" x2="6" y2="18"/>
                                    <line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                                <span>Decline</span>
                            </button>
                        </div>
                    </div>
                </div>

            {{-- ══ SENT / PENDING REQUESTS SCREEN ══ --}}
            @elseif($activeScreen === 'sent-requests')

                <div class="pending-page">
                    <div class="pending-page-header">
                        <div class="pending-page-icon" aria-hidden="true">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2">
                                <path d="M22 2L11 13"/>
                                <path d="M22 2L15 22L11 13L2 9L22 2Z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="pending-page-title">Pending Requests</h2>
                            <p class="pending-page-subtitle">Waiting for others to accept your request</p>
                        </div>
                    </div>

                    <div class="pending-grid">
                        @forelse($sentRequests as $request)
                        <div class="pending-card" wire:key="sent-{{ $request->id }}">
                            <div class="pending-card-left">
                                <div class="pending-avatar" aria-hidden="true">
                                    {{ strtoupper(substr($request->userTwo->name, 0, 1)) }}
                                </div>
                                <div class="pending-user-meta">
                                    <div class="pending-user-name">{{ $request->userTwo->name }}</div>
                                    <div class="pending-user-email">{{ $request->userTwo->email }}</div>
                                    <div class="pending-user-time">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <circle cx="12" cy="12" r="10"/>
                                            <polyline points="12 6 12 12 16 14"/>
                                        </svg>
                                        Sent {{ $request->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                            <div class="pending-status">
                                <span class="pending-status-badge">Awaiting Response</span>
                            </div>
                        </div>
                        @empty
                        <div class="pending-empty" role="status">
                            <h3>No pending requests</h3>
                            <p>When you send requests, they'll appear here.</p>
                        </div>
                        @endforelse
                    </div>
                </div>

            {{-- ══ EMPTY / WELCOME STATE ══ --}}
            @else
                <div class="empty-state" role="status">
                    <div class="empty-state-icon" aria-hidden="true">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="1" opacity=".3">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                        </svg>
                    </div>
                    <h3 class="empty-state-title">Welcome to Chat</h3>
                    <p class="empty-state-sub">
                        Select a conversation from the sidebar<br>
                        or search for someone to message.
                    </p>
                </div>
            @endif

        </main>
    </div>

</div>