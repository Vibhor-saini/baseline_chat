/**
 * chat.js
 * ───────────────────────────────────────────────────────────────────────────
 * Realtime layer for Baseline Chat.
 *
 * Responsibilities:
 *  1. Scroll management — keep the chat anchored to the bottom.
 *  2. Conversation channel — subscribe to the active chat's private channel
 *     and push incoming messages into the Livewire component.
 *  3. User channel — subscribe to each logged-in user's own private channel
 *     so request / conversation events are received instantly.
 *  4. PendingCount badge — refresh the tiny nav badge component after events.
 *  5. Mobile UX — show/hide sidebar on small screens.
 *  6. Desktop UX — auto-focus the message input after renders.
 *
 * All Echo / Livewire interaction is scoped to authenticated users only.
 * Private channels are used exclusively — no public broadcast channels.
 * ───────────────────────────────────────────────────────────────────────────
 */

(function () {
    'use strict';

    /* ───────────────────────────────────────────────────────────────────────
     | SCROLL HELPERS
     | ─────────────────────────────────────────────────────────────────────*/

    /**
     * Scroll the message list to the bottom.
     * @param {boolean} smooth - Use smooth scrolling (true) or instant (false).
     */
    function scrollToBottom(smooth = false) {
        const anchor = document.getElementById('scroll-anchor');
        if (!anchor) return;
        anchor.scrollIntoView({ behavior: smooth ? 'smooth' : 'instant', block: 'end' });
    }

    /* ───────────────────────────────────────────────────────────────────────
     | LIVEWIRE COMPONENT ACCESSOR
     | ─────────────────────────────────────────────────────────────────────*/

    /**
     * Find the Livewire component instance for the main chat component.
     * Walks up from `.teams-chat-root` to find the nearest `wire:id` element.
     *
     * @returns {object|null} Livewire component or null if not found.
     */
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

    /**
     * Tell the PendingCount Livewire component to re-query the count.
     * This updates the nav-rail badge in real time without a full page refresh.
     */
    function refreshPendingCountBadge() {
        Livewire.getByName('chat.pending-count').forEach(component => {
            component.call('refreshCount');
        });
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
         | Prevents duplicate Echo subscriptions when Livewire re-renders.
         | ----------------------------------------------------------------*/
        let currentConversationChannel = null;
        let currentConversationId      = null;

        /* ------------------------------------------------------------------
         | connectToConversation(conversationId)
         |
         | Subscribe to a private conversation channel for MessageSent events.
         | Safely leaves the old channel before joining the new one.
         | ----------------------------------------------------------------*/
        function connectToConversation(conversationId) {
            // No-op if already subscribed to this conversation.
            if (!conversationId || String(conversationId) === String(currentConversationId)) {
                return;
            }

            // Leave the previous channel cleanly.
            if (currentConversationChannel) {
                Echo.leave(currentConversationChannel);
                console.log('[Chat] Left channel:', currentConversationChannel);
            }

            currentConversationChannel = `chat.${conversationId}`;
            currentConversationId      = conversationId;

            console.log('[Chat] Joined channel:', currentConversationChannel);

            Echo.private(currentConversationChannel)
                .listen('.message.sent', (event) => {
                    console.log('[Chat] message.sent received:', event);

                    const component = getChatComponent();
                    if (!component) return;

                    component.call('appendMessage', event.message);

                    // Let the DOM update before scrolling.
                    setTimeout(() => scrollToBottom(true), 150);
                });
        }

        /* ------------------------------------------------------------------
         | detectAndConnectConversation()
         |
         | Read the selected conversation ID from the Blade root element's
         | data attribute and (re)connect if it has changed.
         | ----------------------------------------------------------------*/
        function detectAndConnectConversation() {
            const root = document.querySelector('.teams-chat-root');
            if (!root) return;

            const conversationId = root.dataset.conversation;

            if (conversationId && conversationId !== '' && conversationId !== 'null') {
                connectToConversation(conversationId);
            } else if (!conversationId || conversationId === '' || conversationId === 'null') {
                // Conversation was closed — leave the channel.
                if (currentConversationChannel) {
                    Echo.leave(currentConversationChannel);
                    console.log('[Chat] Left channel (conversation closed):', currentConversationChannel);
                    currentConversationChannel = null;
                    currentConversationId      = null;
                }
            }
        }

        // Initial connection attempt on page load.
        detectAndConnectConversation();

        /* ------------------------------------------------------------------
         | Livewire commit hook
         |
         | After every Livewire re-render: re-check channel and scroll down.
         | ----------------------------------------------------------------*/
        Livewire.hook('commit', ({ succeed }) => {
            succeed(() => {
                requestAnimationFrame(() => {
                    detectAndConnectConversation();
                    scrollToBottom(true);
                });
            });
        });

        /* ------------------------------------------------------------------
         | Private user channel
         |
         | Each logged-in user subscribes to user.{id} for request and
         | conversation events. The user ID is stored on <body data-user-id>.
         | ----------------------------------------------------------------*/
        const userId = document.body.dataset.userId;

        if (!userId) {
            console.warn('[Chat] data-user-id missing on <body> — realtime updates disabled.');
            return;
        }

        console.log(`[Chat] Subscribing to private channel: user.${userId}`);

        Echo.private(`user.${userId}`)

            /*
             | ConversationUpdated
             | Fires when a new conversation is created or its status changes
             | (e.g. a pending request is accepted).
             | Action: refresh conversations + all request lists.
             */
            .listen('.conversation.updated', (event) => {
                console.log('[Chat] conversation.updated:', event);

                const component = getChatComponent();
                if (component) {
                    component.call('refreshConversationData');
                }

                refreshPendingCountBadge();
            })

            /*
             | PendingRequestUpdated
             | Fires when a request is sent, accepted, or rejected.
             | Action: refresh only the pending / sent-request lists.
             */
            .listen('.pending.request.updated', (event) => {
                console.log('[Chat] pending.request.updated:', event);

                const component = getChatComponent();
                if (component) {
                    component.call('refreshPendingData');
                }

                refreshPendingCountBadge();
            });

    }); // end livewire:init

    /* ───────────────────────────────────────────────────────────────────────
     | MOBILE SIDEBAR UX
     | ─────────────────────────────────────────────────────────────────────*/

    document.addEventListener('click', (e) => {
        const layout = document.getElementById('teamsLayout');
        if (!layout) return;

        // Opening a chat or request → slide main panel into view on mobile.
        if (e.target.closest('.conv-item') || e.target.closest('.request-toggle')) {
            layout.classList.add('conversation-open');
        }

        // Back button → slide the sidebar back into view on mobile.
        if (e.target.closest('#mobileBackBtn')) {
            layout.classList.remove('conversation-open');
        }
    });

    /* ───────────────────────────────────────────────────────────────────────
     | PROFILE DROPDOWN
     | ─────────────────────────────────────────────────────────────────────*/

    document.addEventListener('click', (e) => {
        const btn      = document.getElementById('profileBtn');
        const dropdown = document.getElementById('profileDropdown');
        const wrap     = document.getElementById('profileWrap');

        if (!btn || !dropdown || !wrap) return;

        if (wrap.contains(e.target)) {
            // Toggle.
            const isOpen = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', String(!isOpen));
            dropdown.classList.toggle('profile-dropdown-open', !isOpen);
        } else {
            // Close when clicking outside.
            btn.setAttribute('aria-expanded', 'false');
            dropdown.classList.remove('profile-dropdown-open');
        }
    });

    /* ───────────────────────────────────────────────────────────────────────
     | GLOBAL SEARCH — close on outside click
     | ─────────────────────────────────────────────────────────────────────*/

    document.addEventListener('click', (e) => {
        const wrap = document.getElementById('globalSearchWrap');
        if (!wrap) return;

        // If click is outside the search wrap, Livewire handles clearing via
        // wire:model, but we also blur the input for clean UX.
        if (!wrap.contains(e.target)) {
            const input = document.getElementById('globalSearchInput');
            if (input) input.blur();
        }
    });

    /* ───────────────────────────────────────────────────────────────────────
     | AUTO FOCUS MESSAGE INPUT ON DESKTOP
     | ─────────────────────────────────────────────────────────────────────*/

    document.addEventListener('livewire:update', () => {
        if (window.innerWidth > 768) {
            const input = document.getElementById('messageInput');
            if (input) input.focus();
        }
    });

})();