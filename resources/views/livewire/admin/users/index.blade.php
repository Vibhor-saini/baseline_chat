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
            <a href="{{ route('users.create') }}" class="btn-primary">
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
                            <th class="col-joined">Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
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
                            <td class="td-muted col-joined">{{ $user->created_at->diffForHumans() }}</td>
                            <td>
                                <div class="actions-cell">
                                    <a href="{{ route('users.edit', $user) }}" class="act-btn act-btn-edit">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        Edit
                                    </a>
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

</div>