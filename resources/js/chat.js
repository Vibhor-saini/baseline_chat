/**
 * chat.js — Baseline Chat realtime layer
 * Handles: presence, typing, ticks, profile updates, status colors, avatars.
 */
(function () {
    'use strict';

    /* ── Status color map ────────────────────────────────────────────────── */
    const STATUS_COLORS = {
        available: '#23e07a',
        busy:      '#ff5f72',
        away:      '#ffb547',
        dnd:       '#ff5f72',
    };

    const STATUS_LABELS = {
        available: 'Available',
        busy:      'Busy',
        away:      'Away',
        dnd:       'Do Not Disturb',
    };

    function statusColor(s) { return STATUS_COLORS[s] || STATUS_COLORS.available; }
    function statusLabel(s) { return STATUS_LABELS[s] || 'Available'; }

    /* ── Scroll helpers ──────────────────────────────────────────────────── */
    function scrollToBottom(smooth = false) {
        const anchor = document.getElementById('scroll-anchor');
        if (!anchor) return;
        anchor.scrollIntoView({ behavior: smooth ? 'smooth' : 'instant', block: 'end' });
    }

    /* ── Livewire component accessor ─────────────────────────────────────── */
    function getChatComponent() {
        const root = document.querySelector('.teams-chat-root');
        if (!root) return null;
        const el = root.closest('[wire\\:id]') ?? root.querySelector('[wire\\:id]');
        if (!el) return null;
        return Livewire.find(el.getAttribute('wire:id'));
    }

    /* ── Pending count badge ─────────────────────────────────────────────── */
    function refreshPendingCountBadge() {
        Livewire.getByName('chat.pending-count').forEach(c => c.call('refreshCount'));
    }

    /* ── Sidebar preview cache ───────────────────────────────────────────── */
    const _sidebarPreviewCache = new Map();

    function cacheSidebarPreviews() {
        document.querySelectorAll('[id^="conv-preview-"]').forEach(el => {
            const convId = el.id.replace('conv-preview-', '');
            const val = el.dataset.lastPreview;
            if (val !== undefined && val !== '') _sidebarPreviewCache.set(convId, val);
        });
    }

    function restoreSidebarPreview(convId) {
        const preview = document.getElementById(`conv-preview-${convId}`);
        if (!preview) return;
        const text = _sidebarPreviewCache.get(String(convId))
                  || preview.dataset.lastPreview || '';
        preview.textContent = text;
        preview.classList.remove('conv-preview--typing');
    }


    /* ── Typing indicator ────────────────────────────────────────────────── */
    let _remoteTypingTimer     = null;
    let _sidebarTypingTimer    = null;
    let _activeConvIdForTyping = null;

    function showTypingIndicator(userName, conversationId) {
        _activeConvIdForTyping = String(conversationId);
        const row    = document.getElementById('typing-indicator-row');
        const avatar = document.getElementById('typing-indicator-avatar');
        const label  = document.getElementById('typing-indicator-label');
        if (row) {
            if (avatar) avatar.textContent = userName ? userName.charAt(0).toUpperCase() : '?';
            if (label)  label.textContent  = `${userName} is typing…`;
            row.style.display = '';
            row.setAttribute('aria-label', `${userName} is typing`);
        }
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
        setTimeout(() => scrollToBottom(true), 80);
    }

    function hideTypingIndicator() {
        const row = document.getElementById('typing-indicator-row');
        if (row) { row.style.display = 'none'; row.removeAttribute('aria-label'); }
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

    /* ── Tick / delivery status DOM helpers ──────────────────────────────── */
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
        return `<svg class="tick tick--sent" width="10" height="11" viewBox="0 0 10 11" fill="none" aria-label="Sent">
            <path d="M1 5.5L4.5 9 9 3" stroke="#9090b0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>`;
    }

    function updateTickDOM(messageId, status) {
        const el = document.getElementById(`tick-${messageId}`);
        if (el) el.innerHTML = tickSVG(status);
    }

    function updateSidebarTick(conversationId, status) {
        const preview = document.getElementById(`conv-preview-${conversationId}`);
        if (!preview) return;
        const tick = preview.querySelector('.tick');
        if (tick) tick.outerHTML = tickSVG(status);
    }

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
        const actions = row.querySelector('.msg-actions');
        if (actions) actions.remove();
        const fwd = row.querySelector('.fwd-label');
        if (fwd) fwd.remove();
        const timeWrap = row.querySelector('.msg-time-wrap');
        if (timeWrap) timeWrap.remove();
    }


    /* ── Sidebar instant update for new messages ─────────────────────────── */
    function updateSidebarForNewMessage(message) {
        const convId  = message.conversation_id;
        const preview = document.getElementById(`conv-preview-${convId}`);
        const badge   = document.getElementById(`unread-${convId}`);
        if (preview && !preview.classList.contains('conv-preview--typing')) {
            let text = '';
            if (message.type === 'image')     text = '📷 Image';
            else if (message.type === 'file') text = '📎 File';
            else                              text = (message.body || '').substring(0, 30);
            preview.textContent = text;
            preview.dataset.lastPreview = text;
            _sidebarPreviewCache.set(String(convId), text);
        }
        if (badge) {
            const current = parseInt(badge.textContent, 10) || 0;
            const next    = current + 1;
            badge.textContent   = next > 99 ? '99+' : String(next);
            badge.style.display = '';
        }
    }

    /* ═══════════════════════════════════════════════════════════════════════
     | AVATAR & STATUS UPDATE HELPERS
     | Applies profile updates (avatar, status, name) to ALL matching DOM
     | elements — topbar, sidebar dots, chat header, message avatars, etc.
     ════════════════════════════════════════════════════════════════════════*/

    /**
     * User-status → dot color. Returns CSS hex string.
     */
    function getStatusDotColor(status) {
        return statusColor(status);
    }

    /**
     * Apply a profile update for a given userId to all relevant DOM nodes.
     * @param {string|number} userId
     * @param {string}        status  e.g. 'available', 'busy', 'away', 'dnd'
     * @param {string}        name    display name (may be empty)
     * @param {string}        avatarUrl  full URL or empty string
     */
    function applyProfileUpdate(userId, status, name, avatarUrl) {
        const uid       = String(userId);
        const myUserId  = String(document.body.dataset.userId || '');
        const color     = statusColor(status);
        const label     = statusLabel(status);
        const isMe      = uid === myUserId;

        /* 1. Update avatar images / initials for this user everywhere */
        if (avatarUrl) {
            document.querySelectorAll(`[data-user-id="${uid}"]`).forEach(el => {
                // Only update actual avatar elements — skip status dots, presence dots, etc.
                const isAvatarImg = el.tagName === 'IMG' && (
                    el.classList.contains('profile-avatar-img') ||
                    el.classList.contains('pp-avatar-img') ||
                    el.classList.contains('msg-avatar-img') ||
                    el.classList.contains('conv-avatar--img') ||
                    el.id === 'topbarAvatarImg' ||
                    el.id === 'avatarPreviewImg' ||
                    el.closest('.msg-avatar') ||
                    el.closest('.conv-avatar-wrap') ||
                    el.closest('.chat-header-avatar') ||
                    el.closest('.pp-avatar-wrap')
                );

                const isInitialsDiv = el.tagName === 'DIV' && (
                    el.classList.contains('conv-avatar') ||
                    el.classList.contains('pp-avatar-initials') ||
                    el.id === 'topbarAvatarInitials' ||
                    el.id === 'avatarPreviewImg'
                );

                // Also handle the chat-header-avatar container (has text content, no <img>)
                const isHeaderAvatar = el.classList.contains('chat-header-avatar');

                if (isAvatarImg) {
                    el.src = avatarUrl;
                    el.style.borderRadius = '50%';
                    el.style.objectFit   = 'cover';
                } else if (isHeaderAvatar) {
                    el.innerHTML = '';
                    const img = document.createElement('img');
                    img.src              = avatarUrl;
                    img.alt              = name || '';
                    img.dataset.userId   = uid;
                    img.style.cssText    = 'width:100%;height:100%;border-radius:50%;object-fit:cover;';
                    el.appendChild(img);
                } else if (isInitialsDiv) {
                    const img = document.createElement('img');
                    img.src            = avatarUrl;
                    img.alt            = name || '';
                    img.dataset.userId = uid;
                    img.id             = el.id || '';
                    if (el.classList.contains('conv-avatar')) {
                        img.className = 'conv-avatar conv-avatar--img';
                        img.style.cssText = 'width:42px;height:42px;border-radius:50%;object-fit:cover;';
                    } else if (el.classList.contains('pp-avatar-initials')) {
                        img.className = 'pp-avatar-img';
                        img.style.cssText = 'width:52px;height:52px;border-radius:50%;object-fit:cover;';
                    } else {
                        img.style.cssText = 'width:100%;height:100%;border-radius:50%;object-fit:cover;';
                    }
                    el.replaceWith(img);
                }
                // All other [data-user-id] elements (msg-avatar-mine wrapper divs, spans, etc.) — skip
            });
        }

        /* 2. Update presence-dot colors in sidebar/conversation list */
        document.querySelectorAll(`.presence-dot[data-presence-uid="${uid}"]`).forEach(dot => {
            dot.dataset.userStatus = status;
            if (dot.classList.contains('presence-online')) {
                dot.style.background = color;
                dot.style.boxShadow  = `0 0 5px ${color}`;
                dot.title = label;
            }
        });

        /* 3. Update chat-header status for the currently open conversation */
        const headerStatus = document.getElementById('chat-header-status');
        if (headerStatus && String(headerStatus.dataset.presenceUid) === uid) {
            headerStatus.dataset.userStatus = status;
            const dot  = document.getElementById('chat-header-status-dot');
            const text = document.getElementById('chat-header-status-text');
            // Update header status whenever the user is online (has status-online OR is in onlineUserIds).
            // We always update — the color reflects the actual status, not just online/offline.
            if (dot) {
                dot.style.background = color;
                dot.style.boxShadow  = `0 0 6px ${color}`;
                // Ensure online class is present so the dot shows
                dot.classList.add('status-online');
                dot.classList.remove('status-offline');
            }
            if (text) {
                text.textContent = label;
                text.style.color = color;
            }
            headerStatus.style.color = color;
        }

        /* 4. If it's MY own update — update topbar elements */
        if (isMe) {
            // Status dot on the topbar profile button
            const topbarDot = document.getElementById('topbarStatusDot');
            if (topbarDot) {
                // Remove all status classes, add the right one
                topbarDot.className = topbarDot.className
                    .replace(/pp-status-dot--\S+/g, '')
                    .replace(/profile-status-dot--\S+/g, '')
                    .trim();
                topbarDot.style.background = color;
                topbarDot.style.boxShadow  = `0 0 6px ${color}`;
            }
            // Profile name in topbar
            if (name) {
                const nameEl = document.getElementById('topbarProfileName');
                if (nameEl) nameEl.textContent = name;
            }
            // Nav-rail profile dot (bottom of left nav)
            document.querySelectorAll('.nav-profile .presence').forEach(dot => {
                dot.style.background = color;
                dot.style.boxShadow  = `0 0 4px ${color}`;
            });
            // Profile panel header status dot + label (if panel is open)
            const ppDot = document.querySelector('.pp-status-dot');
            if (ppDot) {
                ppDot.className = ppDot.className
                    .replace(/pp-status-dot--\S+/g, '').trim() + ` pp-status-dot--${status}`;
            }
        }
    }


    /* ── Page load scroll ────────────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', () => scrollToBottom());
    document.addEventListener('livewire:navigated', () => scrollToBottom());

    /* ═══════════════════════════════════════════════════════════════════════
     | LIVEWIRE INIT — all realtime wiring
     ════════════════════════════════════════════════════════════════════════*/
    document.addEventListener('livewire:init', () => {

        /* -- Presence state ------------------------------------------------*/
        const onlineUserIds = new Set();
        // Map<userId:string, status:string>  — tracks the known status per user
        const userStatusMap = new Map();

        function applyPresence() {
            document.querySelectorAll('[data-presence-uid]').forEach(el => {
                const uid    = String(el.dataset.presenceUid);
                const isOnline = onlineUserIds.has(uid);
                // Determine color: use known status if online, else grey
                const status  = isOnline ? (userStatusMap.get(uid) || 'available') : 'offline';
                const color   = isOnline ? statusColor(status) : '#4e4e6e';
                const label   = isOnline ? statusLabel(status) : 'Offline';

                if (el.classList.contains('presence-dot')) {
                    el.classList.toggle('presence-online',  isOnline);
                    el.classList.toggle('presence-offline', !isOnline);
                    el.style.background = color;
                    el.style.boxShadow  = isOnline ? `0 0 5px ${color}` : 'none';
                    el.title = label;
                }

                if (el.id === 'chat-header-status') {
                    const dot  = document.getElementById('chat-header-status-dot');
                    const text = document.getElementById('chat-header-status-text');
                    if (isOnline) {
                        const userStatus = userStatusMap.get(uid) || el.dataset.userStatus || 'available';
                        const c = statusColor(userStatus);
                        if (dot) {
                            dot.classList.add('status-online');
                            dot.classList.remove('status-offline');
                            dot.style.background = c;
                            dot.style.boxShadow  = `0 0 6px ${c}`;
                        }
                        if (text) {
                            text.textContent   = statusLabel(userStatus);
                            text.style.color   = c;
                        }
                        // Also tint the parent wrapper
                        el.style.color = c;
                    } else {
                        if (dot) {
                            dot.classList.remove('status-online');
                            dot.classList.add('status-offline');
                            dot.style.background = '#4e4e6e';
                            dot.style.boxShadow  = 'none';
                        }
                        if (text) {
                            text.textContent = el.dataset.lastSeen || 'Offline';
                            text.style.color = '#8a8aaa';
                        }
                        el.style.color = '#8a8aaa';
                    }
                }
            });
        }

        console.log('[Chat] Joining presence channel: presence.chat');

        Echo.join('presence.chat')
            .here((members) => {
                console.log('[Presence] here:', members);
                onlineUserIds.clear();
                members.forEach(m => {
                    onlineUserIds.add(String(m.id));
                    if (m.status) userStatusMap.set(String(m.id), m.status);
                });
                applyPresence();
                setTimeout(() => window._checkAndMarkDelivered && window._checkAndMarkDelivered(), 500);
            })
            .joining((member) => {
                console.log('[Presence] joining:', member);
                onlineUserIds.add(String(member.id));
                if (member.status) userStatusMap.set(String(member.id), member.status);
                applyPresence();
                setTimeout(() => window._checkAndMarkDelivered && window._checkAndMarkDelivered(), 200);
            })
            .leaving((member) => {
                console.log('[Presence] leaving:', member);
                onlineUserIds.delete(String(member.id));
                applyPresence();
            })
            .error((err) => console.error('[Presence] channel error:', err));

        window._applyPresence = applyPresence;
        window._onlineUserIds = onlineUserIds;
        window._checkAndMarkDelivered = function () {
            const onlineIds = Array.from(onlineUserIds).map(Number).filter(Boolean);
            if (onlineIds.length === 0) return;
            const component = getChatComponent();
            if (component) component.call('markDeliveredForOnlineRecipients', onlineIds);
        };


        /* ── Profile updates channel (public) ──────────────────────────── */
        Echo.channel('profile-updates')
            .listen('.profile.updated', (event) => {
                console.log('[Profile] profile.updated received:', event);
                // Update the userStatusMap so presence re-applies correctly
                userStatusMap.set(String(event.userId), event.status);
                // Apply avatar, status color, name everywhere
                applyProfileUpdate(event.userId, event.status, event.name, event.avatarUrl);
                // Re-apply presence so dots reflect updated status color
                applyPresence();
            });

        /* ── Livewire profile-saved event (fires on own tab only) ────────── */
        Livewire.on('profile-saved', (params) => {
            const status    = (params && params[0] && params[0].status)    || 'available';
            const name      = (params && params[0] && params[0].name)      || '';
            const avatarUrl = (params && params[0] && params[0].avatarUrl) || '';
            const myUserId  = document.body.dataset.userId || '';
            // Update local status map
            if (myUserId) userStatusMap.set(String(myUserId), status);
            applyProfileUpdate(myUserId, status, name, avatarUrl);
            applyPresence();
        });

        /* ── Livewire status-changed event (quick status tap) ─────────── */
        Livewire.on('status-changed', (params) => {
            const status   = (params && params[0] && params[0].status) || 'available';
            const myUserId = document.body.dataset.userId || '';
            if (myUserId) userStatusMap.set(String(myUserId), status);
            const topbarDot = document.getElementById('topbarStatusDot');
            if (topbarDot) {
                topbarDot.style.background = statusColor(status);
                topbarDot.style.boxShadow  = `0 0 6px ${statusColor(status)}`;
            }
            document.querySelectorAll('.nav-profile .presence').forEach(dot => {
                dot.style.background = statusColor(status);
            });
        });

        /* ── Connect to conversation channel ──────────────────────────────*/
        let currentConversationChannel = null;
        let currentConversationId      = null;

        function connectToConversation(conversationId) {
            if (!conversationId || String(conversationId) === String(currentConversationId)) return;

            if (currentConversationChannel) {
                Echo.leave(currentConversationChannel);
                hideTypingIndicator();
                stopTypingNow();
                console.log('[Chat] Left channel:', currentConversationChannel);
            }

            currentConversationChannel = `chat.${conversationId}`;
            currentConversationId      = conversationId;
            console.log('[Chat] Joined channel:', currentConversationChannel);

            Echo.private(currentConversationChannel)
                .listen('.message.sent', (event) => {
                    console.log('[Chat] message.sent received:', event);
                    hideTypingIndicator();
                    const incomingConvId = String(event.message.conversation_id);
                    if (incomingConvId === String(currentConversationId)) {
                        const component = getChatComponent();
                        if (component) {
                            component.call('appendMessage', event.message);
                            setTimeout(() => scrollToBottom(true), 150);
                        }
                    } else {
                        updateSidebarForNewMessage(event.message);
                    }
                })
                .listen('.message.delivered', (event) => {
                    console.log('[Chat] message.delivered:', event);
                    updateTickDOM(event.messageId, 'delivered');
                    updateSidebarTick(currentConversationId, 'delivered');
                })
                .listen('.message.read', (event) => {
                    console.log('[Chat] message.read:', event);
                    document.querySelectorAll(`[id^="tick-"]`).forEach(el => {
                        if (el.querySelector('svg')) el.innerHTML = tickSVG('read');
                    });
                    updateSidebarTick(currentConversationId, 'read');
                    const component = getChatComponent();
                    if (component) component.call('markConversationRead', event.conversationId, event.readAt);
                })
                .listen('.message.deleted', (event) => {
                    console.log('[Chat] message.deleted:', event);
                    markDeletedInDOM(event.messageId);
                    const component = getChatComponent();
                    if (component) component.call('handleRemoteDelete', event.messageId);
                })
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

        Livewire.hook('commit', ({ component, commit, succeed }) => {
            succeed(() => {
                requestAnimationFrame(() => {
                    detectAndConnectConversation();
                    attachTypingListener();
                    scrollToBottom(true);
                    if (window._applyPresence) window._applyPresence();
                    cacheSidebarPreviews();
                });
                const calls = commit?.calls ?? [];
                const wasSend = calls.some(c => c.method === 'sendMessage' || c.method === 'forwardTo');
                if (wasSend) {
                    setTimeout(() => window._checkAndMarkDelivered && window._checkAndMarkDelivered(), 100);
                }
            });
        });

        /* ── Private user channel ──────────────────────────────────────── */
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
            .listen('.message.sent', (event) => {
                console.log('[Chat] message.sent (user channel):', event);
                const incomingConvId = String(event.message.conversation_id);
                if (incomingConvId === String(currentConversationId)) return;
                updateSidebarForNewMessage(event.message);
            })
            .listen('.message.delivered', (event) => {
                console.log('[Chat] message.delivered (user channel):', event);
                updateTickDOM(event.messageId, 'delivered');
                updateSidebarTick(event.conversationId, 'delivered');
            })
            .listen('.message.read', (event) => {
                console.log('[Chat] message.read (user channel):', event);
                if (String(event.conversationId) === String(currentConversationId)) {
                    document.querySelectorAll('[id^="tick-"]').forEach(el => {
                        if (el.querySelector('svg')) el.innerHTML = tickSVG('read');
                    });
                }
                updateSidebarTick(event.conversationId, 'read');
                const component = getChatComponent();
                if (component) component.call('markConversationRead', event.conversationId, event.readAt);
            })
            .listen('.user.typing', (event) => {
                console.log('[Chat] user.typing (user channel):', event);
                if (String(event.conversationId) === String(currentConversationId)) return;
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
                    _sidebarTypingTimer = setTimeout(() => {
                        if (preview.classList.contains('conv-preview--typing')) {
                            restoreSidebarPreview(convId);
                        }
                    }, 4000);
                } else {
                    if (preview.classList.contains('conv-preview--typing')) {
                        restoreSidebarPreview(convId);
                    }
                }
            });

    }); // end livewire:init


    /* ── Sender-side typing detection ────────────────────────────────────── */
    let _localTypingTimer    = null;
    let _localKeepAliveTimer = null;
    let _isCurrentlyTyping   = false;
    const KEEPALIVE_INTERVAL = 2000;
    const STOP_DELAY         = 2000;

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

        input.addEventListener('input', () => {
            const component = getChatComponent();
            if (!component) return;
            if (!_isCurrentlyTyping) {
                _isCurrentlyTyping = true;
                component.call('broadcastTyping', true);
                _localKeepAliveTimer = setInterval(() => {
                    if (_isCurrentlyTyping) component.call('broadcastTyping', true);
                    else clearInterval(_localKeepAliveTimer);
                }, KEEPALIVE_INTERVAL);
            }
            clearTimeout(_localTypingTimer);
            _localTypingTimer = setTimeout(() => stopTypingNow(), STOP_DELAY);
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) stopTypingNow();
        });
    }

    document.addEventListener('livewire:update',    () => {
        if (window.innerWidth > 768) {
            const input = document.getElementById('messageInput');
            if (input) input.focus();
        }
        attachTypingListener();
    });
    document.addEventListener('livewire:navigated', () => attachTypingListener());
    document.addEventListener('DOMContentLoaded',   () => attachTypingListener());

    /* ── Mobile sidebar UX ───────────────────────────────────────────────── */
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

    /* ── Profile panel — outside-click + Escape dismiss ─────────────────── */
    let _profileJustToggled = false;

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('#profileBtn');
        if (btn) {
            _profileJustToggled = true;
            setTimeout(() => { _profileJustToggled = false; }, 0);
        }
    }, true);

    document.addEventListener('click', (e) => {
        const wrap = document.getElementById('profileWrap');
        if (!wrap) return;
        if (wrap.contains(e.target)) return;
        if (_profileJustToggled) return;
        Livewire.dispatch('close-profile-panel');
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') Livewire.dispatch('close-profile-panel');
    });

    /* ── Profile avatar file-reader (JS-driven, no wire:model) ──────────── */
    document.addEventListener('DOMContentLoaded', () => { attachAvatarInputListener(); });
    document.addEventListener('livewire:navigated', () => { attachAvatarInputListener(); });
    // Also re-attach after every Livewire render (panel re-renders on toggle)
    document.addEventListener('livewire:update', () => { attachAvatarInputListener(); });

    function attachAvatarInputListener() {
        const fileInput = document.getElementById('profileAvatarInput');
        if (!fileInput || fileInput._avatarListenerAttached) return;
        fileInput._avatarListenerAttached = true;

        fileInput.addEventListener('change', async () => {
            const file = fileInput.files[0];
            if (!file) return;

            const loadingEl = document.getElementById('ppUploadLoading');
            if (loadingEl) loadingEl.style.display = 'flex';

            try {
                // 1. Show local preview immediately (no network wait)
                const localUrl = URL.createObjectURL(file);
                const previewWrap = document.getElementById('avatarPreviewWrap');
                if (previewWrap) {
                    const existing = previewWrap.querySelector('img.pp-avatar-img');
                    if (existing) {
                        existing.src = localUrl;
                    } else {
                        const initials = previewWrap.querySelector('#avatarPreviewImg');
                        if (initials) {
                            const img = document.createElement('img');
                            img.src            = localUrl;
                            img.alt            = 'Preview';
                            img.className      = 'pp-avatar-img';
                            img.id             = 'avatarPreviewImg';
                            img.dataset.userId = document.body.dataset.userId || '';
                            initials.replaceWith(img);
                        }
                    }
                }

                // 2. Upload the actual file to the server via fetch (NOT Livewire)
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const formData  = new FormData();
                formData.append('avatar', file);
                formData.append('_token', csrfToken);

                const response = await fetch('/profile/avatar', {
                    method:  'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body:    formData,
                });

                if (!response.ok) {
                    throw new Error(`Upload failed: ${response.status}`);
                }

                const data = await response.json();

                // 3. Tell the Livewire component the path (a short string, not binary)
                const profilePanel = Livewire.getByName('profile.panel')[0];
                if (profilePanel && data.path) {
                    profilePanel.call('setAvatarPath', data.path);
                }

            } catch (err) {
                console.error('[Profile] Avatar upload failed:', err);
            } finally {
                if (loadingEl) loadingEl.style.display = 'none';
                // Reset file input so same file can be re-selected if needed
                fileInput.value = '';
            }
        });
    }


    /* ── Global search — blur on outside click ───────────────────────────── */
    document.addEventListener('click', (e) => {
        const wrap = document.getElementById('globalSearchWrap');
        if (!wrap) return;
        if (!wrap.contains(e.target)) {
            const input = document.getElementById('globalSearchInput');
            if (input) input.blur();
        }
    });

    /* ── Last-seen ping ──────────────────────────────────────────────────── */
    const _csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    function pingLastSeen() {
        if (!_csrfToken) return;
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
    setInterval(() => { if (document.visibilityState === 'visible') pingLastSeen(); }, 60_000);
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') pingLastSeen(); });
    window.addEventListener('beforeunload', pingLastSeen);

    /* ── Live timestamps ─────────────────────────────────────────────────── */
    function formatLiveTime(isoString) {
        const date  = new Date(isoString);
        const now   = new Date();
        const diffS = Math.floor((now - date) / 1000);
        if (diffS < 60)   return 'just now';
        if (diffS < 3600) { const m = Math.floor(diffS / 60); return `${m} min ago`; }
        const isToday = date.toDateString() === now.toDateString();
        if (isToday) return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        const yesterday = new Date(now);
        yesterday.setDate(now.getDate() - 1);
        if (date.toDateString() === yesterday.toDateString()) {
            return 'Yesterday ' + date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        }
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

    document.addEventListener('DOMContentLoaded', updateLiveTimestamps);
    setInterval(updateLiveTimestamps, 30_000);
    document.addEventListener('livewire:update', updateLiveTimestamps);

    /* ── User Profile Card ───────────────────────────────────────────────── */
    const STATUS_COLORS_UPC = {
        available: '#23e07a',
        busy:      '#ff5f72',
        away:      '#ffb547',
        dnd:       '#ff5f72',
    };

    window._closeUserProfileCard = function () {
        const overlay = document.getElementById('userProfileCardOverlay');
        if (overlay) {
            overlay.style.opacity = '0';
            setTimeout(() => { overlay.style.display = 'none'; overlay.style.opacity = ''; }, 180);
        }
    };

    window._openUserProfileCard = async function (userId) {
        if (!userId) return;

        const overlay = document.getElementById('userProfileCardOverlay');
        if (!overlay) return;

        // Show with loading state
        document.getElementById('upcName').textContent           = '…';
        document.getElementById('upcStatusLabel').textContent    = '';
        document.getElementById('upcAvatarInitials').textContent = '…';
        document.getElementById('upcAvatarImg').style.display    = 'none';
        document.getElementById('upcQuote').style.display        = 'none';
        overlay.style.display = 'flex';
        overlay.style.opacity = '0';
        requestAnimationFrame(() => { overlay.style.opacity = '1'; });

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const resp = await fetch(`/user/${userId}/profile-card`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            });
            if (!resp.ok) throw new Error('Failed to load profile');
            const user = await resp.json();

            const color = STATUS_COLORS_UPC[user.status] || STATUS_COLORS_UPC.available;

            // Avatar
            const avatarImg      = document.getElementById('upcAvatarImg');
            const avatarInitials = document.getElementById('upcAvatarInitials');
            if (user.avatar_url) {
                avatarImg.src                = user.avatar_url;
                avatarImg.alt                = user.name;
                avatarImg.style.display      = 'block';
                avatarInitials.style.display = 'none';
            } else {
                avatarImg.style.display      = 'none';
                avatarInitials.style.display = 'flex';
                avatarInitials.textContent   = user.initials;
            }

            // Status ring
            const ring = document.getElementById('upcStatusRing');
            if (ring) { ring.style.background = color; ring.style.boxShadow = `0 0 8px ${color}`; }

            // Name
            document.getElementById('upcName').textContent = user.name;

            // Status dot + label
            const dot = document.getElementById('upcStatusDot');
            dot.style.background = color;
            dot.style.boxShadow  = `0 0 5px ${color}`;
            document.getElementById('upcStatusLabel').textContent = user.status_label;
            document.getElementById('upcStatusLabel').style.color = color;

            // Quote
            const quoteEl = document.getElementById('upcQuote');
            if (user.status_quote && user.status_quote.trim()) {
                quoteEl.textContent   = user.status_quote;
                quoteEl.style.display = 'block';
            } else {
                quoteEl.style.display = 'none';
            }

        } catch (err) {
            console.error('[UserProfileCard]', err);
            document.getElementById('upcName').textContent = 'Failed to load';
        }
    };

    // Escape key closes card
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') window._closeUserProfileCard();
    });

    // Delegate click on .upc-trigger elements
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('.upc-trigger');
        if (!trigger) return;
        e.stopPropagation();
        const uid = trigger.dataset.uid;
        if (uid) window._openUserProfileCard(uid);
    });

})();
