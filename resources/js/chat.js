/**
 * chat.js
 * ───────────────────────────────────────────────────────────────────────────
 * Realtime layer for Baseline Chat.
 *
 * Typing indicator design
 * ────────────────────────
 * Typing state is managed 100% client-side — no Livewire server round-trip.
 *
 *  SENDER side:
 *   - input event   → broadcast isTyping=true  (once per burst via flag)
 *   - 2.5 s silence → broadcast isTyping=false
 *   - Enter pressed → broadcast isTyping=false immediately
 *   - broadcastTyping() calls the Livewire method which fires UserTyping event
 *
 *  RECEIVER side:
 *   - Echo .user.typing event → showTypingIndicator() / hideTypingIndicator()
 *     purely in the DOM, zero server calls.
 *   - Auto-hide after 3 s if the stop event is missed.
 * ───────────────────────────────────────────────────────────────────────────
 */

(function () {
    'use strict';

    /* ───────────────────────────────────────────────────────────────────────
     | SCROLL HELPERS
     | ─────────────────────────────────────────────────────────────────────*/

    function scrollToBottom(smooth = false) {
        const anchor = document.getElementById('scroll-anchor');
        if (!anchor) return;
        anchor.scrollIntoView({ behavior: smooth ? 'smooth' : 'instant', block: 'end' });
    }

    /* ───────────────────────────────────────────────────────────────────────
     | LIVEWIRE COMPONENT ACCESSOR
     | ─────────────────────────────────────────────────────────────────────*/

    function getChatComponent() {
        const root = document.querySelector('.teams-chat-root');
        if (!root) return null;

        const el = root.closest('[wire\\:id]') ?? root.querySelector('[wire\\:id]');
        if (!el) return null;

        return Livewire.find(el.getAttribute('wire:id'));
    }

    /* ───────────────────────────────────────────────────────────────────────
     | PENDING COUNT BADGE
     | ─────────────────────────────────────────────────────────────────────*/

    function refreshPendingCountBadge() {
        Livewire.getByName('chat.pending-count').forEach(c => c.call('refreshCount'));
    }

    /* ───────────────────────────────────────────────────────────────────────
     | SIDEBAR PREVIEW CACHE
     |
     | Keeps the real last-message text for each conversation so we can
     | restore it instantly when typing/draft indicators are cleared,
     | without waiting for a Livewire server round-trip.
     |
     | Populated on every Livewire render from data-last-preview attributes.
     | Also updated live when a new message arrives via WebSocket.
     | ─────────────────────────────────────────────────────────────────────*/
    const _sidebarPreviewCache = new Map(); // Map<convId:string, html:string>

    /**
     * Walk all sidebar conv-preview elements and cache their current
     * data-last-preview attribute value.
     * Called after every Livewire render commit.
     */
    function cacheSidebarPreviews() {
        document.querySelectorAll('[id^="conv-preview-"]').forEach(el => {
            const convId = el.id.replace('conv-preview-', '');
            const val    = el.dataset.lastPreview;
            if (val !== undefined && val !== '') {
                _sidebarPreviewCache.set(convId, val);
            }
        });
    }

    /**
     * Restore a sidebar preview to its last-known real message text.
     * Falls back to data-last-preview attribute, then empty string.
     */
    function restoreSidebarPreview(convId) {
        const preview = document.getElementById(`conv-preview-${convId}`);
        if (!preview) return;
        const text = _sidebarPreviewCache.get(String(convId))
                  || preview.dataset.lastPreview
                  || '';
        preview.textContent = text;
        preview.classList.remove('conv-preview--typing');
    }

    /* ───────────────────────────────────────────────────────────────────────
     | CLIENT-SIDE TYPING INDICATOR
     |
     | All DOM manipulation — no Livewire calls, no server round-trips.
     | ─────────────────────────────────────────────────────────────────────*/

    let _remoteTypingTimer        = null;   // auto-hide timer on the receiver side (open chat window)
    let _sidebarTypingTimer       = null;   // auto-hide timer for sidebar-only typing (non-open conv)
    let _activeConvIdForTyping    = null;   // which conversation the indicator belongs to

    /**
     * Show the typing indicator in both the chat window and the sidebar.
     * @param {string} userName  Display name of the typer.
     * @param {string|number} conversationId  Active conversation.
     */
    function showTypingIndicator(userName, conversationId) {
        _activeConvIdForTyping = String(conversationId);

        // ── Chat window indicator ─────────────────────────────────────────
        const row    = document.getElementById('typing-indicator-row');
        const avatar = document.getElementById('typing-indicator-avatar');
        const label  = document.getElementById('typing-indicator-label');

        if (row) {
            if (avatar) avatar.textContent = userName ? userName.charAt(0).toUpperCase() : '?';
            if (label)  label.textContent  = `${userName} is typing…`;
            row.style.display = '';
            row.setAttribute('aria-label', `${userName} is typing`);
        }

        // ── Sidebar preview text ──────────────────────────────────────────
        const preview = document.getElementById(`conv-preview-${conversationId}`);
        if (preview) {
            preview.classList.add('conv-preview--typing');
            preview.innerHTML =
                `<span class="sidebar-typing-dots" aria-hidden="true">` +
                    `<span class="sidebar-typing-dot"></span>` +
                    `<span class="sidebar-typing-dot"></span>` +
                    `<span class="sidebar-typing-dot"></span>` +
                `</span> ${userName} is typing…`;
        }

        // Scroll so the typing bubble is visible.
        setTimeout(() => scrollToBottom(true), 80);
    }

    /**
     * Hide the typing indicator and restore the sidebar preview text.
     * Only restores the sidebar text if it currently shows typing content
     * (i.e. Livewire hasn't already re-rendered it with real content).
     */
    function hideTypingIndicator() {
        // ── Chat window indicator ─────────────────────────────────────────
        const row = document.getElementById('typing-indicator-row');
        if (row) {
            row.style.display = 'none';
            row.removeAttribute('aria-label');
        }

        // ── Sidebar preview text — only clear if still showing typing UI ──
        if (_activeConvIdForTyping) {
            const preview = document.getElementById(`conv-preview-${_activeConvIdForTyping}`);
            if (preview && preview.classList.contains('conv-preview--typing')) {
                restoreSidebarPreview(_activeConvIdForTyping);
            }
        }

        _activeConvIdForTyping = null;
        clearTimeout(_remoteTypingTimer);
        _remoteTypingTimer = null;
    }

    /* ───────────────────────────────────────────────────────────────────────
     | TICK / DELIVERY STATUS DOM HELPERS
     | ─────────────────────────────────────────────────────────────────────*/

    /**
     * Return tick SVG HTML for a given status.
     * Matches the Blade partial logic exactly.
     */
    function tickSVG(status) {
        if (status === 'read') {
            return `<svg class="tick tick--read" width="16" height="11" viewBox="0 0 16 11" fill="none" aria-label="Read">
                <path d="M1 5.5L4.5 9 10 3" stroke="#4fc3f7" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M5 5.5L8.5 9 14 3" stroke="#4fc3f7" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>`;
        }
        if (status === 'delivered') {
            return `<svg class="tick tick--delivered" width="16" height="11" viewBox="0 0 16 11" fill="none" aria-label="Delivered">
                <path d="M1 5.5L4.5 9 10 3" stroke="#9090b0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M5 5.5L8.5 9 14 3" stroke="#9090b0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>`;
        }
        // sent (single tick)
        return `<svg class="tick tick--sent" width="10" height="11" viewBox="0 0 10 11" fill="none" aria-label="Sent">
            <path d="M1 5.5L4.5 9 9 3" stroke="#9090b0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>`;
    }

    /** Update a single message tick in the chat window. */
    function updateTickDOM(messageId, status) {
        const el = document.getElementById(`tick-${messageId}`);
        if (el) el.innerHTML = tickSVG(status);
    }

    /**
     * Update the sidebar preview tick for the latest message.
     * The sidebar shows a tick only if the latest message is mine.
     * We find the tick span inside the conv-preview element.
     */
    function updateSidebarTick(conversationId, status) {
        const preview = document.getElementById(`conv-preview-${conversationId}`);
        if (!preview) return;
        const tick = preview.querySelector('.tick');
        if (tick) tick.outerHTML = tickSVG(status);
    }

    /**
     * Replace a message bubble with "This message was deleted" text.
     * Mirrors what Blade renders for deleted_at !== null.
     */
    function markDeletedInDOM(messageId) {
        const row = document.getElementById(`msg-${messageId}`);
        if (!row) return;

        const bubble = row.querySelector('.msg-bubble');
        if (!bubble) return;

        bubble.classList.add('bubble-deleted');
        bubble.innerHTML = `<span class="deleted-text">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            This message was deleted
        </span>`;

        // Remove action buttons
        const actions = row.querySelector('.msg-actions');
        if (actions) actions.remove();

        // Remove forwarded label
        const fwd = row.querySelector('.fwd-label');
        if (fwd) fwd.remove();

        // Remove timestamp/tick
        const timeWrap = row.querySelector('.msg-time-wrap');
        if (timeWrap) timeWrap.remove();
    }

    /* ───────────────────────────────────────────────────────────────────────
     | SIDEBAR INSTANT UPDATE FOR NEW MESSAGES
     |
     | Called when a message.sent event arrives for a conversation that is
     | NOT currently open. Updates the preview text and unread badge in-place,
     | with no Livewire server round-trip.
     | ─────────────────────────────────────────────────────────────────────*/

    /**
     * Instantly update sidebar preview text and unread badge for a new message.
     * @param {object} message  The message payload from the MessageSent event.
     */
    function updateSidebarForNewMessage(message) {
        const convId  = message.conversation_id;
        const preview = document.getElementById(`conv-preview-${convId}`);
        const badge   = document.getElementById(`unread-${convId}`);

        // Update preview text — only if not currently showing a typing indicator
        if (preview && !preview.classList.contains('conv-preview--typing')) {
            let text = '';
            if (message.type === 'image')     text = '📷 Image';
            else if (message.type === 'file') text = '📎 File';
            else                              text = (message.body || '').substring(0, 30);
            preview.textContent = text;
            // Keep cache + data attribute in sync
            preview.dataset.lastPreview = text;
            _sidebarPreviewCache.set(String(convId), text);
        }

        // Increment unread badge
        if (badge) {
            const current = parseInt(badge.textContent, 10) || 0;
            const next    = current + 1;
            badge.textContent   = next > 99 ? '99+' : String(next);
            badge.style.display = '';
        }
    }

    /* ───────────────────────────────────────────────────────────────────────
     | PAGE LOAD SCROLL
     | ─────────────────────────────────────────────────────────────────────*/

    document.addEventListener('DOMContentLoaded', () => scrollToBottom());
    document.addEventListener('livewire:navigated', () => scrollToBottom());

    /* ───────────────────────────────────────────────────────────────────────
     | LIVEWIRE INIT — all realtime wiring lives inside this event
     | ─────────────────────────────────────────────────────────────────────*/

    document.addEventListener('livewire:init', () => {

        /* ------------------------------------------------------------------
         | State: active conversation channel tracking
         | ----------------------------------------------------------------*/
        let currentConversationChannel = null;
        let currentConversationId      = null;

        /* ------------------------------------------------------------------
         | ── PRESENCE SYSTEM ────────────────────────────────────────────
         |
         | We join a single presence channel: presence.chat
         | Echo gives us the full member list on join, plus joining/leaving
         | events as users come and go.
         |
         | onlineUserIds  — Set<string> of user IDs currently online
         | applyPresence() — walks all [data-presence-uid] DOM nodes and
         |                   updates their CSS class and aria labels.
         | ----------------------------------------------------------------*/

        const onlineUserIds = new Set();

        /**
         * Walk all DOM elements that have [data-presence-uid] and apply the
         * correct online/offline class based on onlineUserIds.
         * Called after any membership change.
         */
        function applyPresence() {
            document.querySelectorAll('[data-presence-uid]').forEach(el => {
                const uid = String(el.dataset.presenceUid);

                if (el.classList.contains('presence-dot')) {
                    // Sidebar conversation dots
                    el.classList.toggle('presence-online',  onlineUserIds.has(uid));
                    el.classList.toggle('presence-offline', !onlineUserIds.has(uid));
                    el.title = onlineUserIds.has(uid) ? 'Online' : 'Offline';
                }

                if (el.id === 'chat-header-status') {
                    // Chat header — update dot colour + status text
                    const dot  = document.getElementById('chat-header-status-dot');
                    const text = document.getElementById('chat-header-status-text');

                    if (onlineUserIds.has(uid)) {
                        dot?.classList.add('status-online');
                        dot?.classList.remove('status-offline');
                        if (text) text.textContent = 'Online';
                    } else {
                        dot?.classList.remove('status-online');
                        dot?.classList.add('status-offline');
                        // Fall back to the last_seen text server wrote into data-last-seen
                        if (text) text.textContent = el.dataset.lastSeen || 'Offline';
                    }
                }
            });
        }

        console.log('[Chat] Joining presence channel: presence.chat');

        Echo.join('presence.chat')

            // Full member list on initial join
            .here((members) => {
                console.log('[Presence] here:', members);
                onlineUserIds.clear();
                members.forEach(m => onlineUserIds.add(String(m.id)));
                applyPresence();
                // Check delivery for any messages sent while recipient was already online.
                setTimeout(() => window._checkAndMarkDelivered && window._checkAndMarkDelivered(), 500);
            })

            // Someone came online
            .joining((member) => {
                console.log('[Presence] joining:', member);
                onlineUserIds.add(String(member.id));
                applyPresence();
                // Recipient just came online — mark our undelivered messages to them.
                setTimeout(() => window._checkAndMarkDelivered && window._checkAndMarkDelivered(), 200);
            })

            // Someone went offline
            .leaving((member) => {
                console.log('[Presence] leaving:', member);
                onlineUserIds.delete(String(member.id));
                applyPresence();
            })

            .error((err) => {
                console.error('[Presence] channel error:', err);
            });

        // Re-apply presence after every Livewire render (new conv items may appear)
        // We expose applyPresence globally so the commit hook below can call it.
        window._applyPresence = applyPresence;

        // Expose the live online set globally so server-side delivery checks
        // can be triggered from the Livewire commit hook after sendMessage.
        window._onlineUserIds = onlineUserIds;

        // Expose delivery check so it can be called after presence initializes.
        window._checkAndMarkDelivered = function () {
            const onlineIds = Array.from(onlineUserIds).map(Number).filter(Boolean);
            if (onlineIds.length === 0) return;
            const component = getChatComponent();
            if (component) component.call('markDeliveredForOnlineRecipients', onlineIds);
        };

        /* ------------------------------------------------------------------
         | connectToConversation(conversationId)
         | ----------------------------------------------------------------*/
        function connectToConversation(conversationId) {
            if (!conversationId || String(conversationId) === String(currentConversationId)) {
                return;
            }

            // Leave the previous channel and clear any stale typing state.
            if (currentConversationChannel) {
                Echo.leave(currentConversationChannel);
                hideTypingIndicator();
                stopTypingNow(); // clear our own outgoing typing state
                console.log('[Chat] Left channel:', currentConversationChannel);
            }

            currentConversationChannel = `chat.${conversationId}`;
            currentConversationId      = conversationId;

            console.log('[Chat] Joined channel:', currentConversationChannel);

            Echo.private(currentConversationChannel)

                /* ── Incoming message ─────────────────────────────────── */
                .listen('.message.sent', (event) => {
                    console.log('[Chat] message.sent received:', event);

                    hideTypingIndicator();

                    const incomingConvId = String(event.message.conversation_id);

                    if (incomingConvId === String(currentConversationId)) {
                        // Chat is open — append message
                        const component = getChatComponent();
                        if (component) {
                            component.call('appendMessage', event.message);
                            setTimeout(() => scrollToBottom(true), 150);
                        }
                    } else {
                        // Chat is NOT open — update sidebar instantly via DOM
                        updateSidebarForNewMessage(event.message);
                    }
                })

                /* ── Message delivered ────────────────────────────────── */
                .listen('.message.delivered', (event) => {
                    console.log('[Chat] message.delivered:', event);
                    updateTickDOM(event.messageId, 'delivered');
                    updateSidebarTick(currentConversationId, 'delivered');
                })

                /* ── Message read ─────────────────────────────────────── */
                .listen('.message.read', (event) => {
                    console.log('[Chat] message.read:', event);
                    // Mark ALL my sent messages in this conversation as read
                    document.querySelectorAll(`[id^="tick-"]`).forEach(el => {
                        const inner = el.querySelector('svg');
                        if (inner) el.innerHTML = tickSVG('read');
                    });
                    updateSidebarTick(currentConversationId, 'read');
                    // Tell Livewire to update its in-memory message array too
                    const component = getChatComponent();
                    if (component) component.call('markConversationRead', event.conversationId, event.readAt);
                })

                /* ── Message deleted ──────────────────────────────────── */
                .listen('.message.deleted', (event) => {
                    console.log('[Chat] message.deleted:', event);
                    markDeletedInDOM(event.messageId);
                    const component = getChatComponent();
                    if (component) component.call('handleRemoteDelete', event.messageId);
                })

                /* ── Typing indicator — pure DOM, no server call ─────── */
                .listen('.user.typing', (event) => {
                    console.log('[Chat] user.typing received:', event);
                    clearTimeout(_remoteTypingTimer);
                    if (event.isTyping) {
                        showTypingIndicator(event.userName, currentConversationId);
                        _remoteTypingTimer = setTimeout(() => hideTypingIndicator(), 4000);
                    } else {
                        hideTypingIndicator();
                    }
                });
        }

        /* ------------------------------------------------------------------
         | detectAndConnectConversation()
         | ----------------------------------------------------------------*/
        function detectAndConnectConversation() {
            const root = document.querySelector('.teams-chat-root');
            if (!root) return;

            const conversationId = root.dataset.conversation;

            if (conversationId && conversationId !== '' && conversationId !== 'null') {
                connectToConversation(conversationId);
            } else if (!conversationId || conversationId === '' || conversationId === 'null') {
                if (currentConversationChannel) {
                    Echo.leave(currentConversationChannel);
                    hideTypingIndicator();
                    console.log('[Chat] Left channel (conversation closed):', currentConversationChannel);
                    currentConversationChannel = null;
                    currentConversationId      = null;
                }
            }
        }

        detectAndConnectConversation();

        /* ------------------------------------------------------------------
         | Livewire commit hook — re-check channel + scroll after every render.
         |
         | NOTE: we deliberately do NOT call hideTypingIndicator() here.
         |       Re-renders (e.g. from the search input or sidebar clicks)
         |       must not wipe a live typing indicator.
         | ----------------------------------------------------------------*/
        Livewire.hook('commit', ({ component, commit, succeed }) => {
            succeed(() => {
                requestAnimationFrame(() => {
                    detectAndConnectConversation();
                    attachTypingListener();
                    scrollToBottom(true);
                    // Re-apply presence dots after any Livewire re-render
                    if (window._applyPresence) window._applyPresence();
                    // Refresh sidebar preview cache after every Livewire render
                    cacheSidebarPreviews();
                });

                // When sendMessage or forwardTo completes, immediately check
                // if recipients are online and mark the new message delivered.
                const calls = commit?.calls ?? [];
                const wasSend = calls.some(c =>
                    c.method === 'sendMessage' || c.method === 'forwardTo'
                );
                if (wasSend) {
                    setTimeout(() => window._checkAndMarkDelivered && window._checkAndMarkDelivered(), 100);
                }
            });
        });

        /* ------------------------------------------------------------------
         | Private user channel
         | ----------------------------------------------------------------*/
        const userId = document.body.dataset.userId;

        if (!userId) {
            console.warn('[Chat] data-user-id missing on <body> — realtime updates disabled.');
            return;
        }

        console.log(`[Chat] Subscribing to private channel: user.${userId}`);

        Echo.private(`user.${userId}`)

            .listen('.conversation.updated', (event) => {
                console.log('[Chat] conversation.updated:', event);

                const component = getChatComponent();
                if (component) component.call('refreshConversationData');

                refreshPendingCountBadge();
            })

            .listen('.pending.request.updated', (event) => {
                console.log('[Chat] pending.request.updated:', event);

                const component = getChatComponent();
                if (component) component.call('refreshPendingData');

                refreshPendingCountBadge();
            })

            /* ── Incoming message for a NON-open conversation ─────────────
             | MessageSent now also broadcasts on user.{recipientId} so we
             | receive it here regardless of which chat is open.
             | Skip if it belongs to the currently-open conversation (already
             | handled by the chat.{conversationId} channel listener above).
             | ────────────────────────────────────────────────────────────*/
            .listen('.message.sent', (event) => {
                console.log('[Chat] message.sent (user channel):', event);

                const incomingConvId = String(event.message.conversation_id);

                // If this conversation is already open, the chat channel
                // handler deals with it — don't double-process.
                if (incomingConvId === String(currentConversationId)) return;

                // Update sidebar preview + unread badge instantly in DOM.
                updateSidebarForNewMessage(event.message);
            })

            /* ── Delivered tick update — fires on user channel so sender
             | sees double grey tick even if they've switched conversations.
             | ────────────────────────────────────────────────────────────*/
            .listen('.message.delivered', (event) => {
                console.log('[Chat] message.delivered (user channel):', event);
                // Update the tick in the chat window (element exists when conv is open).
                updateTickDOM(event.messageId, 'delivered');
                // Update the sidebar tick for this conversation.
                updateSidebarTick(event.conversationId, 'delivered');
            })

            /* ── Read tick update — fires on user channel so sender sees
             | blue tick even if they've switched conversations.
             | ────────────────────────────────────────────────────────────*/
            .listen('.message.read', (event) => {
                console.log('[Chat] message.read (user channel):', event);
                // If the read conversation is currently open, update all ticks in the window.
                if (String(event.conversationId) === String(currentConversationId)) {
                    document.querySelectorAll('[id^="tick-"]').forEach(el => {
                        if (el.querySelector('svg')) el.innerHTML = tickSVG('read');
                    });
                }
                // Always update the sidebar tick.
                updateSidebarTick(event.conversationId, 'read');
                // Keep Livewire in-memory state in sync.
                const component = getChatComponent();
                if (component) component.call('markConversationRead', event.conversationId, event.readAt);
            })

            .listen('.user.typing', (event) => {
                console.log('[Chat] user.typing (user channel):', event);

                // Skip if already handled by the open conversation channel
                // (that handler shows both the chat bubble AND the sidebar).
                if (String(event.conversationId) === String(currentConversationId)) return;

                // This conversation is NOT open — update the SIDEBAR ONLY.
                // Never touch the in-chat typing bubble (#typing-indicator-row)
                // because that belongs to a different conversation's window.
                const convId  = event.conversationId;
                const preview = document.getElementById(`conv-preview-${convId}`);
                if (!preview) return;

                clearTimeout(_sidebarTypingTimer);

                if (event.isTyping) {
                    preview.classList.add('conv-preview--typing');
                    preview.innerHTML =
                        `<span class="sidebar-typing-dots" aria-hidden="true">` +
                            `<span class="sidebar-typing-dot"></span>` +
                            `<span class="sidebar-typing-dot"></span>` +
                            `<span class="sidebar-typing-dot"></span>` +
                        `</span> ${event.userName} is typing…`;

                    // Auto-restore the sidebar text if stop event is missed.
                    _sidebarTypingTimer = setTimeout(() => {
                        if (preview.classList.contains('conv-preview--typing')) {
                            restoreSidebarPreview(convId);
                        }
                    }, 4000);
                } else {
                    // Explicit stop — restore sidebar preview text.
                    if (preview.classList.contains('conv-preview--typing')) {
                        restoreSidebarPreview(convId);
                    }
                }
            });

    }); // end livewire:init

    /* ───────────────────────────────────────────────────────────────────────
     | SENDER-SIDE TYPING DETECTION
     |
     | Attaches to the #messageInput element.
     | Calls the Livewire broadcastTyping() method so the server fires
     | UserTyping event — but only to change the broadcast state, not to
     | re-render any UI on the sender's side.
     | ─────────────────────────────────────────────────────────────────────*/

    let _localTypingTimer      = null;  // stop-typing debounce (sender)
    let _localKeepAliveTimer   = null;  // re-broadcast keepalive (sender)
    let _isCurrentlyTyping     = false;

    // How often to re-broadcast isTyping=true while keys keep coming (ms).
    // Must be shorter than the receiver's auto-hide window (3 000 ms).
    const KEEPALIVE_INTERVAL = 2000;
    // How long of silence before we broadcast stop-typing (ms).
    const STOP_DELAY = 2000;

    /**
     * Broadcast stop-typing and clear all sender timers.
     */
    function stopTypingNow() {
        if (!_isCurrentlyTyping) return;
        _isCurrentlyTyping = false;

        clearTimeout(_localTypingTimer);
        clearInterval(_localKeepAliveTimer);
        _localTypingTimer    = null;
        _localKeepAliveTimer = null;

        const component = getChatComponent();
        if (component) component.call('broadcastTyping', false);
    }

    function attachTypingListener() {
        const input = document.getElementById('messageInput');
        if (!input || input._typingListenerAttached) return;

        input._typingListenerAttached = true;

        // ── input: user is actively typing ────────────────────────────────
        input.addEventListener('input', () => {
            const component = getChatComponent();
            if (!component) return;

            if (!_isCurrentlyTyping) {
                // First keystroke of a new burst — broadcast start immediately.
                _isCurrentlyTyping = true;
                component.call('broadcastTyping', true);

                // Keep re-broadcasting isTyping=true every KEEPALIVE_INTERVAL
                // so the receiver's 3 s auto-hide timer keeps getting reset.
                _localKeepAliveTimer = setInterval(() => {
                    if (_isCurrentlyTyping) {
                        component.call('broadcastTyping', true);
                    } else {
                        clearInterval(_localKeepAliveTimer);
                    }
                }, KEEPALIVE_INTERVAL);
            }

            // Reset the stop-typing debounce on every keystroke.
            clearTimeout(_localTypingTimer);
            _localTypingTimer = setTimeout(() => stopTypingNow(), STOP_DELAY);
        });

        // ── keydown Enter: stop before the message hits WebSocket ─────────
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                stopTypingNow();
            }
        });
    }

    // Attach on every Livewire update (input re-rendered) and initial load.
    document.addEventListener('livewire:update',    () => {
        // Only auto-focus on desktop so mobile keyboards don't pop up.
        if (window.innerWidth > 768) {
            const input = document.getElementById('messageInput');
            if (input) input.focus();
        }
        attachTypingListener();
    });
    document.addEventListener('livewire:navigated', () => attachTypingListener());
    document.addEventListener('DOMContentLoaded',   () => attachTypingListener());

    /* ───────────────────────────────────────────────────────────────────────
     | MOBILE SIDEBAR UX
     | ─────────────────────────────────────────────────────────────────────*/

    document.addEventListener('click', (e) => {
        const layout = document.getElementById('teamsLayout');
        if (!layout) return;

        if (e.target.closest('.conv-item') || e.target.closest('.request-toggle')) {
            layout.classList.add('conversation-open');
        }

        if (e.target.closest('#mobileBackBtn')) {
            layout.classList.remove('conversation-open');
        }
    });

    /* ───────────────────────────────────────────────────────────────────────
     | PROFILE PANEL — outside-click + Escape dismiss + real-time avatars
     |
     | The panel open/close state is owned by the Livewire Profile\Panel
     | component. JS only dispatches Livewire events to trigger toggle/close.
     | ─────────────────────────────────────────────────────────────────────*/

    // Track whether a profile-open click just happened so we can skip
    // the simultaneous outside-click handler on the same event.
    let _profileJustToggled = false;

    // Intercept the profile button click BEFORE it reaches Livewire so we
    // can set the guard flag. The button's own onclick still fires after this.
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('#profileBtn');
        if (btn) {
            _profileJustToggled = true;
            // Clear the guard after this event has fully propagated.
            setTimeout(() => { _profileJustToggled = false; }, 0);
        }
    }, true); // capture phase — runs before the bubbling outside-click handler

    document.addEventListener('click', (e) => {
        const wrap = document.getElementById('profileWrap');
        if (!wrap) return;
        // If click is inside profile-wrap, Livewire onclick handles toggling.
        if (wrap.contains(e.target)) return;
        // Guard: if we just toggled via the button, don't also close.
        if (_profileJustToggled) return;
        // Outside click → close the panel.
        Livewire.dispatch('close-profile-panel');
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            Livewire.dispatch('close-profile-panel');
        }
    });

    // Real-time avatar propagation via profile-updates channel.
    // Registered inside livewire:init so Echo is guaranteed to be available.
    document.addEventListener('livewire:init', () => {

        /* ── Helper: apply a status color to all status-bearing dots ──── */
        function applyStatusColor(userId, status, name, avatarUrl) {
            const statusColors = {
                available: '#23e07a',
                busy:      '#ff5f72',
                away:      '#ffb547',
                dnd:       '#ff5f72',
            };
            const color = statusColors[status] || statusColors.available;
            const myUserId = String(document.body.dataset.userId || '');
            const uid = String(userId);

            // ── Update avatar images for this user everywhere ──────────
            if (avatarUrl) {
                document.querySelectorAll(`[data-user-id="${uid}"]`).forEach(el => {
                    if (el.tagName === 'IMG') {
                        el.src = avatarUrl;
                    } else if (
                        !el.classList.contains('profile-status-dot') &&
                        !el.classList.contains('presence-dot') &&
                        !el.classList.contains('profile-status-dot-sm')
                    ) {
                        const img = document.createElement('img');
                        img.src            = avatarUrl;
                        img.alt            = name || '';
                        img.className      = el.className + ' profile-avatar--img';
                        img.dataset.userId = uid;
                        img.style.cssText  = el.style.cssText;
                        el.replaceWith(img);
                    }
                });
            }

            // ── If this is MY OWN update → update topbar status dot ───
            if (uid === myUserId) {
                document.querySelectorAll('.profile-status-dot').forEach(dot => {
                    dot.style.background = color;
                    dot.style.boxShadow  = `0 0 6px ${color}`;
                });
                document.querySelectorAll('.nav-profile .presence').forEach(dot => {
                    dot.style.background = color;
                });
                if (name) {
                    document.querySelectorAll('.profile-name').forEach(el => {
                        el.textContent = name;
                    });
                    document.querySelectorAll('.profile-panel-display-name').forEach(el => {
                        el.textContent = name;
                    });
                }
            }

            // ── Update presence-dots in sidebar/chat for this user ────
            // (Sidebar conversation items show a dot per user)
            document.querySelectorAll(`.presence-dot[data-presence-uid="${uid}"]`).forEach(dot => {
                // Only color if they are online per our presence set
                // Keep presence-online logic intact — just tint the dot color
                if (dot.classList.contains('presence-online') && uid === myUserId) {
                    dot.style.background = color;
                    dot.style.boxShadow  = `0 0 5px ${color}`;
                }
            });
        }

        // Listen on the public broadcast channel (all users see each other's updates)
        Echo.channel('profile-updates')
            .listen('.profile.updated', (event) => {
                console.log('[Profile] profile.updated received:', event);
                applyStatusColor(event.userId, event.status, event.name, event.avatarUrl);
            });
    });

    // Also listen for the Livewire dispatch 'profile-saved' event —
    // this fires on the SENDER's own tab immediately after save()
    // so their topbar dot updates without waiting for the broadcast round-trip.
    document.addEventListener('livewire:init', () => {
        Livewire.on('profile-saved', () => {
            // Re-read the status from the currently active status button
            const activeBtn = document.querySelector('.profile-status-btn--active');
            if (!activeBtn) return;
            const statusTitle = activeBtn.title?.toLowerCase() || 'available';
            const statusMap = {
                available: 'available',
                busy: 'busy',
                away: 'away',
                'do not disturb': 'dnd',
            };
            const status = statusMap[statusTitle] || 'available';
            const myUserId = document.body.dataset.userId || '';
            const name = document.querySelector('.profile-panel-display-name')?.textContent?.trim() || '';
            const avatarImg = document.querySelector(`#avatarPreviewWrap img`);
            const avatarUrl = avatarImg ? avatarImg.src : null;

            const statusColors = {
                available: '#23e07a',
                busy:      '#ff5f72',
                away:      '#ffb547',
                dnd:       '#ff5f72',
            };
            const color = statusColors[status] || '#23e07a';

            document.querySelectorAll('.profile-status-dot').forEach(dot => {
                dot.style.background = color;
                dot.style.boxShadow  = `0 0 6px ${color}`;
            });
            document.querySelectorAll('.nav-profile .presence').forEach(dot => {
                dot.style.background = color;
            });
            if (name) {
                document.querySelectorAll('.profile-name').forEach(el => el.textContent = name);
            }
        });
    });

    /* ───────────────────────────────────────────────────────────────────────
     | GLOBAL SEARCH — blur on outside click
     | ─────────────────────────────────────────────────────────────────────*/

    document.addEventListener('click', (e) => {
        const wrap = document.getElementById('globalSearchWrap');
        if (!wrap) return;

        if (!wrap.contains(e.target)) {
            const input = document.getElementById('globalSearchInput');
            if (input) input.blur();
        }
    });

    /* ───────────────────────────────────────────────────────────────────────
     | LAST SEEN PING
     |
     | POST /presence/ping periodically to keep last_seen fresh in the DB.
     | This is the DB-side fallback; real presence is tracked via the
     | presence channel above.
     |
     | Fires on:
     |  - DOMContentLoaded (initial load)
     |  - every 60 s while the tab is visible
     |  - when the tab becomes visible again after being hidden
     |  - before the page unloads (best-effort)
     | ─────────────────────────────────────────────────────────────────────*/

    const _csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    function pingLastSeen() {
        if (!_csrfToken) return;
        // Use sendBeacon for unload (non-blocking); fetch for regular pings.
        navigator.sendBeacon
            ? navigator.sendBeacon('/presence/ping', (() => {
                  const fd = new FormData();
                  fd.append('_token', _csrfToken);
                  return fd;
              })())
            : fetch('/presence/ping', {
                  method: 'POST',
                  headers: { 'X-CSRF-TOKEN': _csrfToken, 'Accept': 'application/json' },
              }).catch(() => {});
    }

    document.addEventListener('DOMContentLoaded', pingLastSeen);

    // Ping every 60 s while the tab is active
    setInterval(() => {
        if (document.visibilityState === 'visible') pingLastSeen();
    }, 60_000);

    // Ping when tab becomes visible (user switches back to this tab)
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') pingLastSeen();
    });

    // Best-effort ping on unload
    window.addEventListener('beforeunload', pingLastSeen);

    /* ───────────────────────────────────────────────────────────────────────
     | LIVE TIMESTAMPS
     |
     | Updates every [data-timestamp] element in the chat window so
     | message times stay current without any server round-trip.
     |
     | Logic (mirrors WhatsApp/Telegram):
     |   < 1 min   → "just now"
     |   1–59 min  → "5 min ago"
     |   same day  → "2:35 PM"          (fixed clock time, already past 1 h)
     |   yesterday → "Yesterday 2:35 PM"
     |   older     → "Jun 10, 2:35 PM"
     |
     | Re-runs every 30 s. Also triggered after each Livewire commit so
     | freshly appended messages get the right label immediately.
     | ─────────────────────────────────────────────────────────────────────*/

    function formatLiveTime(isoString) {
        const date  = new Date(isoString);
        const now   = new Date();
        const diffS = Math.floor((now - date) / 1000);

        if (diffS < 60)  return 'just now';
        if (diffS < 3600) {
            const m = Math.floor(diffS / 60);
            return `${m} min ago`;
        }

        // Same calendar day — show clock time only
        const isToday = date.toDateString() === now.toDateString();
        if (isToday) {
            return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        }

        // Yesterday
        const yesterday = new Date(now);
        yesterday.setDate(now.getDate() - 1);
        if (date.toDateString() === yesterday.toDateString()) {
            return 'Yesterday ' + date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        }

        // Older — "Jun 10, 2:35 PM"
        return date.toLocaleDateString([], { month: 'short', day: 'numeric' })
             + ', '
             + date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    }

    function updateLiveTimestamps() {
        document.querySelectorAll('[data-timestamp]').forEach(el => {
            const iso = el.dataset.timestamp;
            if (!iso) return;
            el.textContent = formatLiveTime(iso);
        });
    }

    // Run immediately, then every 30 s
    document.addEventListener('DOMContentLoaded', updateLiveTimestamps);
    setInterval(updateLiveTimestamps, 30_000);

    // Also run after every Livewire re-render (new messages appended)
    document.addEventListener('livewire:update', updateLiveTimestamps);

})();
