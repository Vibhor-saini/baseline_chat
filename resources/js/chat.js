(function () {

    /*
    |--------------------------------------------------------------------------
    | SCROLL TO BOTTOM
    |--------------------------------------------------------------------------
    */

    function scrollToBottom(smooth = false) {
        const anchor = document.getElementById('scroll-anchor');
        if (!anchor) return;
        anchor.scrollIntoView({ behavior: smooth ? 'smooth' : 'instant', block: 'end' });
    }

    /*
    |--------------------------------------------------------------------------
    | GET LIVEWIRE COMPONENT INSTANCE
    | Walks up from .teams-chat-root to find the nearest wire:id element.
    |--------------------------------------------------------------------------
    */

    function getLivewireComponent() {
        const root = document.querySelector('.teams-chat-root');
        if (!root) return null;

        // The Livewire component element may be the root itself or a parent.
        const el = root.closest('[wire\\:id]') ?? root.querySelector('[wire\\:id]');
        if (!el) return null;

        return Livewire.find(el.getAttribute('wire:id'));
    }

    /*
    |--------------------------------------------------------------------------
    | SCROLL ON PAGE LOAD + NAVIGATION
    |--------------------------------------------------------------------------
    */

    document.addEventListener('DOMContentLoaded', () => scrollToBottom());
    document.addEventListener('livewire:navigated', () => scrollToBottom());


    /*
|--------------------------------------------------------------------------
| REFRESH PENDING COUNT BADGE
| Finds the PendingCount Livewire component and tells it to re-count.
|--------------------------------------------------------------------------
*/
function refreshPendingCountBadge() {
    // Livewire 3: find component by name
    Livewire.getByName('chat.pending-count').forEach(component => {
        component.call('refreshCount');
    });
}
    
    /*
    |--------------------------------------------------------------------------
    | LIVEWIRE INIT — all realtime logic lives here
    |--------------------------------------------------------------------------
    */

    document.addEventListener('livewire:init', () => {

        let activeConversationChannel = null;
        let activeConversationId      = null;

        /*
        |----------------------------------------------------------------------
        | CONNECT TO A CONVERSATION'S BROADCAST CHANNEL
        | Prevents duplicate subscriptions by tracking the active channel.
        |----------------------------------------------------------------------
        */

        function connectToConversation(conversationId) {
            if (!conversationId || activeConversationId == conversationId) return;

            // Leave the previous channel cleanly.
            if (activeConversationChannel) {
                Echo.leave(activeConversationChannel);
            }

            activeConversationChannel = `chat.${conversationId}`;
            activeConversationId      = conversationId;

            console.log('[Chat] Connected to channel:', activeConversationChannel);

            Echo.private(activeConversationChannel)
                .listen('.message.sent', (event) => {
                    console.log('[Chat] Message received:', event);

                    const component = getLivewireComponent();
                    if (!component) return;

                    component.call('appendMessage', event.message);

                    // Small delay lets the DOM update before scrolling.
                    setTimeout(() => scrollToBottom(true), 150);
                });
        }

        /*
        |----------------------------------------------------------------------
        | DETECT ACTIVE CONVERSATION FROM DOM
        | The Blade template stores the selected conversation ID on the root div.
        |----------------------------------------------------------------------
        */

        function detectAndConnectConversation() {
            const root = document.querySelector('.teams-chat-root');
            if (!root) return;

            const conversationId = root.dataset.conversation;

            if (conversationId && conversationId !== String(activeConversationId)) {
                connectToConversation(conversationId);
            }
        }

        // Run once immediately on init.
        detectAndConnectConversation();

        /*
        |----------------------------------------------------------------------
        | AFTER EVERY LIVEWIRE COMMIT (re-render)
        | Re-check if the active conversation changed, and scroll to bottom.
        |----------------------------------------------------------------------
        */

        Livewire.hook('commit', ({ succeed }) => {
            succeed(() => {
                requestAnimationFrame(() => {
                    detectAndConnectConversation();
                    scrollToBottom(true);
                });
            });
        });

        /*
        |----------------------------------------------------------------------
        | PRIVATE USER CHANNEL
        | Each logged-in user subscribes to their own private channel so they
        | receive realtime updates for requests and conversation changes.
        |----------------------------------------------------------------------
        */

        const userId = document.body.dataset.userId;

        if (!userId) {
            console.warn('[Chat] No userId found on body — private channel not connected.');
            return;
        }

        Echo.private(`user.${userId}`)

            /*
            |------------------------------------------------------------------
            | ConversationUpdated — fires when:
            |   - A new conversation is accepted
            |   - A request is sent (so both sides update instantly)
            | Action: refresh conversations + pending lists
            |------------------------------------------------------------------
            */
            .listen('.conversation.updated', (event) => {
                console.log('[Chat] conversation.updated:', event);

                const component = getLivewireComponent();
                if (component) component.call('refreshConversationData');

                // Also refresh the nav badge component
                refreshPendingCountBadge();
            })

            /*
            |------------------------------------------------------------------
            | PendingRequestUpdated — fires when:
            |   - A request is sent, accepted, or rejected
            | Action: refresh pending + sent request lists only
            |------------------------------------------------------------------
            */
            .listen('.pending.request.updated', (event) => {
                console.log('[Chat] pending.request.updated:', event);

                const component = getLivewireComponent();
                if (component) component.call('refreshPendingData');

                // Also refresh the nav badge component
                refreshPendingCountBadge();
            });

                console.log(`[Chat] Subscribed to private channel: user.${userId}`);
            });

    /*
    |--------------------------------------------------------------------------
    | MOBILE SIDEBAR UX
    | Adds/removes a class to show/hide the sidebar on small screens.
    |--------------------------------------------------------------------------
    */

    document.addEventListener('click', (e) => {
        const layout = document.querySelector('.teams-layout');
        if (!layout) return;

        if (e.target.closest('.conv-item') || e.target.closest('.request-toggle')) {
            layout.classList.add('conversation-open');
        }

        if (e.target.closest('#mobileBackBtn')) {
            layout.classList.remove('conversation-open');
        }
    });

    /*
    |--------------------------------------------------------------------------
    | AUTO FOCUS MESSAGE INPUT ON DESKTOP
    |--------------------------------------------------------------------------
    */

    document.addEventListener('livewire:update', () => {
        const input = document.getElementById('messageInput');
        if (input && window.innerWidth > 768) {
            input.focus();
        }
    });

})();