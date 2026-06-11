# Implementation Plan: Chat Realtime Fixes

## Overview

Seven tasks implement all six bug fixes. Tasks 1–3 are independent and safe to run in any order. Tasks 4 and 5 must be done together (event + caller). Tasks 6 and 7 are JS-only and independent of each other.

## Tasks

- [x] 1. Fix Blade array count() calls
  - In `resources/views/livewire/chat/index.blade.php`, replace `$sentRequests->count()` with `count($sentRequests)` and `$pendingRequests->count()` with `count($pendingRequests)`
  - **Files**: `resources/views/livewire/chat/index.blade.php`

- [x] 2. Eliminate redundant loadConversations() calls
  - Remove the `$this->loadConversations()` call from inside `markMessagesRead()` in `Index.php`
  - Add an explicit `$this->loadConversations()` call at the end of `selectConversation()` so the sidebar stays in sync after opening a chat
  - **Files**: `app/Livewire/Chat/Index.php`

- [x] 3. Fix refreshConversationData() to not re-run mark-read/delivered
  - Add private method `refreshSelectedConversation()` that only re-fetches `$selectedConversation` via `Conversation::with(['userOne','userTwo'])->find($this->selectedConversationId)` without triggering mark operations
  - Update `refreshConversationData()` to call `$this->refreshSelectedConversation()` instead of the `selectConversation()` block
  - **Files**: `app/Livewire/Chat/Index.php`

- [x] 4. Expand UserTyping to broadcast on recipient's user channel
  - Add `public int $recipientUserId` property to `UserTyping` event
  - Update constructor to accept `int $recipientUserId` as the fifth parameter
  - Add `new PrivateChannel('user.' . $this->recipientUserId)` to `broadcastOn()`
  - Add `'conversationId' => $this->conversationId` to `broadcastWith()` payload
  - **Files**: `app/Events/UserTyping.php`

- [x] 5. Pass recipient user ID when broadcasting typing in Livewire component
  - In `broadcastTyping()`, resolve the other participant's ID from `$this->selectedConversation` (compare `user_one_id` to `auth()->id()`, take the other)
  - Pass the resolved ID as the fifth argument to the `UserTyping` constructor
  - **Files**: `app/Livewire/Chat/Index.php`

- [x] 6. Add user.typing listener on private user channel in JS
  - In `chat.js`, inside the `Echo.private('user.${userId}')` chain, add a `.listen('.user.typing', ...)` handler
  - Guard: if `String(event.conversationId) === String(currentConversationId)`, return early (already handled by the conversation channel)
  - When `event.isTyping` is true: call `showTypingIndicator(event.userName, event.conversationId)` and set `_remoteTypingTimer` to auto-hide after 4000 ms
  - When `event.isTyping` is false: call `hideTypingIndicator()`
  - **Files**: `resources/js/chat.js`

- [x] 7. Replace refreshSidebarForConv Livewire call with pure DOM update in JS
  - Add `updateSidebarForNewMessage(message)` function in `chat.js` that:
    1. Updates `#conv-preview-{conversationId}` with new message preview text (type image → '📷 Image', file → '📎 File', text → first 30 chars of body), skipping if the element currently has `conv-preview--typing` class
    2. Increments `#unread-{conversationId}` badge (parse current text as int, add 1, cap at '99+', set `style.display = ''`)
  - In the `.listen('.message.sent', ...)` handler, for non-open conversations replace `component.call('refreshSidebarForConv', parseInt(incomingConvId, 10))` with `updateSidebarForNewMessage(event.message)`
  - **Files**: `resources/js/chat.js`

## Task Dependency Graph

```json
{
  "waves": [
    { "wave": 1, "tasks": [1, 2, 3, 4, 7] },
    { "wave": 2, "tasks": [5, 6] }
  ]
}
```

## Notes

- Tasks 4 and 5 must be deployed together; deploying Task 4 alone will cause a PHP error in `broadcastTyping()` until Task 5 is applied.
- Task 2 removes the only `loadConversations()` call inside `markMessagesRead()`. Verify that every code path that previously relied on that implicit call now has an explicit one (primarily `selectConversation()`, which is covered by Task 2's added call).
- Task 7 removes the `component.call('refreshSidebarForConv', ...)` JS call. The server-side `refreshSidebarForConv` PHP method can be left in place or cleaned up separately — it is no longer invoked from JS after this task.
- After all tasks are applied, run `npm run build` (or `vite build`) to bundle the updated `chat.js`.
