<div>
    <style>
        .dash-page { animation: fadeUp .25s ease both; }
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(10px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* ── Stats grid ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 22px 22px 18px;
            position: relative;
            overflow: hidden;
            transition: border-color .2s, transform .15s;
        }

        .stat-card:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: var(--card-accent, var(--accent));
            border-radius: 14px 14px 0 0;
        }

        .stat-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--accent-dim);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
        }

        .stat-label {
            font-size: .75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-2);
            margin-bottom: 6px;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 800;
            line-height: 1;
            color: var(--text-1);
        }

        .stat-value.accent  { color: var(--accent-h); }
        .stat-value.green   { color: #57c75a; }
        .stat-value.orange  { color: #e8a040; }

        /* ── Second row ── */
        .row2 {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 16px;
        }

        @media (max-width: 1024px) { .row2 { grid-template-columns: 1fr; } }

        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 22px;
            transition: border-color .2s;
        }

        .panel:hover { border-color: rgba(98,100,167,.4); }

        .panel-title {
            font-size: .9375rem;
            font-weight: 700;
            color: var(--text-1);
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .panel-title svg { color: var(--accent-h); }

        /* Activity rows */
        .activity-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 13px 0;
            border-bottom: 1px solid var(--border);
        }

        .activity-row:last-child { border-bottom: none; }

        .activity-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: .875rem;
            color: var(--text-2);
        }

        .activity-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--accent);
            flex-shrink: 0;
        }

        .activity-val {
            font-size: .875rem;
            font-weight: 600;
            color: var(--text-1);
        }

        .activity-val.green { color: #57c75a; }

        /* Quick actions */
        .action-btn {
            display: block;
            width: 100%;
            padding: 13px 18px;
            border-radius: 10px;
            font-size: .875rem;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background .15s, transform .1s;
            margin-bottom: 10px;
        }

        .action-btn:last-child { margin-bottom: 0; }

        .action-btn.primary {
            background: var(--accent);
            color: #fff;
        }

        .action-btn.primary:hover {
            background: var(--accent-h);
            transform: translateY(-1px);
        }

        .action-btn.muted {
            background: var(--surface-2);
            color: var(--text-3);
            cursor: not-allowed;
            opacity: .6;
        }

        /* Page header */
        .page-hd {
            margin-bottom: 24px;
        }

        .page-hd h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-1);
            margin: 0 0 4px;
        }

        .page-hd p {
            font-size: .875rem;
            color: var(--text-2);
            margin: 0;
        }

        /* Profile chip in header */
        .hd-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
        }

        .hd-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.1rem;
            color: #fff;
            position: relative;
        }

        .hd-avatar .presence {
            position: absolute;
            bottom: 1px; right: 1px;
            width: 12px; height: 12px;
            border-radius: 50%;
            background: #57c75a;
            border: 2px solid var(--content-bg);
        }

        .hd-info h1 { font-size: 1.4rem; font-weight: 800; margin: 0 0 2px; }
        .hd-info p  { font-size: .8125rem; color: var(--text-2); margin: 0; }
    </style>

    <div class="dash-page">

        {{-- Profile header --}}
        <div class="hd-profile">
            <div class="hd-avatar">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                <span class="presence"></span>
            </div>
            <div class="hd-info">
                <h1>Welcome back, {{ auth()->user()->name }}</h1>
                <p>Here's what's happening on your platform today.</p>
            </div>
        </div>

        {{-- Stats grid --}}
        <div class="stats-grid">

            <div class="stat-card" style="--card-accent:#6264a7">
                <div class="stat-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7b7dd6" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div class="stat-label">Total Users</div>
                <div class="stat-value">{{ $totalUsers }}</div>
            </div>

            <div class="stat-card" style="--card-accent:#7b7dd6">
                <div class="stat-icon" style="background:rgba(123,125,214,.15)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7b7dd6" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/><path d="M16 11l2 2 4-4"/></svg>
                </div>
                <div class="stat-label">Admin Users</div>
                <div class="stat-value accent">{{ $adminUsers }}</div>
            </div>

            <div class="stat-card" style="--card-accent:#3a8a3c">
                <div class="stat-icon" style="background:rgba(87,199,90,.1)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#57c75a" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <div class="stat-label">Normal Users</div>
                <div class="stat-value">{{ $normalUsers }}</div>
            </div>

            <div class="stat-card" style="--card-accent:#57c75a">
                <div class="stat-icon" style="background:rgba(87,199,90,.1)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#57c75a" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="stat-label">Online Users</div>
                <div class="stat-value green">0</div>
            </div>

        </div>

        {{-- Row 2 --}}
        <div class="row2">

            <div class="panel">
                <div class="panel-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    Platform Activity
                </div>

                <div class="activity-row">
                    <span class="activity-label"><span class="activity-dot"></span>Messages Today</span>
                    <span class="activity-val">0</span>
                </div>
                <div class="activity-row">
                    <span class="activity-label"><span class="activity-dot" style="background:#e8a040"></span>Storage Usage</span>
                    <span class="activity-val">0 MB</span>
                </div>
                <div class="activity-row">
                    <span class="activity-label"><span class="activity-dot" style="background:#57c75a"></span>Queue Status</span>
                    <span class="activity-val green">Active</span>
                </div>
                <div class="activity-row">
                    <span class="activity-label"><span class="activity-dot" style="background:#57c75a"></span>Realtime Engine</span>
                    <span class="activity-val green">Connected</span>
                </div>
            </div>

            <div class="panel">
                <div class="panel-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    Quick Actions
                </div>

                <a href="{{ route('users.create') }}" class="action-btn primary">
                    + Create User
                </a>

                <a href="{{ route('chat.index') }}" class="action-btn primary" style="background:var(--surface-2);color:var(--text-1);border:1px solid var(--border)">
                    Open Chat
                </a>

                <button class="action-btn muted" disabled>
                    Reports — Coming Soon
                </button>
            </div>

        </div>

    </div>

</div>