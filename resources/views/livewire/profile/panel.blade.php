{{-- resources/views/livewire/profile/panel.blade.php --}}
{{--
    Teams-style floating profile card.
    • Alpine REMOVED — status state is owned entirely by the Livewire $status
      property. JS (chat.js) applies status classes post-render via the
      livewire:commit hook so there is no Alpine ↔ JS class-ownership conflict.
    • Status buttons highlight via the Livewire $status property (class set in PHP).
    • Instant visual feedback on click is achieved by JS calling applyPanelStatus()
      before the Livewire round-trip completes.
--}}
<div class="profile-panel {{ $isOpen ? 'profile-panel--open' : '' }}"
     id="profileDropdown"
     role="dialog"
     aria-modal="true"
     aria-label="Profile settings"
     wire:key="profile-panel"
     data-current-status="{{ $status }}">

    {{-- ── Panel Header: avatar + name + close ────────────────────── --}}
    <div class="pp-header">
        {{-- Avatar upload wrapper (JS-driven, no wire:model on file input) --}}
        <div class="pp-avatar-section">
            <label class="pp-avatar-label" for="profileAvatarInput"
                   title="Change profile picture" aria-label="Upload profile picture">
                <div class="pp-avatar-wrap" id="avatarPreviewWrap" wire:ignore>
                    @if(auth()->user()->profile_image)
                        <img src="{{ Storage::url(auth()->user()->profile_image) }}"
                             alt="{{ auth()->user()->name }}"
                             class="pp-avatar-img"
                             id="avatarPreviewImg"
                             data-user-id="{{ auth()->id() }}">
                    @else
                        <div class="pp-avatar-initials"
                             id="avatarPreviewImg"
                             data-user-id="{{ auth()->id() }}"
                             aria-hidden="true">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                    @endif
                </div>
                {{-- Status ring — class set by JS after each render --}}
                <span class="pp-avatar-status-ring pp-status-ring--{{ $status }}"
                      id="ppAvatarStatusRing"
                      aria-hidden="true"></span>
                <div class="pp-avatar-overlay" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                        <circle cx="12" cy="13" r="4"/>
                    </svg>
                </div>
            </label>

            {{-- Hidden file input --}}
            <input type="file"
                   id="profileAvatarInput"
                   accept="image/jpeg,image/png,image/webp"
                   class="file-input-hidden"
                   aria-label="Select profile picture"
                   onclick="event.stopPropagation()">

            {{-- Upload loading spinner overlay --}}
            <div class="pp-upload-loading" id="ppUploadLoading" style="display:none" aria-live="polite">
                <svg class="pp-spinner" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="12" cy="12" r="10" stroke-opacity=".2"/>
                    <path d="M12 2a10 10 0 0 1 10 10" stroke-opacity="1"/>
                </svg>
            </div>
        </div>

        <div class="pp-header-info">
            <div class="pp-header-name">{{ auth()->user()->name }}</div>
            <div class="pp-header-meta">
                {{-- Class set by PHP + kept in sync by JS after render --}}
                <span class="pp-status-dot pp-status-dot--{{ $status }}"
                      id="ppPanelStatusDot"
                      aria-hidden="true"></span>
                <span class="pp-status-label" id="ppPanelStatusLabel">
                    @php
                        $labels = ['available' => 'Available', 'busy' => 'Busy', 'away' => 'Away', 'dnd' => 'Do Not Disturb'];
                        echo $labels[$status] ?? 'Available';
                    @endphp
                </span>
                @if(auth()->user()->is_admin)
                    <span class="badge-admin">Admin</span>
                @endif
            </div>
        </div>

        <button type="button" class="pp-close-btn"
                wire:click="closePanel"
                aria-label="Close profile panel">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    {{-- ── Status Quick Picker ──────────────────────────────────────── --}}
    <div class="pp-section pp-section--status">
        <div class="pp-section-label">Status</div>
        <div class="pp-status-grid" role="group" aria-label="Set your status">
            @foreach([
                ['value' => 'available', 'label' => 'Available',      'icon_class' => 'pp-sicon--available'],
                ['value' => 'busy',      'label' => 'Busy',           'icon_class' => 'pp-sicon--busy'],
                ['value' => 'away',      'label' => 'Away',           'icon_class' => 'pp-sicon--away'],
                ['value' => 'dnd',       'label' => 'Do Not Disturb', 'icon_class' => 'pp-sicon--dnd'],
            ] as $s)
            <button type="button"
                    wire:click="changeStatus('{{ $s['value'] }}')"
                    onclick="window._applyPanelStatus && window._applyPanelStatus('{{ $s['value'] }}')"
                    wire:loading.attr="disabled"
                    wire:loading.class="pp-status-btn--loading"
                    wire:target="changeStatus('{{ $s['value'] }}')"
                    class="pp-status-btn {{ $status === $s['value'] ? 'pp-status-btn--active' : '' }}"
                    aria-pressed="{{ $status === $s['value'] ? 'true' : 'false' }}"
                    data-status-value="{{ $s['value'] }}"
                    title="{{ $s['label'] }}">
                <span class="pp-sicon {{ $s['icon_class'] }}" aria-hidden="true"></span>
                <span class="pp-status-btn-label">{{ $s['label'] }}</span>
                @if($isChangingStatus && $status === $s['value'])
                    <svg class="pp-spinner pp-spinner--xs" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" stroke-opacity=".2"/>
                        <path d="M12 2a10 10 0 0 1 10 10"/>
                    </svg>
                @endif
            </button>
            @endforeach
        </div>
    </div>

    {{-- ── Divider ─────────────────────────────────────────────────── --}}
    <div class="pp-divider" aria-hidden="true"></div>

    {{-- ── Edit Form ───────────────────────────────────────────────── --}}
    <div class="pp-section pp-section--form">
        <div class="pp-section-label">Profile</div>

        {{-- Display Name --}}
        <div class="pp-field">
            <label class="pp-field-label" for="profileNameInput">Display Name</label>
            <input type="text"
                   id="profileNameInput"
                   wire:model="name"
                   class="pp-input {{ $errors->has('name') ? 'pp-input--error' : '' }}"
                   placeholder="Your name"
                   maxlength="60"
                   autocomplete="off"
                   aria-describedby="{{ $errors->has('name') ? 'pp-name-error' : '' }}">
            @error('name')
                <p class="pp-field-error" id="pp-name-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        {{-- Bio / Status Quote --}}
        <div class="pp-field">
            <label class="pp-field-label" for="profileBioInput">
                What's your status?
                <span class="pp-char-count" aria-live="polite">{{ strlen($statusQuote) }}/160</span>
            </label>
            <textarea id="profileBioInput"
                      wire:model.defer="statusQuote"
                      class="pp-input pp-input--textarea {{ $errors->has('statusQuote') ? 'pp-input--error' : '' }}"
                      placeholder="e.g. In a meeting until 3pm"
                      maxlength="160"
                      rows="2"
                      aria-describedby="{{ $errors->has('statusQuote') ? 'pp-bio-error' : '' }}"></textarea>
            @error('statusQuote')
                <p class="pp-field-error" id="pp-bio-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        {{-- General save error --}}
        @if($saveError)
            <div class="pp-alert pp-alert--error" role="alert" aria-live="assertive">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                {{ $saveError }}
            </div>
        @endif

        {{-- Success message --}}
        @if($successMessage)
            <div class="pp-alert pp-alert--success pp-alert--autohide"
                 role="status"
                 aria-live="polite">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                {{ $successMessage }}
            </div>
        @endif

        {{-- Save button --}}
        <button type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                wire:loading.class="pp-save-btn--loading"
                class="pp-save-btn"
                id="ppSaveBtn"
                aria-busy="{{ $isSaving ? 'true' : 'false' }}">
            <span wire:loading.remove wire:target="save" class="pp-save-btn-text">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                    <polyline points="7 3 7 8 15 8"/>
                </svg>
                Save Changes
            </span>
            <span wire:loading wire:target="save" class="pp-save-btn-saving">
                <svg class="pp-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <circle cx="12" cy="12" r="10" stroke-opacity=".25"/>
                    <path d="M12 2a10 10 0 0 1 10 10"/>
                </svg>
                Saving…
            </span>
        </button>

    </div>

    {{-- ── Footer: Sign out ────────────────────────────────────────── --}}
    <div class="pp-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="pp-signout-btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Sign out
            </button>
        </form>
    </div>

</div>
