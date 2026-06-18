{{-- resources/views/livewire/profile/panel.blade.php --}}
<div class="profile-dropdown {{ $isOpen ? 'dropdown-open' : '' }}"
     id="profileDropdown"
     role="dialog"
     aria-modal="true"
     aria-label="Edit profile"
     wire:key="profile-panel">

    {{-- ── Avatar + Upload ─────────────────────────────── --}}
    <div class="profile-panel-hero">
        {{--
            NO wire:model on the file input.
            We use a plain JS FileReader to preview the image locally,
            then pass the base64 data to a hidden Livewire property on save.
            This prevents Livewire's upload endpoint from replacing the page.
        --}}
        <label class="profile-avatar-upload" for="profileAvatarInput"
               title="Change profile picture" aria-label="Upload profile picture">
            <div id="avatarPreviewWrap" wire:ignore>
                @if(auth()->user()->profile_image)
                    <img src="{{ Storage::url(auth()->user()->profile_image) }}"
                         alt="{{ auth()->user()->name }}"
                         class="profile-avatar-img"
                         id="avatarPreviewImg"
                         data-user-id="{{ auth()->id() }}">
                @else
                    <div class="profile-avatar-initials"
                         id="avatarPreviewImg"
                         data-user-id="{{ auth()->id() }}"
                         aria-hidden="true">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                @endif
            </div>
            <div class="profile-avatar-overlay" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                    <circle cx="12" cy="13" r="4"/>
                </svg>
                <span>Change Photo</span>
            </div>
        </label>

        {{-- Plain file input — NO wire:model, purely JS-driven --}}
        <input type="file"
               id="profileAvatarInput"
               accept="image/jpeg,image/png,image/webp"
               class="file-input-hidden"
               aria-label="Select profile picture">

        @error('newAvatar')
            <p class="profile-error-msg">{{ $message }}</p>
        @enderror

        <div class="profile-panel-name-wrap">
            <div class="profile-panel-display-name">{{ auth()->user()->name }}</div>
            <div class="profile-panel-role">
                @if(auth()->user()->is_admin)
                    <span class="badge-admin">Admin</span>
                @else
                    <span class="badge-member">Member</span>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Status Picker ───────────────────────────────── --}}
    <div class="profile-panel-section">
        <div class="profile-section-label">Status</div>
        <div class="profile-status-picker" role="group" aria-label="Set your status">
            @foreach([
                ['value' => 'available', 'label' => 'Available', 'class' => 'status-icon-online'],
                ['value' => 'busy',      'label' => 'Busy',      'class' => 'status-icon-busy'],
                ['value' => 'away',      'label' => 'Away',      'class' => 'status-icon-away'],
                ['value' => 'dnd',       'label' => 'Do Not Disturb', 'class' => 'status-icon-dnd'],
            ] as $s)
            <button type="button"
                    wire:click="$set('status', '{{ $s['value'] }}')"
                    class="profile-status-btn {{ $status === $s['value'] ? 'profile-status-btn--active' : '' }}"
                    aria-pressed="{{ $status === $s['value'] ? 'true' : 'false' }}"
                    title="{{ $s['label'] }}">
                <span class="profile-status-dot-sm {{ $s['class'] }}" aria-hidden="true"></span>
                <span>{{ $s['label'] }}</span>
            </button>
            @endforeach
        </div>
    </div>

    {{-- ── Edit Form ───────────────────────────────────── --}}
    <div class="profile-panel-section profile-panel-form">

        {{-- Name --}}
        <div class="profile-edit-field">
            <label class="profile-field-label" for="profileNameInput">Display Name</label>
            <input type="text"
                   id="profileNameInput"
                   wire:model.defer="name"
                   class="profile-input {{ $errors->has('name') ? 'profile-input--error' : '' }}"
                   placeholder="Your name"
                   maxlength="60"
                   autocomplete="off"
                   aria-describedby="{{ $errors->has('name') ? 'name-error' : '' }}">
            @error('name')
                <p class="profile-error-msg" id="name-error">{{ $message }}</p>
            @enderror
        </div>

        {{-- Bio / Status Quote --}}
        <div class="profile-edit-field">
            <label class="profile-field-label" for="profileBioInput">
                Bio
                <span class="profile-char-counter" aria-live="polite">{{ strlen($statusQuote) }}/160</span>
            </label>
            <textarea id="profileBioInput"
                      wire:model.defer="statusQuote"
                      class="profile-input profile-input--textarea {{ $errors->has('statusQuote') ? 'profile-input--error' : '' }}"
                      placeholder="What's on your mind?"
                      maxlength="160"
                      rows="2"
                      aria-describedby="{{ $errors->has('statusQuote') ? 'bio-error' : '' }}"></textarea>
            @error('statusQuote')
                <p class="profile-error-msg" id="bio-error">{{ $message }}</p>
            @enderror
        </div>

        {{-- General save error --}}
        @if($saveError)
            <p class="profile-error-msg profile-error-msg--general">{{ $saveError }}</p>
        @endif

        {{-- Success message — use CSS animation to fade out, no $wire.set to avoid re-render --}}
        @if($successMessage)
            <div class="profile-success-msg profile-success-msg--autohide"
                 role="status"
                 aria-live="polite">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                {{ $successMessage }}
            </div>
        @endif

        {{-- Save Button --}}
        <button type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                class="profile-save-btn">
            <span wire:loading.remove wire:target="save">Save Changes</span>
            <span wire:loading wire:target="save" class="profile-saving-label">
                <svg class="profile-spinner" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10" stroke-opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke-opacity="1"/></svg>
                Saving…
            </span>
        </button>

    </div>

    {{-- ── Sign Out ─────────────────────────────────────── --}}
    <div class="profile-panel-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="profile-signout-btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Sign out
            </button>
        </form>
    </div>

</div>
