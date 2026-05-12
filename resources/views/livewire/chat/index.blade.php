<div class="teams-chat-root">

    <div class="teams-layout">

        {{-- ═══════════════════════════════════════════════════════
             SIDEBAR
        ═══════════════════════════════════════════════════════ --}}

        <aside class="teams-sidebar" id="teamsSidebar">

            <div class="sidebar-header">
                <div class="sidebar-title-row">
                    <h2 class="sidebar-title">Chat</h2>
                    <button class="icon-btn" title="New Chat">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    </button>
                </div>

                <div class="search-wrap">
                    <svg class="search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <input
                        type="text"
                        placeholder="Search"
                        class="search-input"
                    >
                </div>
            </div>

            <div class="sidebar-tabs">
                <button class="tab-btn tab-active">Recent</button>
                <button class="tab-btn">Contacts</button>
            </div>

            <div class="conversations-list">

                @forelse($conversations as $conversation)

                    <div
                        wire:click="selectConversation({{ $conversation->id }})"
                        class="conv-item {{ ($selectedConversation && $selectedConversation->id === $conversation->id) ? 'conv-active' : '' }}"
                    >
                        <div class="conv-avatar-wrap">
                            <div class="conv-avatar">
                                {{ strtoupper(substr('C', 0, 1)) }}
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

                    <div class="empty-list">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" opacity=".4"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        <p>No conversations yet</p>
                    </div>

                @endforelse

            </div>

        </aside>

        {{-- ═══════════════════════════════════════════════════════
             CHAT WINDOW
        ═══════════════════════════════════════════════════════ --}}

        <main class="teams-main">

            @if($selectedConversation)

                {{-- HEADER --}}
                <div class="chat-header">
                    <button class="mobile-back-btn" id="mobileBackBtn" title="Back">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                    </button>

                    <div class="chat-header-avatar">
                        C
                    </div>

                    <div class="chat-header-info">
                        <h2 class="chat-header-name">{{ $selectedConversation->otherUser()->name }}</h2>
                        <span class="chat-header-status">
                            <span class="status-dot"></span> Online
                        </span>
                    </div>

                    <div class="chat-header-actions">
                        <button class="icon-btn" title="Video Call">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                        </button>
                        <button class="icon-btn" title="Voice Call">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.73a16 16 0 0 0 6.29 6.29l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        </button>
                        <button class="icon-btn" title="More options">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                        </button>
                    </div>
                </div>

                {{-- MESSAGES --}}
                <div class="messages-area" id="messages-container">

                    @forelse($messages as $message)
                        @php $isMine = $message->sender_id === auth()->id(); @endphp

                        <div class="msg-row {{ $isMine ? 'msg-mine' : 'msg-theirs' }}">

                            @if(!$isMine)
                                <div class="msg-avatar">
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
                                <div class="msg-avatar msg-avatar-mine">
                                    {{ strtoupper(substr($message->sender->name, 0, 1)) }}
                                </div>
                            @endif

                        </div>

                    @empty

                        <div class="empty-messages">
                            <div class="empty-messages-icon">💬</div>
                            <h3>No messages yet</h3>
                            <p>Send a message to start the conversation</p>
                        </div>

                    @endforelse

                    {{-- Scroll anchor - always at bottom --}}
                    <div id="scroll-anchor"></div>

                </div>

                {{-- INPUT --}}
                <div class="chat-input-area">
                    <div class="input-toolbar">
                        <button class="icon-btn toolbar-btn" title="Attach file">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                        </button>
                        <button class="icon-btn toolbar-btn" title="Emoji">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 13s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                        </button>
                        <button class="icon-btn toolbar-btn" title="GIF">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><text x="6" y="16" font-size="8" fill="currentColor" stroke="none" font-weight="bold">GIF</text></svg>
                        </button>
                    </div>

                    <form wire:submit="sendMessage" class="input-form">
                        <input
                            type="text"
                            wire:model="body"
                            placeholder="Type a new message"
                            class="message-input"
                            id="messageInput"
                            autocomplete="off"
                        >
                        <button type="submit" class="send-btn" title="Send">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        </button>
                    </form>
                </div>

            @else

                {{-- EMPTY STATE --}}
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" opacity=".3"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    <h3 class="empty-state-title">Welcome to Chat</h3>
                    <p class="empty-state-sub">Select a conversation from the sidebar<br>to start messaging in real time.</p>
                </div>

            @endif

        </main>

    </div>

</div>

{{-- ═══════════════════════════════════════════════════════
     STYLES
═══════════════════════════════════════════════════════ --}}
<style>
/* ── Reset & Root ─────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --teams-bg:        #1b1b1f;
    --sidebar-bg:      #202027;
    --main-bg:         #2a2a32;
    --surface:         #2f2f3a;
    --surface-hover:   #38384a;
    --surface-active:  #3d3d55;
    --border:          #38384a;
    --accent:          #6264a7;
    --accent-hover:    #7b7dd6;
    --accent-dim:      rgba(98,100,167,.15);
    --text-primary:    #f3f3f5;
    --text-secondary:  #9b9baf;
    --text-muted:      #6b6b80;
    --online:          #57c75a;
    --bubble-mine:     #6264a7;
    --bubble-theirs:   #2f2f3a;
    --input-bg:        #1b1b24;
    --shadow:          0 2px 12px rgba(0,0,0,.4);
    --radius:          12px;
    --radius-sm:       8px;
    --sidebar-w:       300px;
    --header-h:        64px;
    --input-h:         112px;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
}

/* ── Root wrapper ─────────────────────────────────────── */
.teams-chat-root {
    width: 100%;
    height: calc(100vh - 80px); /* adjust if your app has a navbar */
    display: flex;
    flex-direction: column;
    background: var(--teams-bg);
    color: var(--text-primary);
    overflow: hidden;
}

/* ── Layout ───────────────────────────────────────────── */
.teams-layout {
    display: flex;
    flex: 1;
    min-height: 0; /* critical for nested flex scroll */
    overflow: hidden;
}

/* ── Sidebar ──────────────────────────────────────────── */
.teams-sidebar {
    width: var(--sidebar-w);
    min-width: var(--sidebar-w);
    background: var(--sidebar-bg);
    border-right: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: transform .25s ease;
}

.sidebar-header {
    padding: 16px 16px 12px;
    border-bottom: 1px solid var(--border);
    flex-shrink: 0;
}

.sidebar-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.sidebar-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-primary);
}

.search-wrap {
    position: relative;
}

.search-icon {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    pointer-events: none;
}

.search-input {
    width: 100%;
    background: var(--input-bg);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 8px 14px 8px 34px;
    color: var(--text-primary);
    font-size: .875rem;
    outline: none;
    transition: border-color .15s;
}

.search-input:focus {
    border-color: var(--accent);
}

.search-input::placeholder { color: var(--text-muted); }

/* Tabs */
.sidebar-tabs {
    display: flex;
    padding: 8px 16px 0;
    gap: 4px;
    flex-shrink: 0;
}

.tab-btn {
    background: none;
    border: none;
    color: var(--text-secondary);
    font-size: .8125rem;
    font-weight: 500;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: background .15s, color .15s;
}

.tab-btn:hover { background: var(--surface-hover); color: var(--text-primary); }

.tab-active {
    color: var(--accent-hover) !important;
    background: var(--accent-dim) !important;
}

/* Conversations list */
.conversations-list {
    flex: 1;
    overflow-y: auto;
    padding: 8px;
}

.conversations-list::-webkit-scrollbar { width: 4px; }
.conversations-list::-webkit-scrollbar-track { background: transparent; }
.conversations-list::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

.conv-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: background .15s;
    border: 1px solid transparent;
}

.conv-item:hover { background: var(--surface-hover); }

.conv-active {
    background: var(--surface-active) !important;
    border-color: var(--accent) !important;
}

.conv-avatar-wrap { position: relative; flex-shrink: 0; }

.conv-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--accent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: .9rem;
}

.presence-dot {
    position: absolute;
    bottom: 1px;
    right: 1px;
    width: 11px;
    height: 11px;
    border-radius: 50%;
    border: 2px solid var(--sidebar-bg);
}

.presence-online { background: var(--online); }

.conv-info { flex: 1; min-width: 0; }

.conv-info-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 6px;
}

.conv-name {
    font-size: .875rem;
    font-weight: 600;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.conv-time {
    font-size: .72rem;
    color: var(--text-muted);
    flex-shrink: 0;
}

.conv-preview {
    font-size: .78rem;
    color: var(--text-secondary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 2px;
}

.empty-list {
    padding: 40px 16px;
    text-align: center;
    color: var(--text-muted);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    font-size: .875rem;
}

/* ── Main chat area ───────────────────────────────────── */
.teams-main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    background: var(--main-bg);
    overflow: hidden; /* ← key: contain children */
}

/* Header */
.chat-header {
    height: var(--header-h);
    min-height: var(--header-h);
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 20px;
    border-bottom: 1px solid var(--border);
    background: var(--main-bg);
    flex-shrink: 0;
}

.mobile-back-btn {
    display: none;
    background: none;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    padding: 6px;
    border-radius: 6px;
}

.mobile-back-btn:hover { background: var(--surface-hover); color: var(--text-primary); }

.chat-header-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: var(--accent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: .9rem;
    flex-shrink: 0;
}

.chat-header-info { flex: 1; min-width: 0; }

.chat-header-name {
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.2;
}

.chat-header-status {
    font-size: .75rem;
    color: var(--online);
    display: flex;
    align-items: center;
    gap: 4px;
}

.status-dot {
    display: inline-block;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--online);
}

.chat-header-actions {
    display: flex;
    gap: 4px;
}

/* Icon buttons */
.icon-btn {
    background: none;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    padding: 8px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background .15s, color .15s;
}

.icon-btn:hover {
    background: var(--surface-hover);
    color: var(--text-primary);
}

/* ── Messages area ────────────────────────────────────── */
.messages-area {
    flex: 1;
    overflow-y: auto;    /* scrolls independently */
    overflow-x: hidden;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-height: 0;       /* critical for flex scroll */
}

.messages-area::-webkit-scrollbar { width: 5px; }
.messages-area::-webkit-scrollbar-track { background: transparent; }
.messages-area::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

/* Message rows */
.msg-row {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    max-width: 100%;
    animation: msgIn .18s ease;
}

@keyframes msgIn {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
}

.msg-mine  { flex-direction: row-reverse; }
.msg-theirs { flex-direction: row; }

.msg-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--accent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: .75rem;
    flex-shrink: 0;
    align-self: flex-end;
    margin-bottom: 2px;
}

.msg-avatar-mine { background: #464680; }

.msg-body-wrap {
    max-width: 68%;
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.msg-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 0 4px;
}

.msg-meta-right { flex-direction: row-reverse; }

.msg-author {
    font-size: .72rem;
    font-weight: 600;
    color: var(--text-secondary);
}

.msg-timestamp {
    font-size: .68rem;
    color: var(--text-muted);
}

.msg-bubble {
    padding: 10px 14px;
    border-radius: 18px;
    font-size: .9rem;
    line-height: 1.45;
    word-break: break-word;
    box-shadow: 0 1px 4px rgba(0,0,0,.25);
}

.bubble-mine {
    background: var(--bubble-mine);
    color: #fff;
    border-bottom-right-radius: 4px;
}

.bubble-theirs {
    background: var(--bubble-theirs);
    color: var(--text-primary);
    border-bottom-left-radius: 4px;
    border: 1px solid var(--border);
}

/* Empty messages */
.empty-messages {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: var(--text-muted);
    text-align: center;
    padding: 40px;
}

.empty-messages-icon { font-size: 3rem; margin-bottom: 8px; }
.empty-messages h3 { font-size: 1rem; font-weight: 600; color: var(--text-secondary); }
.empty-messages p  { font-size: .85rem; }

/* ── Input area ───────────────────────────────────────── */
.chat-input-area {
    flex-shrink: 0;
    border-top: 1px solid var(--border);
    background: var(--main-bg);
    padding: 10px 16px 14px;
}

.input-toolbar {
    display: flex;
    gap: 2px;
    margin-bottom: 8px;
}

.toolbar-btn {
    color: var(--text-muted);
    padding: 5px;
}

.toolbar-btn:hover { color: var(--accent-hover); background: var(--accent-dim); }

.input-form {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--input-bg);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 6px 6px 6px 16px;
    transition: border-color .15s;
}

.input-form:focus-within { border-color: var(--accent); }

.message-input {
    flex: 1;
    background: none;
    border: none;
    outline: none;
    color: var(--text-primary);
    font-size: .9rem;
    min-width: 0;
}

.message-input::placeholder { color: var(--text-muted); }

.send-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: var(--accent);
    border: none;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    transition: background .15s, transform .1s;
}

.send-btn:hover { background: var(--accent-hover); transform: scale(1.05); }
.send-btn:active { transform: scale(.95); }

/* ── Empty state (no conversation selected) ───────────── */
.empty-state {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 14px;
    text-align: center;
    padding: 40px;
    color: var(--text-muted);
}

.empty-state-icon {
    width: 96px;
    height: 96px;
    border-radius: 50%;
    background: var(--surface);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
}

.empty-state-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--text-primary);
}

.empty-state-sub {
    font-size: .9rem;
    color: var(--text-secondary);
    line-height: 1.6;
}

/* ── Mobile ───────────────────────────────────────────── */
@media (max-width: 768px) {
    :root { --sidebar-w: 100%; }

    .teams-layout { position: relative; }

    .teams-sidebar {
        position: absolute;
        inset: 0;
        z-index: 10;
        width: 100%;
        transform: translateX(0);
    }

    /* Hide sidebar when a conversation is open on mobile */
    .teams-layout.conversation-open .teams-sidebar {
        transform: translateX(-100%);
    }

    .teams-main { width: 100%; }

    .mobile-back-btn { display: flex !important; }

    .chat-header-actions .icon-btn:not(:last-child) { display: none; }

    .msg-body-wrap { max-width: 80%; }
}
</style>

{{-- ═══════════════════════════════════════════════════════
     SCRIPTS
═══════════════════════════════════════════════════════ --}}
<script>
(function () {

    /* ── Auto-scroll helper ─────────────────────────────── */
    function scrollToBottom(smooth = false) {
        const anchor = document.getElementById('scroll-anchor');
        if (anchor) {
            anchor.scrollIntoView({ behavior: smooth ? 'smooth' : 'instant', block: 'end' });
        }
    }

    /* Scroll instantly on page load / Livewire re-render */
    document.addEventListener('livewire:navigated', () => scrollToBottom());
    document.addEventListener('DOMContentLoaded', () => scrollToBottom());

    /* ── Livewire init ──────────────────────────────────── */
    document.addEventListener('livewire:init', () => {

        /* After any Livewire DOM update, scroll to bottom */
        Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
            succeed(({ snapshot, effect }) => {
                requestAnimationFrame(() => scrollToBottom(true));
            });
        });

        /* Real-time: receive incoming message and append */
        Echo.channel('chat.1')
            .listen('.message.sent', (event) => {
                console.log('Realtime message received:', event);

                Livewire.find(
                    document.querySelector('[wire\\:id]').getAttribute('wire:id')
                ).call('appendMessage', event.message);

                /* appendMessage triggers a Livewire update → hook above scrolls */
                /* Extra safety scroll after 150ms */
                setTimeout(() => scrollToBottom(true), 150);
            });

    });

    /* ── Mobile: toggle sidebar on conversation open/back ── */
    document.addEventListener('click', (e) => {
        const layout = document.querySelector('.teams-layout');
        if (!layout) return;

        /* Conversation selected → hide sidebar on mobile */
        if (e.target.closest('.conv-item')) {
            layout.classList.add('conversation-open');
        }

        /* Back button → show sidebar */
        if (e.target.closest('#mobileBackBtn')) {
            layout.classList.remove('conversation-open');
        }
    });

    /* Focus input when conversation opens */
    document.addEventListener('livewire:update', () => {
        const input = document.getElementById('messageInput');
        if (input && window.innerWidth > 768) input.focus();
    });

})();
</script>