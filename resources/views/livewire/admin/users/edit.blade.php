<div class="max-w-2xl">
    <style>
        .form-page { animation: fadeUp .22s ease both; }
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(8px); }
            to   { opacity:1; transform:translateY(0); }
        }

        .edit-hdr {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        .edit-hdr-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.25rem;
            color: #fff;
            flex-shrink: 0;
        }

        .edit-hdr-info h1 { font-size: 1.4rem; font-weight: 800; color: var(--text-1); margin: 0 0 3px; }
        .edit-hdr-info p  { font-size: .8125rem; color: var(--text-2); margin: 0; }

        .form-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
        }

        .field { margin-bottom: 20px; }

        .field label {
            display: block;
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-2);
            margin-bottom: 7px;
        }

        .field input[type="text"],
        .field input[type="email"],
        .field input[type="password"] {
            width: 100%;
            background: var(--content-bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 11px 14px;
            color: var(--text-1);
            font-size: .9rem;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }

        .field input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-dim);
        }

        .field input::placeholder { color: var(--text-3); }

        .field-hint {
            font-size: .75rem;
            color: var(--text-3);
            margin-top: 4px;
        }

        .field-error {
            font-size: .78rem;
            color: #e05b5b;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .toggle-field {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            background: var(--content-bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            cursor: pointer;
            transition: border-color .15s;
        }

        .toggle-field:hover { border-color: var(--accent); }

        .toggle-field input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--accent);
            cursor: pointer;
        }

        .toggle-text strong {
            display: block;
            font-size: .875rem;
            font-weight: 600;
            color: var(--text-1);
        }

        .toggle-text span {
            font-size: .78rem;
            color: var(--text-2);
        }

        .form-divider {
            height: 1px;
            background: var(--border);
            margin: 22px 0;
        }

        .form-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-submit {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: var(--accent);
            color: #fff;
            padding: 11px 22px;
            border-radius: 10px;
            font-size: .875rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: background .15s, transform .1s;
        }

        .btn-submit:hover { background: var(--accent-h); transform: translateY(-1px); }

        .btn-cancel {
            font-size: .875rem;
            color: var(--text-2);
            text-decoration: none;
            padding: 11px 16px;
            border-radius: 10px;
            transition: background .15s, color .15s;
        }

        .btn-cancel:hover { background: var(--surface-2); color: var(--text-1); }
    </style>

    <div class="form-page">

        {{-- Header with profile preview --}}
        <div class="edit-hdr">
            <div class="edit-hdr-avatar"
                 style="background: hsl({{ (ord($user->name[0]) * 37) % 360 }}, 50%, 42%)">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="edit-hdr-info">
                <h1>Edit User</h1>
                <p>Updating profile for <strong style="color:var(--text-1)">{{ $user->name }}</strong></p>
            </div>
        </div>

        <div class="form-card">

            <form wire:submit="update">

                <div class="field">
                    <label>Full Name</label>
                    <input type="text" wire:model="name" placeholder="Full name">
                    @error('name')
                        <p class="field-error">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="field">
                    <label>Email Address</label>
                    <input type="email" wire:model="email" placeholder="Email address">
                    @error('email')
                        <p class="field-error">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="field">
                    <label>New Password</label>
                    <input type="password" wire:model="password" placeholder="Leave blank to keep current password">
                    <p class="field-hint">Only fill this in if you want to change the password.</p>
                </div>

                <div class="form-divider"></div>

                <div class="field">
                    <label>Permissions</label>
                    <label class="toggle-field">
                        <input type="checkbox" wire:model="is_admin">
                        <div class="toggle-text">
                            <strong>Admin Access</strong>
                            <span>Grant full administrative privileges to this user</span>
                        </div>
                    </label>
                </div>

                <div class="form-divider"></div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                        Update User
                    </button>
                    <a href="{{ route('users.index') }}" class="btn-cancel">Cancel</a>
                </div>

            </form>

        </div>

    </div>

</div>