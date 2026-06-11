# Bugfix Requirements Document

## Introduction

This document covers six interrelated bugs in the Laravel Reverb + Livewire chat application
(`app/Livewire/Chat/Index.php`, `resources/js/chat.js`, `resources/views/livewire/chat/index.blade.php`,
and related event classes). The bugs all stem from the same root themes: realtime events being scoped
too narrowly (only to the currently-open conversation channel), unnecessary full Livewire re-renders
where a direct DOM update would suffice, and a Blade template calling a PHP Collection method on a
plain PHP array. Together they cause missing typing indicators, delayed or flashing unread badges,
a fatal PHP error on page load, and redundant sequential database queries that add latency on every
message send.

---

## Bug Analysis

### Current Behavior (Defect)

**Bug 1 — Sidebar typing indicator invisible for non-open conversations**

1.1 WHEN user B is typing in a conversation that user A has not opened, THEN the system never
    delivers the `user.typing` event to user A because user A is not subscribed to
    `chat.{conversationId}` for that conversation, so the sidebar typing indicator never appears.

1.2 WHEN `showTypingIndicator()` in `chat.js` attempts to update `#conv-preview-{conversationId}`,
    THEN the system is unable to show the typing animation for non-open conversations because the
    Echo channel listener that fires `showTypingIndicator` is only attached inside
    `connectToConversation()`, which is called only when a conversation is opened.

**Bug 2 — Unread badge updated via full Livewire re-render**

1.3 WHEN a `message.sent` event arrives for a conversation that is not currently open, THEN the
    system calls `component.call('refreshSidebarForConv', id)`, which triggers `loadConversations()`
    — a full DB query and full Livewire re-render — instead of updating the existing
    `#unread-{conversationId}` DOM element in-place.

1.4 WHEN `loadConversations()` is called via `refreshSidebarForConv`, THEN the system causes a
    visible flash and perceptible latency because the entire sidebar is re-rendered even though
    only the unread badge number and preview text need to change.

**Bug 3 — Blade template calls `->count()` on plain PHP arrays**

1.5 WHEN the sidebar Blade template evaluates `@if($sentRequests && $sentRequests->count() > 0)`,
    THEN the system throws a fatal PHP error (`Call to a member function count() on array`) because
    `$sentRequests` is a plain PHP array produced by `->get()->all()`, not an Eloquent Collection.

1.6 WHEN the sidebar Blade template evaluates `@if($pendingRequests && $pendingRequests->count() > 0)`,
    THEN the system throws a fatal PHP error for the same reason.

**Bug 4 — `refreshConversationData()` re-runs mark-read/delivered queries on every realtime update**

1.7 WHEN a `conversation.updated` event fires on `user.{userId}`, THEN the system calls
    `refreshConversationData()`, which calls `selectConversation()` when a conversation is open,
    which in turn calls `markMessagesDelivered()` and `markMessagesRead()` — both issuing DB queries
    — even though the conversation state has not meaningfully changed for those messages.

1.8 WHEN `markMessagesRead()` is called redundantly, THEN the system broadcasts duplicate `MessageRead`
    events to the other participant and emits an extra `loadConversations()` DB query per event cycle.

**Bug 5 — Typing events only reach subscribed (open-conversation) recipients**

1.9 WHEN `UserTyping` broadcasts on `PrivateChannel('chat.' . $conversationId)`, THEN the system
    only delivers the event to users who have that specific conversation channel subscribed, meaning
    recipients who have not opened that conversation never receive typing notifications.

1.10 WHEN the recipient JS only calls `connectToConversation()` for the currently-open conversation,
     THEN the system leaves all other conversation channels unsubscribed, so `user.typing` events
     for non-open conversations are silently dropped.

**Bug 6 — `loadConversations()` called multiple times sequentially per user action**

1.11 WHEN `sendMessage()` executes, THEN the system calls `loadConversations()` once inside
     `sendMessage()` and again inside `markMessagesRead()` (which is called by `appendMessage()`
     on the receiver side and also on every `selectConversation()` call), causing redundant
     sequential DB queries per user action.

1.12 WHEN `appendMessage()` executes for the currently-open conversation, THEN the system calls
     `loadConversations()` a second time within the same Livewire lifecycle, adding unnecessary
     latency.

---

### Expected Behavior (Correct)

**Bug 1 / Bug 5 — Typing indicator for all participating conversations**

2.1 WHEN user B is typing in any conversation that user A participates in (open or not), THEN the
    system SHALL deliver the `user.typing` event to user A by also broadcasting on
    `PrivateChannel('user.' . $recipientUserId)`, so user A receives the event regardless of which
    conversation is currently open.

2.2 WHEN user A's `user.{userId}` private channel receives a `user.typing` event for a
    non-open conversation, THEN the system SHALL update the `#conv-preview-{conversationId}`
    sidebar element with the typing animation in-place via DOM manipulation, with no Livewire
    server round-trip.

2.3 WHEN user A opens that conversation while the typing indicator is active, THEN the system
    SHALL display the in-chat typing bubble for the correct conversation.

**Bug 2 — In-place unread badge update**

2.4 WHEN a `message.sent` event arrives for a non-open conversation, THEN the system SHALL
    increment the `#unread-{conversationId}` badge in-place via DOM manipulation and update
    `#conv-preview-{conversationId}` with the new message preview text, without triggering a
    Livewire server call or full sidebar re-render.

2.5 WHEN the `MessageSent` event payload already contains `sender_id`, `body`, `type`, and
    `conversation_id`, THEN the system SHALL use those values directly in the DOM update, making
    no additional server requests.

**Bug 3 — Correct PHP array count in Blade**

2.6 WHEN the sidebar template checks whether `$sentRequests` is non-empty, THEN the system SHALL
    use `count($sentRequests) > 0` (native PHP `count()`) instead of `$sentRequests->count()`.

2.7 WHEN the sidebar template checks whether `$pendingRequests` is non-empty, THEN the system
    SHALL use `count($pendingRequests) > 0` instead of `$pendingRequests->count()`.

**Bug 4 — Refresh without re-running mark-read/delivered**

2.8 WHEN `refreshConversationData()` is called in response to a `conversation.updated` event,
    THEN the system SHALL refresh conversation metadata (list, last message, unread counts) WITHOUT
    calling `selectConversation()`, thereby avoiding re-triggering `markMessagesDelivered()` and
    `markMessagesRead()`.

2.9 WHEN the currently-open conversation's metadata needs refreshing, THEN the system SHALL update
    `$selectedConversation` directly from the DB without re-loading messages or re-broadcasting
    delivery/read receipts.

**Bug 6 — Eliminate redundant `loadConversations()` calls**

2.10 WHEN `sendMessage()` completes, THEN the system SHALL call `loadConversations()` at most once
     per lifecycle, consolidating the redundant calls that currently occur in `sendMessage()` and
     `markMessagesRead()`.

2.11 WHEN `appendMessage()` marks a message as delivered and read, THEN the system SHALL NOT call
     `loadConversations()` inside `markMessagesRead()` for that path; instead a single
     `loadConversations()` call at the end of `appendMessage()` SHALL suffice.

---

### Unchanged Behavior (Regression Prevention)

3.1 WHEN a conversation is open and the other user types, THEN the system SHALL CONTINUE TO show
    the in-chat typing bubble via the existing `typing-indicator-row` mechanism.

3.2 WHEN a user sends a message in the currently-open conversation, THEN the system SHALL CONTINUE
    TO append the message to `$messages`, broadcast `MessageSent`, and update `last_message_at`.

3.3 WHEN a new message arrives for the currently-open conversation, THEN the system SHALL CONTINUE
    TO call `appendMessage()`, mark messages as delivered and read, and broadcast the corresponding
    receipts.

3.4 WHEN message delivery or read receipts are received, THEN the system SHALL CONTINUE TO update
    tick icons in-place via `updateTickDOM()` and `updateSidebarTick()` with no server round-trip.

3.5 WHEN a user clicks a conversation in the sidebar, THEN the system SHALL CONTINUE TO call
    `selectConversation()`, load the last 50 messages, mark them delivered and read, and switch
    the active screen to 'chat'.

3.6 WHEN a pending or sent request exists, THEN the system SHALL CONTINUE TO display it in the
    sidebar using the request banner and collapsible request list.

3.7 WHEN the presence channel reports a user as online or offline, THEN the system SHALL CONTINUE
    TO update presence dots and the chat header status text without any server call.

3.8 WHEN the forward modal is opened and a message is forwarded, THEN the system SHALL CONTINUE
    TO create the new message, broadcast `MessageSent`, and append it to the current conversation
    if applicable.

3.9 WHEN a message is soft-deleted, THEN the system SHALL CONTINUE TO broadcast `MessageDeleted`
    and replace the bubble content with the "This message was deleted" UI in-place via
    `markDeletedInDOM()`.

3.10 WHEN `loadConversations()` is called, THEN the system SHALL CONTINUE TO return conversations
     as a plain PHP array (via `->get()->all()`), never an Eloquent Collection, to prevent
     Livewire's `array_merge` crash.
