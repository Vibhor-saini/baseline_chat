<div wire:click="selectConversation({{ $conversation->id }})"
     class="conv-item {{ ($selectedConversation && $selectedConversation->id === $conversation->id) ? 'conv-active' : '' }}"
     role="listitem" tabindex="0"
     aria-label="Conversation with {{ $other->name }}"
     data-user-id="{{ $other->id }}"
     data-user-avatar-gradient="{{ \App\Helpers\AvatarColor::gradient($other->id) }}"
     wire:key="conv-{{ $conversation->id }}">
  <div class="conv-avatar-wrap" wire:ignore>
      <button type="button"
              class="conv-avatar-btn"
              onclick="event.stopPropagation(); window._openUserProfileCard('{{ $other->id }}')"
              title="View {{ $other->name }}'s profile"
              aria-label="View {{ $other->name }}'s profile">
        @if($other->profile_image)
          <img src="{{ Storage::url($other->profile_image) }}"
               alt="{{ $other->name }}"
               class="conv-avatar conv-avatar--img"
               data-user-id="{{ $other->id }}"
               data-initials="{{ strtoupper(substr($other->name,0,1)) }}"
               onerror="this.onerror=null;this.style.display='none';var s=this.nextElementSibling;if(s){s.style.display='flex';}">
          <div class="conv-avatar" data-user-id="{{ $other->id }}"
               style="display:none;background:{{ \App\Helpers\AvatarColor::gradient($other->id) }}">{{ strtoupper(substr($other->name,0,1)) }}</div>
          @else
          <div class="conv-avatar" data-user-id="{{ $other->id }}"
               style="background:{{ \App\Helpers\AvatarColor::gradient($other->id) }}">{{ strtoupper(substr($other->name,0,1)) }}</div>
        @endif
      </button>
    <span class="presence-dot"
          data-presence-uid="{{ $other->id }}"
          data-user-status="{{ $other->status instanceof \App\Enums\UserStatus ? $other->status->value : 'available' }}"
          aria-hidden="true"></span>
  </div>
  <div class="conv-info">
    <div class="conv-info-top">
      <span class="conv-name">{{ $other->name }}</span>
      <div class="conv-top-right">
        <span class="conv-time">{{ $conversation->last_message_at?->diffForHumans(short:true) ?? '' }}</span>
        <button type="button" 
                class="conv-menu-btn" 
                onclick="event.stopPropagation(); window._toggleConvMenu({{ $conversation->id }})"
                aria-label="Conversation options"
                title="More options">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/>
          </svg>
        </button>
      </div>
    </div>
    <div class="conv-bottom-row">
      @php
        // For forwarded messages: strip the original body suffix from legacy merged data
        $latestPreviewText = '';
        if ($latest && !$latest->deleted_at) {
            if ($latest->type !== 'text') {
                $latestPreviewText = $latest->type === 'image' ? '📷 Image' : '📎 File';
            } elseif ($latest->forwarded_from_id && $latest->forwardedFrom) {
                $fwdBody = $latest->forwardedFrom->body ?? '';
                $cleaned = $fwdBody
                    ? trim(str_replace(["\n" . $fwdBody, $fwdBody], '', $latest->body ?? ''))
                    : trim($latest->body ?? '');
                $latestPreviewText = $cleaned ?: '↩ Forwarded message';
            } else {
                $latestPreviewText = $latest->body ?? '';
            }
        }
        $latestPreviewShort = \Illuminate\Support\Str::limit($latestPreviewText, 30);
      @endphp
      <p class="conv-preview"
         id="conv-preview-{{ $conversation->id }}"
         data-last-preview="@if($latest){{ $latest->deleted_at ? 'This message was deleted' : $latestPreviewShort }}@endif">
        @php
          $convDraft = $draftBodies[(string) $conversation->id] ?? null;
        @endphp
        @if($convDraft && $selectedConversationId !== $conversation->id)
          {{-- Draft label — only shown for non-active conversations --}}
          <span class="draft-label">Draft:</span>
          <span class="draft-text">{{ \Illuminate\Support\Str::limit($convDraft, 28) }}</span>
        @elseif($latest)
          @if($isMineLatest)
            @include('livewire.chat.partials.tick', ['status' => $latest->deliveryStatus()])
            <span><span class="you-label">You:</span> {{ $latest->deleted_at ? 'This message was deleted' : $latestPreviewShort }}</span>
          @else
            <span>{{ $latest->deleted_at ? 'This message was deleted' : $latestPreviewShort }}</span>
          @endif
        @else
          <span class="conv-preview-placeholder">Click to open chat</span>
        @endif
      </p>
      @php
        $isActive     = $selectedConversation && $selectedConversation->id === $conversation->id;
        $showUnread   = !$isActive && $unread > 0;
      @endphp
      @if($showUnread)
      <span class="unread-badge"
            wire:key="badge-{{ $conversation->id }}"
            id="unread-{{ $conversation->id }}">
        {{ $unread > 99 ? '99+' : $unread }}
      </span>
      @endif
    </div>
  </div>

  {{-- Conversation Context Menu --}}
  <div class="conv-menu" id="conv-menu-{{ $conversation->id }}" style="display:none">
    <button type="button" 
            class="conv-menu-item"
            onclick="event.stopPropagation(); window._openInNewWindow({{ $conversation->id }}, '{{ $other->name }}', {{ $other->id }})"
            title="Open chat in a separate window">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
        <polyline points="15 3 21 3 21 9"/>
        <line x1="10" y1="14" x2="21" y2="3"/>
      </svg>
      <span>Open in New Window</span>
    </button>
    
    @if(in_array($conversation->id, $favoriteConvIds ?? [], true))
    <button type="button" 
            class="conv-menu-item"
            wire:click="toggleFavorite({{ $conversation->id }})"
            onclick="event.stopPropagation();"
            title="Remove from favorites">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2">
        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
      </svg>
      <span>Unfavourite</span>
    </button>
    @else
    <button type="button" 
            class="conv-menu-item"
            wire:click="toggleFavorite({{ $conversation->id }})"
            onclick="event.stopPropagation();"
            title="Add to favorites">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
      </svg>
      <span>Favourite</span>
    </button>
    @endif
    
    <button type="button" 
            class="conv-menu-item"
            wire:click="markAsUnread({{ $conversation->id }})"
            onclick="event.stopPropagation();"
            title="Mark this conversation as unread">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
        <polyline points="22,6 12,13 2,6"/>
      </svg>
      <span>Mark as Unread</span>
    </button>
    <div class="conv-menu-divider"></div>
    <button type="button" 
            class="conv-menu-item conv-menu-item--danger"
            onclick="event.stopPropagation(); window._confirmDeleteConversation({{ $conversation->id }}, '{{ $other->name }}')"
            title="Delete this conversation">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="3 6 5 6 21 6"/>
        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
        <line x1="10" y1="11" x2="10" y2="17"/>
        <line x1="14" y1="11" x2="14" y2="17"/>
      </svg>
      <span>Delete Conversation</span>
    </button>
  </div>
</div>
