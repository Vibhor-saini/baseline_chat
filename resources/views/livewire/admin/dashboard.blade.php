<div>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Syne:wght@600;700;800&display=swap');

        /* ── Design tokens (matches chat UI) ── */
        :root {
            --dash-bg:          #080810;
            --dash-surface:     #111120;
            --dash-raised:      #161628;
            --dash-overlay:     #1c1c32;
            --dash-hover:       #212138;
            --border-sub:       rgba(255,255,255,.05);
            --border-dim:       rgba(255,255,255,.08);
            --border-acc:       rgba(130,110,255,.3);
            --accent:           #7c5cfc;
            --accent-b:         #9b7eff;
            --accent-dim:       rgba(124,92,252,.12);
            --accent-glow:      rgba(124,92,252,.22);
            --teal:             #38d9c0;
            --teal-dim:         rgba(56,217,192,.1);
            --green:            #23e07a;
            --green-dim:        rgba(35,224,122,.12);
            --amber:            #ffb547;
            --amber-dim:        rgba(255,181,71,.1);
            --red:              #ff5f72;
            --text-1:           #eeeef5;
            --text-2:           #8a8aaa;
            --text-3:           #4e4e6e;
            --shadow-card:      0 4px 24px rgba(0,0,0,.45);
            --shadow-glow:      0 0 32px var(--accent-glow);
        }

        /* ── Page wrapper ── */
        .dash-page {
            font-family: 'DM Sans', system-ui, sans-serif;
            color: var(--text-1);
            padding: 4px 0;
        }

        /* Staggered card entrance */
        .dash-page > * {
            animation: dashFadeUp .4s cubic-bezier(.16,1,.3,1) both;
        }

        .dash-page > *:nth-child(1) { animation-delay: 0s; }
        .dash-page > *:nth-child(2) { animation-delay: .06s; }
        .dash-page > *:nth-child(3) { animation-delay: .12s; }

        @keyframes dashFadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Profile header ── */
        .hd-profile {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 32px;
            padding: 20px 24px;
            background: linear-gradient(135deg,
                rgba(124,92,252,.1) 0%,
                rgba(56,217,192,.04) 60%,
                transparent 100%);
            border: 1px solid var(--border-acc);
            border-radius: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Decorative blobs */
        .hd-profile::before {
            content: '';
            position: absolute;
            top: -40px; right: -40px;
            width: 180px; height: 180px;
            background: radial-gradient(circle, rgba(124,92,252,.15), transparent 70%);
            pointer-events: none;
        }

        .hd-profile::after {
            content: '';
            position: absolute;
            bottom: -30px; left: 200px;
            width: 140px; height: 140px;
            background: radial-gradient(circle, rgba(56,217,192,.1), transparent 70%);
            pointer-events: none;
        }

        .hd-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), #5e3de8);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.2rem;
            color: #fff;
            position: relative;
            flex-shrink: 0;
            box-shadow: 0 0 20px var(--accent-glow);
        }

        .hd-avatar .presence {
            position: absolute;
            bottom: 1px; right: 1px;
            width: 13px; height: 13px;
            border-radius: 50%;
            background: var(--green);
            border: 2px solid var(--dash-bg);
            box-shadow: 0 0 7px var(--green);
        }

        .hd-info h1 {
            font-family: 'Syne', sans-serif;
            font-size: 1.45rem;
            font-weight: 800;
            margin: 0 0 4px;
            letter-spacing: -.02em;
            color: var(--text-1);
        }

        .hd-info p {
            font-size: .82rem;
            color: var(--text-2);
            margin: 0;
        }

        .hd-badge {
            margin-left: auto;
            padding: 6px 14px;
            border-radius: 20px;
            background: var(--accent-dim);
            border: 1px solid var(--border-acc);
            font-size: .72rem;
            font-weight: 700;
            color: var(--accent-b);
            text-transform: uppercase;
            letter-spacing: .07em;
            white-space: nowrap;
        }

        /* ── Section label ── */
        .section-label {
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--text-3);
            margin-bottom: 12px;
            padding-left: 2px;
        }

        /* ── Stats grid ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 12px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--dash-surface);
            border: 1px solid var(--border-dim);
            border-radius: 18px;
            padding: 22px 20px 18px;
            position: relative;
            overflow: hidden;
            cursor: default;
            transition: border-color .25s, transform .2s, box-shadow .25s;
        }

        .stat-card:hover {
            border-color: var(--card-border, var(--border-acc));
            transform: translateY(-3px);
            box-shadow: 0 8px 32px rgba(0,0,0,.4), 0 0 0 1px var(--card-border, var(--border-acc));
        }

        /* Top accent bar */
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: var(--card-line, linear-gradient(90deg, var(--accent), var(--teal)));
            border-radius: 18px 18px 0 0;
        }

        /* Ambient glow in corner */
        .stat-card::after {
            content: '';
            position: absolute;
            top: -20px; right: -20px;
            width: 90px; height: 90px;
            background: radial-gradient(circle, var(--card-glow, var(--accent-glow)), transparent 70%);
            pointer-events: none;
            opacity: 0;
            transition: opacity .25s;
        }

        .stat-card:hover::after { opacity: 1; }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: var(--card-icon-bg, var(--accent-dim));
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .stat-label {
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .09em;
            color: var(--text-3);
            margin-bottom: 8px;
        }

        .stat-value {
            font-family: 'Syne', sans-serif;
            font-size: 2.4rem;
            font-weight: 800;
            line-height: 1;
            color: var(--text-1);
            letter-spacing: -.03em;
        }

        .stat-value.c-accent  { color: var(--accent-b); }
        .stat-value.c-teal    { color: var(--teal); }
        .stat-value.c-green   { color: var(--green); }
        .stat-value.c-amber   { color: var(--amber); }

        .stat-sub {
            font-size: .72rem;
            color: var(--text-3);
            margin-top: 6px;
        }

        /* ── Main row ── */
        .row2 {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 12px;
        }

        @media (max-width: 1024px) { .row2 { grid-template-columns: 1fr; } }

        /* ── Panel ── */
        .panel {
            background: var(--dash-surface);
            border: 1px solid var(--border-dim);
            border-radius: 18px;
            padding: 22px;
            transition: border-color .2s;
        }

        .panel:hover { border-color: var(--border-acc); }

        .panel-title {
            font-family: 'Syne', sans-serif;
            font-size: .9rem;
            font-weight: 700;
            color: var(--text-1);
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 9px;
            letter-spacing: -.01em;
        }

        .panel-title .title-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: var(--accent-dim);
            border: 1px solid var(--border-acc);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-b);
        }

        /* ── Activity rows ── */
        .activity-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 13px 0;
            border-bottom: 1px solid var(--border-sub);
            gap: 10px;
        }

        .activity-row:last-child { border-bottom: none; }

        .activity-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: .83rem;
            color: var(--text-2);
        }

        .activity-pip {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .activity-val {
            font-size: .83rem;
            font-weight: 700;
            color: var(--text-1);
            font-variant-numeric: tabular-nums;
        }

        .activity-val.c-green  { color: var(--green); }
        .activity-val.c-amber  { color: var(--amber); }

        /* ── Quick actions ── */
        .actions-list {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 13px 18px;
            border-radius: 12px;
            font-size: .83rem;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: transform .15s, box-shadow .15s, opacity .15s;
            letter-spacing: .01em;
        }

        .action-btn.primary {
            background: linear-gradient(135deg, var(--accent), #5e3de8);
            color: #fff;
            box-shadow: 0 0 16px var(--accent-glow);
        }

        .action-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 28px var(--accent-glow);
        }

        .action-btn.ghost {
            background: var(--dash-raised);
            color: var(--text-1);
            border: 1px solid var(--border-dim);
        }

        .action-btn.ghost:hover {
            border-color: var(--border-acc);
            transform: translateY(-2px);
            background: var(--dash-overlay);
        }

        .action-btn.disabled-btn {
            background: var(--dash-raised);
            color: var(--text-3);
            border: 1px solid var(--border-sub);
            cursor: not-allowed;
            opacity: .5;
        }

        /* ── Divider ── */
        .divider {
            height: 1px;
            background: var(--border-sub);
            margin: 8px 0;
        }

    </style>

    <div class="dash-page">

        {{-- ── Profile header ── --}}
        <div class="hd-profile">
            <div class="hd-avatar">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                <span class="presence"></span>
            </div>
            <div class="hd-info">
                <h1>Welcome back, {{ auth()->user()->name }}</h1>
                <p>Here's what's happening on your platform today.</p>
            </div>
            <div class="hd-badge">
                {{ auth()->user()->is_admin ? 'Admin' : 'Member' }}
            </div>
        </div>

        {{-- ── Stats ── --}}
        <div class="section-label">Overview</div>
        <div class="stats-grid">

            <div class="stat-card"
                 style="--card-line: linear-gradient(90deg,#7c5cfc,#9b7eff);
                        --card-border: rgba(124,92,252,.35);
                        --card-glow: rgba(124,92,252,.18);">
                <div class="stat-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent-b)" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div class="stat-label">Total Users</div>
                <div class="stat-value">{{ $totalUsers }}</div>
                <div class="stat-sub">All registered accounts</div>
            </div>

            <div class="stat-card"
                 style="--card-line: linear-gradient(90deg,#9b7eff,#38d9c0);
                        --card-border: rgba(155,126,255,.35);
                        --card-glow: rgba(155,126,255,.15);
                        --card-icon-bg: rgba(155,126,255,.12);">
                <div class="stat-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9b7eff" stroke-width="2">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M20 21a8 8 0 1 0-16 0"/>
                        <path d="M16 11l2 2 4-4"/>
                    </svg>
                </div>
                <div class="stat-label">Admin Users</div>
                <div class="stat-value c-accent">{{ $adminUsers }}</div>
                <div class="stat-sub">Elevated privileges</div>
            </div>

            <div class="stat-card"
                 style="--card-line: linear-gradient(90deg,#38d9c0,#23e07a);
                        --card-border: rgba(56,217,192,.3);
                        --card-glow: rgba(56,217,192,.15);
                        --card-icon-bg: rgba(56,217,192,.1);">
                <div class="stat-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--teal)" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <div class="stat-label">Normal Users</div>
                <div class="stat-value c-teal">{{ $normalUsers }}</div>
                <div class="stat-sub">Standard accounts</div>
            </div>

            <div class="stat-card"
                 style="--card-line: linear-gradient(90deg,#23e07a,#57ff9a);
                        --card-border: rgba(35,224,122,.3);
                        --card-glow: rgba(35,224,122,.15);
                        --card-icon-bg: rgba(35,224,122,.1);">
                <div class="stat-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <div class="stat-label">Online Now</div>
                <div class="stat-value c-green">0</div>
                <div class="stat-sub">Active sessions</div>
            </div>

        </div>

        {{-- ── Row 2: Activity + Actions ── --}}
        <div class="section-label">Platform</div>
        <div class="row2">

            {{-- Activity panel --}}
            <div class="panel">
                <div class="panel-title">
                    <div class="title-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                        </svg>
                    </div>
                    Platform Activity
                </div>

                <div class="activity-row">
                    <span class="activity-label">
                        <span class="activity-pip" style="background:var(--accent-b);box-shadow:0 0 5px var(--accent-b)"></span>
                        Messages Today
                    </span>
                    <span class="activity-val">0</span>
                </div>

                <div class="activity-row">
                    <span class="activity-label">
                        <span class="activity-pip" style="background:var(--amber);box-shadow:0 0 5px var(--amber)"></span>
                        Storage Usage
                    </span>
                    <span class="activity-val c-amber">0 MB</span>
                </div>

                <div class="activity-row">
                    <span class="activity-label">
                        <span class="activity-pip" style="background:var(--green);box-shadow:0 0 5px var(--green)"></span>
                        Queue Status
                    </span>
                    <span class="activity-val c-green">Active</span>
                </div>

                <div class="activity-row">
                    <span class="activity-label">
                        <span class="activity-pip" style="background:var(--teal);box-shadow:0 0 5px var(--teal)"></span>
                        Realtime Engine
                    </span>
                    <span class="activity-val c-green">Connected</span>
                </div>

            </div>

            {{-- Quick actions panel --}}
            <div class="panel">
                <div class="panel-title">
                    <div class="title-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                        </svg>
                    </div>
                    Quick Actions
                </div>

                <div class="actions-list">
                    <a href="{{ route('users.create') }}" class="action-btn primary">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <line x1="19" y1="8" x2="19" y2="14"/>
                            <line x1="22" y1="11" x2="16" y2="11"/>
                        </svg>
                        Create User
                    </a>

                    <a href="{{ route('chat.index') }}" class="action-btn ghost">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                        Open Chat
                    </a>

                    <div class="divider"></div>

                    <button class="action-btn disabled-btn" disabled>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <path d="M3 9h18M9 21V9"/>
                        </svg>
                        Reports — Coming Soon
                    </button>
                </div>
            </div>

        </div>

    </div>

</div>  