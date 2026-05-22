<div class="teams-chat-root" data-conversation="{{ $selectedConversationId }}">

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

            {{-- Center: Global Search --}}
            <div class="topbar-center">
                <div class="global-search-wrap" id="globalSearchWrap">
                    <svg class="search-icon global-search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>

                    <input
                        type="text"
                        wire:model.live="search"
                        placeholder="Search people, messages..."
                        class="global-search-input"
                        id="globalSearchInput"
                        autocomplete="off"
                        aria-label="Search people"
                        aria-autocomplete="list"
                        aria-controls="globalSearchResults"
                        aria-expanded="{{ !empty($searchResults) ? 'true' : 'false' }}">

                    {{-- Keyboard shortcut badge (desktop only) --}}
                    <div class="search-kbd-hint" aria-hidden="true">
                        <kbd>⌘</kbd><kbd>K</kbd>
                    </div>

                    @if(!empty($searchResults))
                    <div class="global-search-results" id="globalSearchResults" role="listbox" aria-label="Search results">
                        @foreach($searchResults as $user)
                        <!-- <button
                            type="button"
                            class="global-search-item"
                            role="option"
                            wire:click="startConversation({{ $user->id }})">
                            <div class="global-search-avatar" aria-hidden="true">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div class="global-search-info">
                                <div class="global-search-name">{{ $user->name }}</div>
                                <div class="global-search-email">{{ $user->email }}</div>
                            </div>
                            <div class="global-search-action" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                                </svg>
                                Message
                            </div>
                        </button> -->
                        <div
                            class="global-search-item"
                            role="option">

                            <div class="global-search-avatar" aria-hidden="true">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>

                            <div class="global-search-info">
                                <div class="global-search-name">
                                    {{ $user->name }}
                                </div>

                                <div class="global-search-email">
                                    {{ $user->email }}
                                </div>
                            </div>
                            <div class="global-search-action">

                                @php

                                $alreadyConnected =
                                auth()->user()->hasConversationWith($user->id);

                                $isAdminConversation =
                                auth()->user()->is_admin
                                || $user->is_admin;

                                @endphp

                                @if($alreadyConnected)

                                <button
                                    type="button"
                                    wire:click="openExistingConversation({{ $user->id }})"
                                    class="search-action-btn">

                                    Open Chat

                                </button>

                                @elseif($isAdminConversation)

                                <button
                                    type="button"
                                    wire:click="startConversation({{ $user->id }})"
                                    class="search-action-btn">

                                    Start Chat

                                </button>

                                @else

                                <button
                                    type="button"
                                    wire:click="startConversation({{ $user->id }})"
                                    class="search-action-btn">

                                    Send Request

                                </button>

                                @endif

                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            {{-- Right: Actions + Profile --}}
            <div class="topbar-right">

                {{-- Notification bell --}}
                <button class="topbar-icon-btn" title="Notifications" aria-label="Notifications">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                        <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                    </svg>
                    <span class="notif-badge" aria-label="3 notifications">3</span>
                </button>

                {{-- Profile button --}}
                <div class="profile-wrap" id="profileWrap">
                    <button
                        class="profile-btn"
                        id="profileBtn"
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-controls="profileDropdown"
                        title="Profile menu"
                        data-name="{{ auth()->user()->name }}"
                        data-email="{{ auth()->user()->email }}"
                        data-role="{{ auth()->user()->is_admin ? 'Admin' : 'Member' }}"
                        data-initials="{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}">
                        <div class="profile-avatar" aria-hidden="true">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            <span class="profile-status-dot"></span>
                        </div>
                        <div class="profile-meta">
                            <div class="profile-name">{{ auth()->user()->name }}</div>
                            <div class="profile-role">{{ auth()->user()->is_admin ? 'Admin' : 'Member' }}</div>
                        </div>
                        <svg class="profile-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M6 9l6 6 6-6" />
                        </svg>
                    </button>

                    {{-- Profile Dropdown --}}
                    <div class="profile-dropdown" id="profileDropdown" role="menu" aria-label="Profile options">
                        {{-- User card at top --}}
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

                        {{-- Status Submenu --}}
                        <div class="dropdown-section-label">Set status</div>
                        <button class="dropdown-item status-item status-online" role="menuitem" type="button">
                            <span class="status-icon status-icon-online"></span>
                            Available
                        </button>
                        <button class="dropdown-item status-item" role="menuitem" type="button">
                            <span class="status-icon status-icon-busy"></span>
                            Busy
                        </button>
                        <button class="dropdown-item status-item" role="menuitem" type="button">
                            <span class="status-icon status-icon-away"></span>
                            Away
                        </button>
                        <button class="dropdown-item status-item" role="menuitem" type="button">
                            <span class="status-icon status-icon-dnd"></span>
                            Do not disturb
                        </button>

                        <div class="dropdown-divider"></div>

                        <button class="dropdown-item" role="menuitem" type="button">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                            View profile
                        </button>
                        <button class="dropdown-item" role="menuitem" type="button">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="12" cy="12" r="3" />
                                <path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14" />
                            </svg>
                            Settings
                        </button>

                        <div class="dropdown-divider"></div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item dropdown-item-danger" role="menuitem" type="submit">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
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
                    <button class="icon-btn" title="New Chat" aria-label="New chat">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                    </button>
                </div>
                <div>
                </div>
                <div class="search-wrap">
                    <svg class="search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                    <input
                        type="text"
                        placeholder="Search"
                        class="search-input"
                        wire:model="search"
                        aria-label="Search conversations">
                </div>
            </div>

            <div class="sidebar-tabs" role="tablist">
                <button class="tab-btn tab-active" role="tab" aria-selected="true">Recent</button>
                <button class="tab-btn" role="tab" aria-selected="false">Contacts</button>
            </div>
            @if($pendingRequests && count($pendingRequests))
            <div class="request-section">
                {{-- Header: Click to expand/collapse the Requests section --}}
                <button
                    type="button"
                    class="request-header"
                    wire:click="toggleRequests">

                    <div class="request-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                        </svg>

                        <span>Requests</span>
                    </div>

                    <div class="request-header-right">
                        <span class="request-count">
                            {{ count($pendingRequests) }}
                        </span>

                        <span class="request-chevron">
                            {{ $showRequests ? '▾' : '▸' }}
                        </span>
                    </div>
                </button>

                {{-- Request list only visible when section is expanded --}}
                @if($showRequests)
                <div class="request-list">
                    @foreach($pendingRequests as $request)
                    <div class="request-item">
                        <button
                            type="button"
                            wire:click="openRequest({{ $request->id }})"
                            class="request-toggle">

                            <div class="request-user">
                                <div class="request-avatar">
                                    {{ strtoupper(substr($request->userOne->name, 0, 1)) }}
                                </div>

                                <div class="request-user-info">
                                    <div class="request-user-name">
                                        {{ $request->userOne->name }}
                                    </div>

                                    <div class="request-user-text">
                                        Click to view request
                                    </div>
                                </div>
                            </div>

                            <div class="request-arrow">
                                →
                            </div>
                        </button>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endif


            <div class="conversations-list" role="list">
                @forelse($conversations as $conversation)
                <div
                    wire:click="selectConversation({{ $conversation->id }})"
                    class="conv-item {{ ($selectedConversation && $selectedConversation->id === $conversation->id) ? 'conv-active' : '' }}"
                    role="listitem"
                    tabindex="0"
                    aria-label="Conversation with {{ $conversation->otherUser()->name }}">
                    <div class="conv-avatar-wrap">
                        <div class="conv-avatar" aria-hidden="true">
                            {{ strtoupper(substr($conversation->otherUser()->name, 0, 1)) }}
                        </div>
                        <span class="presence-dot presence-online"></span>
                    </div>
                    <div class="conv-info">
                        <div class="conv-info-top">
                            <span class="conv-name">{{ $conversation->otherUser()->name }}</span>
                            <span class="conv-time">now</span>
                        </div>
                        <p class="conv-preview">Realtime chat active</p>
                    </div>
                </div>
                @empty
                <div class="empty-list" role="status">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" opacity=".4" aria-hidden="true">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                    </svg>
                    <p>No conversations yet</p>
                </div>
                @endforelse
            </div>

        </aside>

        {{-- ───────────────────────────────────────────────
             CHAT WINDOW
        ─────────────────────────────────────────────── --}}
        <main class="teams-main" id="teamsMain">

            @if($selectedConversation)

            {{-- Header --}}
            <div class="chat-header">
                <button class="mobile-back-btn" id="mobileBackBtn" title="Back" aria-label="Back to conversations">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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

                <!-- <div class="chat-header-actions">
                    <button class="icon-btn" title="Video Call" aria-label="Start video call">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="23 7 16 12 23 17 23 7" />
                            <rect x="1" y="5" width="15" height="14" rx="2" />
                        </svg>
                    </button>
                    <button class="icon-btn" title="Voice Call" aria-label="Start voice call">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.73a16 16 0 0 0 6.29 6.29l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" />
                        </svg>
                    </button>
                    <button class="icon-btn" title="More options" aria-label="More options">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="5" r="1" />
                            <circle cx="12" cy="12" r="1" />
                            <circle cx="12" cy="19" r="1" />
                        </svg>
                    </button>
                </div> -->
            </div>


            {{-- Messages --}}
            <div class="messages-area" id="messages-container" role="log" aria-live="polite" aria-label="Messages">

                @forelse($messages as $message)
                @php $isMine = $message->sender_id === auth()->id(); @endphp

                <div class="msg-row {{ $isMine ? 'msg-mine' : 'msg-theirs' }}" role="article">

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

            {{-- Input --}}
            <div class="chat-input-area">
                <div class="input-toolbar">
                    <button class="icon-btn toolbar-btn" title="Attach file" aria-label="Attach file">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48" />
                        </svg>
                    </button>
                    <button class="icon-btn toolbar-btn" title="Emoji" aria-label="Add emoji">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <path d="M8 13s1.5 2 4 2 4-2 4-2" />
                            <line x1="9" y1="9" x2="9.01" y2="9" />
                            <line x1="15" y1="9" x2="15.01" y2="9" />
                        </svg>
                    </button>
                    <button class="icon-btn toolbar-btn" title="GIF" aria-label="Send GIF">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" />
                            <text x="6" y="16" font-size="8" fill="currentColor" stroke="none" font-weight="bold">GIF</text>
                        </svg>
                    </button>
                </div>

                <form
                    wire:submit="sendMessage"
                    class="input-form"
                    x-data
                    @submit="$refs.msg.value=''">
                    <input
                        type="text"
                        wire:model="body"
                        x-ref="msg"
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

                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13" />
                            <polygon points="22 2 15 22 11 13 2 9 22 2" />
                        </svg>

                    </button>
                </form>
            </div>
            @elseif($activeScreen === 'request-preview' && $selectedRequest)

            <div class="request-preview-screen">

                <div class="request-preview-card">

                    <div class="request-preview-avatar">
                        {{ strtoupper(substr($selectedRequest->userOne->name, 0, 1)) }}
                    </div>

                    <h2 class="request-preview-title">
                        {{ $selectedRequest->userOne->name }} wants to connect
                    </h2>

                    <p class="request-preview-message">
                        Hi {{ auth()->user()->name }}
                    </p>

                    <div class="request-preview-actions">

                        <button
                            wire:click="acceptRequest({{ $selectedRequest->id }})"
                            class="request-accept">

                            Accept

                        </button>

                        <button
                            wire:click="rejectRequest({{ $selectedRequest->id }})"
                            class="request-reject">

                            Reject

                        </button>

                    </div>

                </div>

            </div>

            @elseif($activeScreen === 'sent-requests')

            <div class="pending-page">

                <div class="pending-page-header">

                    <div class="pending-page-icon">

                        <svg width="26"
                            height="26"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2">

                            <path d="M22 2L11 13" />
                            <path d="M22 2L15 22L11 13L2 9L22 2Z" />

                        </svg>

                    </div>

                    <div>

                        <h2 class="pending-page-title">
                            Pending Requests
                        </h2>

                        <p class="pending-page-subtitle">
                            People waiting to accept your request
                        </p>

                    </div>

                </div>

                <div class="pending-grid">

                    @forelse($sentRequests as $request)

                    <div class="pending-card">

                        <div class="pending-card-left">

                            <div class="pending-avatar">
                                {{ strtoupper(substr($request->userTwo->name, 0, 1)) }}
                            </div>

                            <div class="pending-user-meta">

                                <div class="pending-user-name">
                                    {{ $request->userTwo->name }}
                                </div>

                                <div class="pending-user-email">
                                    {{ $request->userTwo->email }}
                                </div>

                                <div class="pending-user-time">

                                    <svg width="14"
                                        height="14"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2">

                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>

                                    </svg>

                                    Sent {{ $request->created_at->diffForHumans() }}

                                </div>

                            </div>

                        </div>

                        <div class="pending-status">
                            <span class="pending-status-badge">
                                Awaiting Response
                            </span>
                        </div>
                    </div>
                    @empty

                    <div class="pending-empty">
                        <h3>No pending requests</h3>
                        <p>
                            When you send requests, they'll appear here.
                        </p>
                    </div>
                    @endforelse
                </div>
            </div>

            @else

            {{-- Empty State --}}
            <div class="empty-state" role="status">

                <div class="empty-state-icon" aria-hidden="true">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" opacity=".3">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                    </svg>
                </div>

                <h3 class="empty-state-title">
                    Welcome to Chat
                </h3>

                <p class="empty-state-sub">
                    Select a conversation from the sidebar<br>
                    to start messaging in real time.
                </p>

            </div>

            @endif

        </main>
    </div>

</div>