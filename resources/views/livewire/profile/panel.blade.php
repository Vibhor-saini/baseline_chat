{{-- resources/views/livewire/profile/panel.blade.php --}}
{{--
    Premium floating profile card.
    • Status state owned by Livewire $status; JS (chat.js) keeps dot/label in sync post-render.
    • Instant visual feedback on status click via window._applyPanelStatus().
--}}
<div class="profile-panel {{ $isOpen ? 'profile-panel--open' : '' }}"
     id="profileDropdown"
     role="dialog"
     aria-modal="true"
     aria-label="Profile settings"
     wire:key="profile-panel"
     data-current-status="{{ $status }}">

    {{-- ── Hero Banner ─────────────────────────────────────────────────── --}}
    <div class="pp-hero">

        {{-- Decorative blobs --}}
        <div class="pp-hero-blob pp-hero-blob--1" aria-hidden="true"></div>
        <div class="pp-hero-blob pp-hero-blob--2" aria-hidden="true"></div>

        {{-- Close --}}
        <button type="button" class="pp-close-btn"
                wire:click="closePanel"
                aria-label="Close profile panel">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>

        {{-- Avatar --}}
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
                             style="background:{{ \App\Helpers\AvatarColor::gradient(auth()->id()) }};"
                             aria-hidden="true">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                    @endif
                </div>
                <span class="pp-avatar-status-ring pp-status-ring--{{ $status }}"
                      id="ppAvatarStatusRing"
                      aria-hidden="true"></span>
                <div class="pp-avatar-overlay" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                        <circle cx="12" cy="13" r="4"/>
                    </svg>
                </div>
            </label>

            <input type="file"
                   id="profileAvatarInput"
                   accept="image/jpeg,image/png,image/webp"
                   class="file-input-hidden"
                   aria-label="Select profile picture"
                   onclick="event.stopPropagation()">

            <div class="pp-upload-loading" id="ppUploadLoading" style="display:none" aria-live="polite">
                <svg class="pp-spinner" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="12" cy="12" r="10" stroke-opacity=".2"/>
                    <path d="M12 2a10 10 0 0 1 10 10" stroke-opacity="1"/>
                </svg>
            </div>
        </div>

        {{-- Name + status + admin pill --}}
        <div class="pp-hero-info">
            <div class="pp-hero-name">{{ auth()->user()->name }}</div>
            <div class="pp-hero-meta">
                <span class="pp-status-dot pp-status-dot--{{ $status }}"
                      id="ppPanelStatusDot"
                      aria-hidden="true"></span>
                <span class="pp-status-label" id="ppPanelStatusLabel">
                    @php
                        echo ['available'=>'Available','busy'=>'Busy','away'=>'Away','dnd'=>'Do Not Disturb'][$status] ?? 'Available';
                    @endphp
                </span>
                @if(auth()->user()->is_admin)
                    <span class="badge-admin">Admin</span>
                @endif
            </div>
            <div class="pp-hero-email">{{ auth()->user()->email }}</div>

            {{-- Remove avatar (only when image set) --}}
            @if(auth()->user()->profile_image)
                <button type="button"
                        wire:click="removeAvatar"
                        class="pp-remove-avatar-btn"
                        title="Remove profile picture"
                        aria-label="Remove profile picture">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                    Remove photo
                </button>
            @endif
        </div>
    </div>

    {{-- ── Status Picker ──────────────────────────────────────────────── --}}
    <div class="pp-section pp-section--status">
        <div class="pp-section-label">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><circle cx="12" cy="12" r="10"/></svg>
            Set status
        </div>
        <div class="pp-status-grid" role="group" aria-label="Set your status">
            @foreach([
                ['value' => 'available', 'label' => 'Available',      'icon_class' => 'pp-sicon--available', 'color' => '#23e07a'],
                ['value' => 'busy',      'label' => 'Busy',           'icon_class' => 'pp-sicon--busy',      'color' => '#ff5f72'],
                ['value' => 'away',      'label' => 'Away',           'icon_class' => 'pp-sicon--away',      'color' => '#ffb547'],
                ['value' => 'dnd',       'label' => 'Do Not Disturb', 'icon_class' => 'pp-sicon--dnd',       'color' => '#ff5f72'],
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
                    style="{{ $status === $s['value'] ? '--status-color:'.$s['color'].';' : '' }}"
                    title="{{ $s['label'] }}">
                <span class="pp-sicon {{ $s['icon_class'] }}" aria-hidden="true"></span>
                <span class="pp-status-btn-label">{{ $s['label'] }}</span>
                @if($isChangingStatus && $status === $s['value'])
                    <svg class="pp-spinner pp-spinner--xs" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" stroke-opacity=".2"/>
                        <path d="M12 2a10 10 0 0 1 10 10"/>
                    </svg>
                @endif
            </button>
            @endforeach
        </div>
    </div>

    {{-- ── Profile Form ────────────────────────────────────────────────── --}}
    <div class="pp-section pp-section--form">
        <div class="pp-section-label">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Profile
        </div>

        <div class="pp-field">
            <label class="pp-field-label" for="profileNameInput">Display Name</label>
            <input type="text"
                   id="profileNameInput"
                   wire:model="name"
                   class="pp-input {{ $errors->has('name') ? 'pp-input--error' : '' }}"
                   placeholder="Your name"
                   maxlength="60"
                   autocomplete="off">
            @error('name')
                <p class="pp-field-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="pp-field">
            <label class="pp-field-label" for="profileBioInput">
                <span>Status message</span>
                <span class="pp-char-count">{{ strlen($statusQuote) }}/160</span>
            </label>
            <textarea id="profileBioInput"
                      wire:model.defer="statusQuote"
                      class="pp-input pp-input--textarea {{ $errors->has('statusQuote') ? 'pp-input--error' : '' }}"
                      placeholder="e.g. In a meeting until 3pm…"
                      maxlength="160"
                      rows="2"></textarea>
            @error('statusQuote')
                <p class="pp-field-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        @if($saveError)
            <div class="pp-alert pp-alert--error" role="alert">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                {{ $saveError }}
            </div>
        @endif

        @if($successMessage)
            <div class="pp-alert pp-alert--success pp-alert--autohide" role="status">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                {{ $successMessage }}
            </div>
        @endif

        <button type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                wire:loading.class="pp-save-btn--loading"
                class="pp-save-btn"
                id="ppSaveBtn">
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

    {{-- ── Change Password ──────────────────────────────────────────────── --}}
    <div class="pp-section pp-section--password">
        <button type="button" class="pp-pw-toggle" wire:click="togglePasswordSection"
                aria-expanded="{{ $showPasswordSection ? 'true' : 'false' }}">
            <span class="pp-pw-toggle-left">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                Change Password
            </span>
            <svg class="pp-pw-chevron {{ $showPasswordSection ? 'pp-pw-chevron--open' : '' }}"
                 width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path d="M6 9l6 6 6-6"/>
            </svg>
        </button>

        @if($showPasswordSection)
        <div class="pp-pw-form" wire:key="pw-form">
            @if($passwordSuccess)
            <div class="pp-alert pp-alert--success" role="status">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                {{ $passwordSuccess }}
            </div>
            @endif

            <div class="pp-field">
                <label class="pp-field-label" for="ppCurrentPw">Current Password</label>
                <input type="password" id="ppCurrentPw" wire:model="currentPassword"
                       class="pp-input {{ $errors->has('currentPassword') ? 'pp-input--error' : '' }}"
                       placeholder="Your current password" autocomplete="current-password">
                @error('currentPassword') <p class="pp-field-error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="pp-field">
                <label class="pp-field-label" for="ppNewPw">New Password</label>
                <input type="password" id="ppNewPw" wire:model="newPasswordField"
                       class="pp-input {{ $errors->has('newPasswordField') ? 'pp-input--error' : '' }}"
                       placeholder="Min. 8 characters" autocomplete="new-password">
                @error('newPasswordField') <p class="pp-field-error" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="pp-field">
                <label class="pp-field-label" for="ppConfirmPw">Confirm Password</label>
                <input type="password" id="ppConfirmPw" wire:model="confirmPasswordField"
                       class="pp-input {{ $errors->has('confirmPasswordField') ? 'pp-input--error' : '' }}"
                       placeholder="Repeat new password" autocomplete="new-password">
                @error('confirmPasswordField') <p class="pp-field-error" role="alert">{{ $message }}</p> @enderror
            </div>

            <button type="button" wire:click="changePassword" wire:loading.attr="disabled" class="pp-pw-submit-btn">
                <span wire:loading.remove wire:target="changePassword">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                    Update Password
                </span>
                <span wire:loading wire:target="changePassword">Updating…</span>
            </button>
        </div>
        @endif
    </div>

    {{-- ── Footer ───────────────────────────────────────────────────────── --}}
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
