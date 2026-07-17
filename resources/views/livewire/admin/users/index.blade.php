<div>
    <style>
        .users-page { animation: fadeUp .22s ease both; }
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(8px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* Page header */
        .page-hdr {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
            gap: 12px;
        }

        .page-hdr-left h1 {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-1);
            margin: 0 0 3px;
        }

        .page-hdr-left p {
            font-size: .8125rem;
            color: var(--text-2);
            margin: 0;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--accent);
            color: #fff;
            padding: 10px 18px;
            border-radius: 10px;
            font-size: .875rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background .15s, transform .1s;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .btn-primary:hover {
            background: var(--accent-h);
            transform: translateY(-1px);
        }

        /* Alert banners */
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: .875rem;
            font-weight: 500;
            margin-bottom: 18px;
            border: 1px solid;
        }

        .alert-success { background: rgba(87,199,90,.1); border-color: rgba(87,199,90,.4); color: #57c75a; }
        .alert-error   { background: rgba(224,91,91,.1); border-color: rgba(224,91,91,.4); color: #e05b5b; }

        /* Search + loading */
        .search-bar {
            position: relative;
            max-width: 360px;
            margin-bottom: 18px;
        }

        .search-bar svg {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-3);
            pointer-events: none;
        }

        .search-input {
            width: 100%;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 14px 10px 38px;
            color: var(--text-1);
            font-size: .875rem;
            outline: none;
            transition: border-color .15s;
        }

        .search-input:focus { border-color: var(--accent); }
        .search-input::placeholder { color: var(--text-3); }

        .loading-tag {
            font-size: .78rem;
            color: var(--accent-h);
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Table wrapper */
        .table-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
        }

        .data-table { width: 100%; border-collapse: collapse; }

        .data-table thead {
            background: rgba(0,0,0,.2);
        }

        .data-table th {
            text-align: left;
            padding: 13px 18px;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--text-2);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        .data-table td {
            padding: 14px 18px;
            font-size: .875rem;
            color: var(--text-1);
            border-bottom: 1px solid rgba(53,53,74,.6);
            vertical-align: middle;
        }

        .data-table tr:last-child td { border-bottom: none; }

        .data-table tbody tr {
            transition: background .12s;
        }

        .data-table tbody tr:hover {
            background: rgba(255,255,255,.03);
        }

        /* User cell */
        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .8rem;
            color: #fff;
            flex-shrink: 0;
        }

        .user-name {
            font-weight: 600;
            color: var(--text-1);
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .badge-admin {
            background: var(--accent-dim);
            color: var(--accent-h);
            border: 1px solid rgba(98,100,167,.3);
        }

        .badge-user {
            background: rgba(255,255,255,.06);
            color: var(--text-2);
            border: 1px solid var(--border);
        }

        /* Actions */
        .actions-cell { display: flex; align-items: center; gap: 4px; }

        .act-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 7px;
            font-size: .8rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background .15s, color .15s;
        }

        .act-btn-edit {
            background: rgba(98,100,167,.15);
            color: var(--accent-h);
        }

        .act-btn-edit:hover { background: rgba(98,100,167,.3); }

        .act-btn-del {
            background: rgba(224,91,91,.1);
            color: #e05b5b;
        }

        .act-btn-del:hover { background: rgba(224,91,91,.22); }

        .act-btn-reset {
            background: rgba(255,179,71,.1);
            color: #ffb347;
        }

        .act-btn-reset:hover { background: rgba(255,179,71,.22); }

        .act-btn-enable {
            background: rgba(87,199,90,.1);
            color: #57c75a;
        }

        .act-btn-enable:hover { background: rgba(87,199,90,.22); }

        .act-btn-disable {
            background: rgba(224,91,91,.08);
            color: #e05b5b;
        }

        .act-btn-disable:hover { background: rgba(224,91,91,.2); }

        /* Status badge */
        .badge-active {
            background: rgba(87,199,90,.12);
            color: #57c75a;
            border: 1px solid rgba(87,199,90,.3);
        }

        .badge-inactive {
            background: rgba(224,91,91,.1);
            color: #e05b5b;
            border: 1px solid rgba(224,91,91,.3);
        }

        /* ── Reset Password Modal ─────────────────── */
        .rp-backdrop {
            position: fixed; inset: 0;
            background: rgba(0,0,0,.65);
            display: flex; align-items: center; justify-content: center;
            z-index: 9999;
            animation: rpFadeIn .15s ease;
        }

        @keyframes rpFadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        .rp-modal {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            width: 100%;
            max-width: 420px;
            padding: 28px;
            box-shadow: 0 24px 64px rgba(0,0,0,.5);
            animation: rpSlideIn .2s cubic-bezier(.16,1,.3,1);
        }

        @keyframes rpSlideIn {
            from { opacity: 0; transform: translateY(-12px) scale(.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .rp-header {
            display: flex; align-items: flex-start;
            justify-content: space-between; gap: 12px;
            margin-bottom: 22px;
        }

        .rp-header-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            background: rgba(255,179,71,.12);
            border: 1px solid rgba(255,179,71,.25);
            display: flex; align-items: center; justify-content: center;
            color: #ffb347; flex-shrink: 0;
        }

        .rp-header-text h3 {
            font-size: 1rem; font-weight: 700;
            color: var(--text-1); margin: 0 0 4px;
        }

        .rp-header-text p {
            font-size: .8rem; color: var(--text-2); margin: 0;
        }

        .rp-close {
            background: none; border: none; cursor: pointer;
            color: var(--text-3); padding: 4px;
            border-radius: 6px; transition: color .15s;
            flex-shrink: 0;
        }

        .rp-close:hover { color: var(--danger); }

        .rp-field { margin-bottom: 16px; }

        .rp-field label {
            display: block;
            font-size: .75rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .06em;
            color: var(--text-2); margin-bottom: 6px;
        }

        .rp-field input {
            width: 100%;
            background: var(--content-bg);
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: 10px 13px;
            color: var(--text-1);
            font-size: .875rem;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }

        .rp-field input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-dim);
        }

        .rp-field input::placeholder { color: var(--text-3); }

        .rp-field-error {
            font-size: .76rem; color: #e05b5b;
            margin-top: 4px;
            display: flex; align-items: center; gap: 4px;
        }

        .rp-actions {
            display: flex; gap: 10px;
            margin-top: 24px;
        }

        .rp-btn-confirm {
            flex: 1;
            display: inline-flex; align-items: center;
            justify-content: center; gap: 7px;
            background: #ffb347;
            color: #1a1200;
            padding: 11px 20px;
            border-radius: 9px;
            font-size: .875rem; font-weight: 700;
            border: none; cursor: pointer;
            transition: background .15s, transform .1s;
        }

        .rp-btn-confirm:hover {
            background: #ffc266;
            transform: translateY(-1px);
        }

        .rp-btn-cancel {
            padding: 11px 18px;
            border-radius: 9px;
            font-size: .875rem; font-weight: 600;
            background: rgba(255,255,255,.05);
            border: 1px solid var(--border);
            color: var(--text-2); cursor: pointer;
            transition: background .15s, color .15s;
        }

        .rp-btn-cancel:hover {
            background: rgba(255,255,255,.09);
            color: var(--text-1);
        }

        /* Muted text */
        .td-muted { color: var(--text-2); font-size: .8125rem; }

        /* Empty row */
        .empty-row td {
            text-align: center;
            padding: 48px 18px;
            color: var(--text-2);
        }

        /* Pagination */
        .pagination-wrap { margin-top: 20px; }

        /* Responsive: hide some cols on small screens */
        @media (max-width: 640px) {
            .col-email, .col-joined { display: none; }
        }
    </style>

    <div class="users-page">

        @if(session()->has('success'))
            <div class="alert alert-success">✓ {{ session('success') }}</div>
        @endif

        @if(session()->has('error'))
            <div class="alert alert-error">✗ {{ session('error') }}</div>
        @endif

        {{-- Header --}}
        <div class="page-hdr">
            <div class="page-hdr-left">
                <h1>Users</h1>
                <p>Manage platform users and permissions</p>
            </div>
            <a href="{{ route('users.create') }}" wire:navigate class="btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                Add User
            </a>
        </div>

        {{-- Search --}}
        <div class="search-bar">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input
                type="text"
                wire:model.live="search"
                placeholder="Search users by name or email…"
                class="search-input"
            >
        </div>

        <div wire:loading class="loading-tag">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
            Loading…
        </div>

        {{-- Table --}}
        <div class="table-card">
            <div style="overflow-x:auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th class="col-email">Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th class="col-joined">Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr style="{{ ! $user->is_active ? 'opacity:.55;' : '' }}"
                            wire:key="user-{{ $user->id }}">
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar"
                                         style="background: hsl({{ (ord($user->name[0]) * 37) % 360 }}, 50%, 42%)">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <span class="user-name">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="td-muted col-email">{{ $user->email }}</td>
                            <td>
                                @if($user->is_admin)
                                    <span class="badge badge-admin">
                                        <svg width="9" height="9" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                        Admin
                                    </span>
                                @else
                                    <span class="badge badge-user">User</span>
                                @endif
                            </td>
                            <td>
                                @if($user->is_active)
                                    <span class="badge badge-active">
                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="12"/></svg>
                                        Active
                                    </span>
                                @else
                                    <span class="badge badge-inactive">
                                        <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="12"/></svg>
                                        Disabled
                                    </span>
                                @endif
                            </td>
                            <td class="td-muted col-joined">{{ $user->created_at->diffForHumans() }}</td>
                            <td>
                                <div class="actions-cell">
                                    <a href="{{ route('users.edit', $user) }}" wire:navigate class="act-btn act-btn-edit">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        Edit
                                    </a>
                                    <button
                                        wire:click="openResetModal({{ $user->id }})"
                                        class="act-btn act-btn-reset"
                                        title="Reset password for {{ $user->name }}">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                        Reset PW
                                    </button>
                                    @if($user->id !== auth()->id())
                                    <button
                                        wire:click="toggleActive({{ $user->id }})"
                                        wire:confirm="{{ $user->is_active ? 'Disable ' . $user->name . '\'s account? They will be logged out immediately.' : 'Enable ' . $user->name . '\'s account?' }}"
                                        class="act-btn {{ $user->is_active ? 'act-btn-disable' : 'act-btn-enable' }}"
                                        title="{{ $user->is_active ? 'Disable account' : 'Enable account' }}">
                                        @if($user->is_active)
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                                            Disable
                                        @else
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                            Enable
                                        @endif
                                    </button>
                                    @endif
                                    <button
                                        wire:click="delete({{ $user->id }})"
                                        wire:confirm="Are you sure you want to delete {{ $user->name }}?"
                                        class="act-btn act-btn-del">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr class="empty-row">
                            <td colspan="5">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" style="opacity:.3;margin:0 auto 12px;display:block"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                No users found matching your search.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination-wrap">
            {{ $users->links() }}
        </div>

    </div>

    {{-- ── Reset Password Modal ─────────────────────────── --}}
    @if($showResetModal)
    <div class="rp-backdrop" wire:click.self="closeResetModal" role="dialog" aria-modal="true" aria-labelledby="rpModalTitle">
        <div class="rp-modal">

            <div class="rp-header">
                <div class="rp-header-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <div class="rp-header-text">
                    <h3 id="rpModalTitle">Reset Password</h3>
                    <p>Set a new password for <strong style="color:var(--text-1)">{{ $resetUserName }}</strong></p>
                </div>
                <button type="button" class="rp-close" wire:click="closeResetModal" aria-label="Close">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="rp-field">
                <label for="rpNewPassword">New Password</label>
                <input type="password"
                       id="rpNewPassword"
                       wire:model="newPassword"
                       placeholder="Min. 8 characters"
                       autocomplete="new-password">
                @error('newPassword')
                    <p class="rp-field-error">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="rp-field">
                <label for="rpConfirmPassword">Confirm Password</label>
                <input type="password"
                       id="rpConfirmPassword"
                       wire:model="newPasswordConfirm"
                       placeholder="Repeat new password"
                       autocomplete="new-password">
                @error('newPasswordConfirm')
                    <p class="rp-field-error">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="rp-actions">
                <button type="button" class="rp-btn-cancel" wire:click="closeResetModal">
                    Cancel
                </button>
                <button type="button" class="rp-btn-confirm" wire:click="confirmResetPassword" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="confirmResetPassword">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                        Reset Password
                    </span>
                    <span wire:loading wire:target="confirmResetPassword">Resetting…</span>
                </button>
            </div>

        </div>
    </div>
    @endif

</div>