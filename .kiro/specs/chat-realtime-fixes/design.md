# Chat Realtime Fixes — Technical Design

## Overview

Six bugs affect the Laravel Reverb + Livewire chat app. The fixes fall into three categories:
1. Expand the `UserTyping` broadcast channel so recipients receive events for non-open conversations
2. Replace Livewire server calls with pure DOM updates for unread badges and sidebar preview text
3. Eliminate redundant PHP array/Collection misuse and redundant DB queries

## Glossary

- **Livewire**: Full-stack PHP component framework that syncs server state to the browser via AJAX.
- **Laravel Reverb**: First-party WebSocket server for Laravel that powers real-time broadcasting.
- **Echo**: Laravel Echo JS client that subscribes to Reverb channels.
- **PrivateChannel**: A broadcast channel authenticated via Laravel's channel routes.
- **`UserTyping`**: The broadcastable event fired when a user starts or stops typing.
- **`MessageSent`**: The broadcastable event fired when a message is sent.
- **`conversationId`**: The integer primary key of a `Conversation` record.
- **`recipientUserId`**: The user ID of the other participant in the conversation.
- **Sidebar preview**: The `#conv-preview-{id}` element showing the last message snippet.
- **Unread badge**: The `#unread-{id}` element showing the count of unread messages.

## Bug Details

### Bug 1 & 5 — Sidebar typing indicator invisible for non-open conversations

`UserTyping` broadcasts only on `PrivateChannel('chat.{conversationId}')`. The JS client subscribes to that channel only when the conversation is opened via `connectToConversation()`. If the recipient has not opened the conversation, they are not subscribed to that channel and never receive the `user.typing` event.

Additionally, the `broadcastWith()` payload does not include `conversationId`, so even if a listener existed on another channel it could not determine which sidebar row to update.

### Bug 2 — Unread badge and preview text updated via full Livewire re-render

When `message.sent` arrives on `Echo.private('chat.{conversationId}')` for a conversation that is not currently open, the JS handler calls `component.call('refreshSidebarForConv', id)`. This triggers `loadConversations()` on the server — a full DB query — followed by a full Livewire diff-and-patch of the sidebar. The result is a visible flash and perceptible latency even though only the badge number and preview text need to change.

### Bug 3 — Blade template calls `->count()` on plain PHP arrays

`$sentRequests` and `$pendingRequests` are populated with `->get()->all()`, producing plain PHP arrays. The Blade template calls `$sentRequests->count()` and `$pendingRequests->count()`, which are Eloquent Collection methods. PHP throws a fatal error: `Call to a member function count() on array`.

### Bug 4 — `refreshConversationData()` re-runs mark-read/delivered on every realtime update

`refreshConversationData()` calls `selectConversation()` when a conversation is open. `selectConversation()` calls `markMessagesDelivered()` and `markMessagesRead()`. These methods issue DB queries and broadcast `MessageDelivered` / `MessageRead` events on every `conversation.updated` WebSocket event, even when no messages have changed delivery state.

### Bug 6 — `loadConversations()` called multiple times per user action

`markMessagesRead()` unconditionally calls `$this->loadConversations()` at its end. This creates duplicate sequential queries:
- `sendMessage()` calls `loadConversations()` then calls `broadcast(new MessageSent(...))`. On the receiver side, `appendMessage()` → `markMessagesRead()` calls it again.
- `selectConversation()` calls `markMessagesDelivered()` then `markMessagesRead()`, so `loadConversations()` fires once inside `markMessagesRead()` and again after `selectConversation()` returns (in callers such as `openChat()`).

## Expected Behavior

### Bugs 1 & 5 — Typing events reach all participants

- `UserTyping` broadcasts on both `PrivateChannel('chat.{conversationId}')` and `PrivateChannel('user.{recipientUserId}')`.
- `broadcastWith()` includes `conversationId` so the JS handler knows which sidebar row to update.
- The `Echo.private('user.{userId}')` block in `chat.js` listens for `.user.typing`. When the event's `conversationId` differs from the currently open conversation, it calls `showTypingIndicator()` on the sidebar row and sets an auto-hide timer. When it matches the open conversation, the event is skipped (already handled by the conversation channel).

### Bug 2 — In-place DOM update

- When `message.sent` arrives for a non-open conversation, JS calls a new `updateSidebarForNewMessage(message)` function that directly manipulates `#conv-preview-{id}` and `#unread-{id}` without any Livewire server call.
- No flash, no re-render, no DB query.

### Bug 3 — Native PHP `count()`

- The Blade template uses `count($sentRequests) > 0` and `count($pendingRequests) > 0`, which work correctly on plain arrays.

### Bug 4 — Lightweight conversation refresh

- `refreshConversationData()` calls a new private `refreshSelectedConversation()` method that re-fetches `$selectedConversation` from the DB without calling `selectConversation()`, thus avoiding `markMessagesDelivered()` and `markMessagesRead()`.

### Bug 6 — Single `loadConversations()` per lifecycle

- `markMessagesRead()` no longer calls `loadConversations()`. Callers that need a fresh list call it explicitly. `selectConversation()` gains an explicit `loadConversations()` call after the mark operations if not already present.

## Hypothesized Root Cause

All six bugs share the same root theme: **the initial implementation scoped realtime subscriptions and state refreshes to the currently-open conversation only**, without accounting for events arriving for other conversations. The secondary theme is **over-reliance on full Livewire re-renders and redundant DB queries** where targeted DOM updates and query deduplication would suffice.

Specifically:
- `UserTyping` was designed assuming the recipient always has the conversation open — a valid assumption for in-chat bubbles but wrong for sidebar indicators.
- `refreshSidebarForConv` was a shortcut that reused the existing `loadConversations()` mechanism instead of a targeted DOM update.
- The Blade array-count bug was introduced when the public state was intentionally changed from Collections to plain arrays (to avoid Livewire's `array_merge` crash) but the Blade template was not updated in sync.
- `markMessagesRead()` calling `loadConversations()` was a defensive measure to keep the sidebar in sync, but it became redundant once callers already ensured a refresh.

## Fix Implementation

### Fix 1 & 5: `app/Events/UserTyping.php`

Add a `public int $recipientUserId` property. Update the constructor signature:

```php
public function __construct(
    int $conversationId,
    int $userId,
    string $userName,
    bool $isTyping,
    int $recipientUserId,
)
```

Update `broadcastOn()`:

```php
public function broadcastOn(): array
{
    return [
        new PrivateChannel('chat.' . $this->conversationId),
        new PrivateChannel('user.' . $this->recipientUserId),
    ];
}
```

Update `broadcastWith()` to include `conversationId`:

```php
public function broadcastWith(): array
{
    return [
        'conversationId' => $this->conversationId,
        'userId'         => $this->userId,
        'userName'       => $this->userName,
        'isTyping'       => $this->isTyping,
    ];
}
```

### Fix 1 & 5: `app/Livewire/Chat/Index.php` — `broadcastTyping()`

Resolve the other participant's ID from `$this->selectedConversation` and pass it:

```php
public function broadcastTyping(bool $isTyping): void
{
    if (! $this->selectedConversation) return;

    $recipientId = $this->selectedConversation->user_one_id === auth()->id()
        ? $this->selectedConversation->user_two_id
        : $this->selectedConversation->user_one_id;

    broadcast(new UserTyping(
        $this->selectedConversation->id,
        auth()->id(),
        auth()->user()->name,
        $isTyping,
        $recipientId,
    ))->toOthers();
}
```

### Fix 2: `resources/js/chat.js` — `updateSidebarForNewMessage()`

New function added before the `livewire:init` listener:

```js
function updateSidebarForNewMessage(message) {
    const convId  = message.conversation_id;
    const preview = document.getElementById(`conv-preview-${convId}`);
    const badge   = document.getElementById(`unread-${convId}`);

    // Update preview text
    if (preview && !preview.classList.contains('conv-preview--typing')) {
        let text = '';
        if (message.type === 'image')      text = '📷 Image';
        else if (message.type === 'file')  text = '📎 File';
        else                               text = (message.body || '').substring(0, 30);
        preview.textContent = text;
    }

    // Increment unread badge
    if (badge) {
        const current = parseInt(badge.textContent, 10) || 0;
        const next    = current + 1;
        badge.textContent    = next > 99 ? '99+' : String(next);
        badge.style.display  = '';
    }
}
```

In the `.listen('.message.sent', ...)` handler, replace:

```js
component.call('refreshSidebarForConv', parseInt(incomingConvId, 10));
```

with:

```js
updateSidebarForNewMessage(event.message);
```

### Fix 3: `resources/views/livewire/chat/index.blade.php`

Replace:

```blade
@if($sentRequests && $sentRequests->count() > 0)
```

with:

```blade
@if($sentRequests && count($sentRequests) > 0)
```

Replace both occurrences of `$sentRequests->count()` (condition and badge display) and both occurrences of `$pendingRequests->count()`.

### Fix 4: `app/Livewire/Chat/Index.php` — `refreshConversationData()`

Add private helper:

```php
private function refreshSelectedConversation(): void
{
    if (! $this->selectedConversationId) return;
    $this->selectedConversation = Conversation::with(['userOne', 'userTwo'])
        ->find($this->selectedConversationId);
}
```

Update `refreshConversationData()`:

```php
public function refreshConversationData(): void
{
    $this->loadConversations();
    $this->loadPendingRequests();
    $this->loadSentRequests();
    $this->clearSentRequestsScreenIfEmpty();
    $this->refreshSelectedConversation();
}
```

### Fix 6: `app/Livewire/Chat/Index.php` — `markMessagesRead()`

Remove the trailing `$this->loadConversations()` call from `markMessagesRead()`. Add an explicit `$this->loadConversations()` call at the end of `selectConversation()` to keep sidebar in sync after opening a chat:

```php
public function selectConversation(int $conversationId): void
{
    // ... existing code ...
    $this->markMessagesDelivered($conversationId);
    $this->markMessagesRead($conversationId);
    $this->loadConversations(); // explicit — no longer inside markMessagesRead
}
```

### Fix 1 & 5: `resources/js/chat.js` — `user.typing` listener

Inside the `Echo.private('user.${userId}')` chain, add:

```js
.listen('.user.typing', (event) => {
    console.log('[Chat] user.typing (user channel):', event);

    // Skip if already handled by the open conversation channel
    if (String(event.conversationId) === String(currentConversationId)) return;

    clearTimeout(_remoteTypingTimer);
    if (event.isTyping) {
        showTypingIndicator(event.userName, event.conversationId);
        _remoteTypingTimer = setTimeout(() => hideTypingIndicator(), 4000);
    } else {
        hideTypingIndicator();
    }
})
```

## Correctness Properties

### Property 1: Typing events reach all participants
For any conversation where user A participates, when user B calls `broadcastTyping(true)`, user A receives a `.user.typing` event on their `user.{A.id}` private channel regardless of which conversation A has open.

**Validates: Requirements 2.1**

### Property 2: No duplicate typing handling
When user A has the conversation open, the `.user.typing` event arriving on the user channel is ignored (guarded by `conversationId === currentConversationId`), so the typing indicator is not triggered twice for the same conversation.

**Validates: Requirements 2.2, 3.1**

### Property 3: Unread badge accuracy
After `updateSidebarForNewMessage()` runs for a given `conversationId`, the `#unread-{conversationId}` element's numeric text is exactly one greater than its previous value, and the element is visible (`display` is not `none`).

**Validates: Requirements 2.4, 2.5**

### Property 4: No fatal Blade error
The Blade template renders without PHP errors when `$sentRequests` and `$pendingRequests` are plain PHP arrays of any length (0 to N).

**Validates: Requirements 2.6, 2.7**

### Property 5: No redundant mark operations on conversation refresh
When `refreshConversationData()` is called in response to a `conversation.updated` event, `markMessagesDelivered()` and `markMessagesRead()` are NOT called; only `$selectedConversation` is refreshed via a single `find()` query.

**Validates: Requirements 2.8, 2.9**

### Property 6: Single loadConversations() per selectConversation() lifecycle
A call to `selectConversation()` results in exactly one `loadConversations()` DB query, not two or more.

**Validates: Requirements 2.10, 2.11**

## Testing Strategy

- **Bug 3**: Render the Blade template with PHP arrays of length 0 and 1 and verify no exception.
- **Bug 1 & 5**: Assert that `UserTyping::broadcastOn()` returns two channels when `$recipientUserId` differs from `$userId`.
- **Bug 4**: Call `refreshConversationData()` on a component with an open conversation and assert `markMessagesRead` is NOT called (spy/mock).
- **Bug 6**: Call `selectConversation()` and assert `loadConversations()` is called exactly once.
- **Bug 2**: In a browser test, receive a `message.sent` WebSocket event for a non-open conversation and assert the badge text increments without a Livewire network request.
- **Regression**: Send a message in the open conversation and verify `appendMessage()` still appends to `$messages` and `loadConversations()` is called once at the end.
