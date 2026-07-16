# 💬 Baseline Chat

A real-time, one-on-one messaging web app built with **Laravel 12**, **Livewire**, and **Laravel Reverb** (self-hosted WebSocket server). It brings WhatsApp-style features — delivery ticks, typing indicators, presence status, reactions, replies, and forwarding — into a self-hosted, open-source package.

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![Livewire](https://img.shields.io/badge/Livewire-4.3-4E56A6)
![Reverb](https://img.shields.io/badge/Laravel%20Reverb-1.0-FF2D20)
![License](https://img.shields.io/badge/License-MIT-green)

---

## 📌 Problem

A team was coordinating project updates over email threads, which caused communication delays, context loss, and version confusion. They needed a lightweight internal messaging tool with real-time delivery and message persistence — without the overhead of a third-party SaaS chat tool.

## ✅ Solution

Built a Laravel-based chat application using **Laravel Reverb** as the WebSocket server. It includes:

- Channel-based messaging with live user presence
- Real-time sidebar updates the moment a new message arrives
- Persistent message storage in MySQL, with pagination for chat history
- Authentication-gated private channels using Laravel Echo on the frontend

## 🧩 Technical Challenges

- **Concurrent WebSocket connections causing sidebar state desync** — resolved by broadcasting channel-level events that all connected clients subscribe to, instead of relying on per-client local state.
- **Message ordering guarantees** — used database-level auto-increment IDs as the canonical message order, instead of trusting client-generated timestamps (which can drift or arrive out of order).
- **Presence channel scaling** — optimized the presence event payload to avoid broadcasting the full user list on every join/leave event, using delta updates instead.

## 📈 Business Impact

| Metric | Result |
|---|---|
| Message delivery | Real-time, via WebSockets |
| Chat history | Fully persisted and paginated |
| Communication delay | Reduced to zero |
| Presence visibility | Live "who's online" indicators |

**Result:** Team members now communicate in real time with zero message delay. Presence indicators show who's online, message history is fully searchable, and the system handles concurrent connections reliably. The project is open-source on GitHub.

---

---

## 📸 Chat Screenshots

<img width="1365" height="645" alt="image" src="https://github.com/user-attachments/assets/5d1e5464-a30a-41af-ac42-667769af0d75" />
<img width="1364" height="641" alt="image" src="https://github.com/user-attachments/assets/da4539e6-6be1-4f33-899a-f28c699cd174" />
<img width="1365" height="637" alt="image" src="https://github.com/user-attachments/assets/fee96dea-05df-421d-b3bb-67a39cb9f06c" />
<img width="1364" height="638" alt="image" src="https://github.com/user-attachments/assets/49063d43-6178-48f5-9d12-4fe5da75207c" />
<img width="1365" height="637" alt="image" src="https://github.com/user-attachments/assets/a3773b94-d081-4087-88ea-e3ee25421017" />


---

## 📸 Code Screenshots

<img width="1365" height="694" alt="image" src="https://github.com/user-attachments/assets/77a5729b-db3e-48b0-a0ee-bde24ad1602b" />
<img width="1365" height="693" alt="image" src="https://github.com/user-attachments/assets/3fb4b381-e038-467c-950a-0f448fa1dd5f" />
<img width="1365" height="694" alt="image" src="https://github.com/user-attachments/assets/cabe2979-8fcb-49e6-a1e0-5c4d0c631423" />
<img width="1365" height="694" alt="image" src="https://github.com/user-attachments/assets/e5229ae4-f2d4-4e3a-bc82-975d56aa8547" />

---


## ✨ Features

### Messaging
- Send text, image, and file messages (PDF, DOC, DOCX, XLS, XLSX)
- Reply to a message — quoted block in the bubble, click to jump to the original (works even if the original was deleted)
- Forward a message — modal with recipient search and optional extra text
- Edit your own text messages inline, with an "edited" label
- Soft-delete your own messages — replaced with "This message was deleted," file removed from storage
- Emoji reactions — toggle add/remove per message, grouped pill display with counts, 4 quick-react buttons + full emoji picker
- Draft messages saved per conversation — "Draft:" label shown in the sidebar
- Auto-linked URLs in messages, with a click-to-open link menu
- Image lightbox with full-screen view, sender name, and timestamp
- Date separators (Today / Yesterday / date) and grouping of consecutive messages within 2 minutes
- Auto scroll-to-bottom on send, receive, and page load

### Delivery & Read Receipts (WhatsApp-style)
- Single grey tick — sent
- Double grey tick — delivered
- Double blue tick — read
- Ticks shown in both the chat bubble and the sidebar preview
- Bulk-marks messages as delivered when the recipient comes online
- Updates live via WebSocket, no page reload needed

### Typing Indicators
- Animated three-dot typing bubble with sender's avatar
- Also shown in the sidebar preview ("X is typing…"), even when that chat isn't open
- Auto-stops after 2 seconds of inactivity

### Conversations & Contact Requests
- One-on-one conversations with a request → accept/reject flow
- Admin conversations bypass the request system (auto-accepted)
- Incoming requests list and outgoing pending requests banner
- Context-aware search results: Open Chat / Send Request / Request Sent / Accept
- Per-conversation unread badge (capped at 99+)
- Conversations sorted by most recent activity

### User Profile
- Slide-out profile panel — edit name, status quote, avatar, and status
- Status modes: Available / Busy / Away / Do Not Disturb, with color-coded dots
- Clickable profile card for other users
- "Online" / "Last seen X ago" / "Never seen" display
- Profile changes (avatar, name, status) broadcast live across all open tabs

### Presence / Online Status
- Real-time presence channel showing who's currently online
- Status-colored dots synced across sidebar, chat header, and nav rail
- Database fallback check (last seen within 2 minutes = online)
- Presence ping on page load, tab focus, and before unload

### Search
- Debounced global search (users by name or email)
- Top 8 results with avatar, name, email, and a contextual action button

### Admin Panel
- Dashboard with total users, admin count, and member count
- Full user CRUD (create, edit, delete, search, pagination)
- Auto-creates a welcome conversation for newly created users
- Admin badge shown across the UI

### Authentication
- Register, login, logout (Laravel Breeze)
- Email verification, password reset, confirm password, change password
- Account deletion with password confirmation

---

## 📡 Real-Time Events

| Event | Channel | Payload |
|---|---|---|
| `MessageSent` | `chat.{id}` + `user.{recipientId}` | Full message object, including reply/forward data |
| `MessageDelivered` | `chat.{id}` + `user.{senderId}` | messageId, conversationId, deliveredAt |
| `MessageRead` | `chat.{id}` + `user.{senderId}` | conversationId, readByUserId, readAt |
| `MessageDeleted` | `chat.{id}` | messageId |
| `ConversationUpdated` | `user.{id}` | conversationId, userId, status |
| `PendingRequestUpdated` | `user.{id}` | userId |
| `UserTyping` | `chat.{id}` + `user.{recipientId}` | conversationId, userId, userName, avatarUrl, isTyping |
| `UserProfileUpdated` | `profile-updates` (public) | userId, avatarUrl, status, name |

All events are broadcast synchronously (`ShouldBroadcastNow`) for instant delivery.

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.2+ |
| Framework | Laravel 12 |
| Reactive UI | Livewire 4.3 |
| Real-time WebSocket | Laravel Reverb 1.0 (self-hosted, Pusher protocol) |
| Auth Scaffolding | Laravel Breeze |
| Frontend JS | Alpine.js 3, vanilla JS |
| CSS Framework | Tailwind CSS v3 |
| Build Tool | Vite 7 |
| WebSocket Client | Laravel Echo 2 + Pusher.js 8.5 |
| HTTP Client | Axios 1.11 |
| Cache / Queue | Redis (Predis 2.0) |
| Storage | Laravel public disk (local) |
| Database | MySQL |
| Testing | PHPUnit 11 |

### Broadcast Channels
- `presence.chat` — who's online
- `private chat.{id}` — per-conversation messages, typing, and ticks
- `private user.{id}` — cross-conversation delivery/read receipts and notifications
- `profile-updates` (public) — live avatar and status sync

---

## 🗄️ Database Schema

- **users** — id, name, email, email_verified_at, password, is_admin, profile_image, status_quote, status, status_manually_set, last_seen, timestamps
- **conversations** — id, user_one_id, user_two_id, status (pending/accepted), last_message_at, timestamps
- **messages** — id, conversation_id, sender_id, body, type (text/image/file), file_path, is_read, delivered_at, read_at, edited_at, forwarded_from_id, reply_to_id, deleted_at (soft delete), timestamps
- **message_reactions** — id, message_id, user_id, emoji, timestamps (unique on message_id + user_id + emoji)

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & npm
- MySQL
- Redis (optional, for Reverb scaling)

### Installation

```bash
# Clone the repo
git clone https://github.com/your-username/baseline-chat.git
cd baseline-chat

# Install PHP dependencies
composer install

# Install JS dependencies
npm install

# Copy env file and set your DB/Reverb credentials
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Build frontend assets
npm run build
```

### Running the app

```bash
# Start the Laravel server
php artisan serve

# Start the Reverb WebSocket server
php artisan reverb:start

# Start Vite (for development)
npm run dev
```

Or, if you have the `concurrently` script set up:

```bash
composer run dev
```

---

## 📄 License

This project is open-source and available under the [MIT License](LICENSE).

---

## 🙋 Contact

Have questions or suggestions? Feel free to open an issue or reach out.
