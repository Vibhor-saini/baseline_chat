<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Baseline Chat') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        :root {
            --nav-bg:       #1a1a24;
            --nav-width:    68px;
            --content-bg:   #1e1e28;
            --surface:      #252532;
            --surface-2:    #2d2d3d;
            --border:       #35354a;
            --accent:       #6264a7;
            --accent-h:     #7b7dd6;
            --accent-dim:   rgba(98,100,167,.18);
            --text-1:       #f0f0f5;
            --text-2:       #9090b0;
            --text-3:       #5a5a78;
            --online:       #57c75a;
            --danger:       #e05b5b;
            --radius:       10px;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--content-bg);
            color: var(--text-1);
            -webkit-font-smoothing: antialiased;
        }

        /* ── App shell ────────────────────────────── */
        .app-shell {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* ── Left nav rail ────────────────────────── */
        .nav-rail {
            width: var(--nav-width);
            background: var(--nav-bg);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 12px 0 16px;
            flex-shrink: 0;
            border-right: 1px solid var(--border);
            z-index: 100;
        }

        /* Brand mark */
        .nav-brand {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.1rem;
            color: #fff;
            margin-bottom: 20px;
            flex-shrink: 0;
            box-shadow: 0 0 0 3px rgba(98,100,167,.25);
        }

        /* Nav items */
        .nav-items {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            flex: 1;
            width: 100%;
            padding: 0 8px;
        }

        .nav-link {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 10px 4px;
            border-radius: var(--radius);
            text-decoration: none;
            color: var(--text-2);
            font-size: .6rem;
            font-weight: 500;
            letter-spacing: .02em;
            text-transform: uppercase;
            transition: background .15s, color .15s;
            position: relative;
            cursor: pointer;
            border: none;
            background: none;
        }

        .nav-link svg { flex-shrink: 0; }

        .nav-link:hover {
            background: rgba(255,255,255,.06);
            color: var(--text-1);
        }

        .nav-link.active {
            background: var(--accent-dim);
            color: var(--accent-h);
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60%;
            background: var(--accent-h);
            border-radius: 0 3px 3px 0;
        }

        /* Danger nav */
        .nav-link.danger { color: var(--text-3); }
        .nav-link.danger:hover { background: rgba(224,91,91,.12); color: var(--danger); }

        /* Nav spacer */
        .nav-spacer { flex: 1; }

        /* Profile avatar in nav */
        .nav-profile {
            position: relative;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .85rem;
            color: #fff;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color .15s;
            margin-top: 8px;
        }

        .nav-profile:hover { border-color: var(--accent-h); }

        .nav-profile .presence {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--online);
            border: 2px solid var(--nav-bg);
        }

        /* ── Main content ─────────────────────────── */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: var(--content-bg);
        }

        /* Top bar (desktop) */
        .topbar {
            height: 52px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 24px;
            gap: 16px;
            flex-shrink: 0;
        }

        .topbar-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-1);
            flex: 1;
        }

        /* Top-bar profile chip */
        .topbar-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px 10px 5px 5px;
            border-radius: 24px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            cursor: pointer;
            transition: border-color .15s;
        }

        .topbar-profile:hover { border-color: var(--accent); }

        .topbar-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .72rem;
            color: #fff;
            position: relative;
        }

        .topbar-avatar .presence {
            position: absolute;
            bottom: -1px;
            right: -1px;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--online);
            border: 2px solid var(--surface);
        }

        .topbar-name {
            font-size: .8125rem;
            font-weight: 600;
            color: var(--text-1);
        }

        /* Scrollable page area */
        .page-area {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* For chat page: no padding, full height */
        .page-area.no-pad {
            overflow: hidden;
            padding: 0;
        }

        .page-area:not(.no-pad) {
            padding: 28px;
        }

        .page-area::-webkit-scrollbar { width: 5px; }
        .page-area::-webkit-scrollbar-track { background: transparent; }
        .page-area::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

        /* ── Mobile top bar ───────────────────────── */
        .mobile-topbar {
            display: none;
            height: 56px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            flex-shrink: 0;
        }

        .mobile-topbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 1rem;
        }

        .mobile-brand-dot {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: .85rem;
        }

        /* Mobile bottom nav */
        .mobile-bottomnav {
            display: none;
            height: 60px;
            background: var(--surface);
            border-top: 1px solid var(--border);
            align-items: center;
            justify-content: space-around;
            flex-shrink: 0;
        }

        .mob-nav-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            padding: 6px 16px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-2);
            font-size: .6rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .03em;
            transition: color .15s;
            background: none;
            border: none;
            cursor: pointer;
        }

        .mob-nav-btn.active { color: var(--accent-h); }
        .mob-nav-btn:hover { color: var(--text-1); }
        .mob-nav-btn.danger:hover { color: var(--danger); }

        /* ── Responsive ───────────────────────────── */
        @media (max-width: 768px) {
            .nav-rail       { display: none; }
            .topbar         { display: none; }
            .mobile-topbar  { display: flex; }
            .mobile-bottomnav { display: flex; }
            .page-area:not(.no-pad) { padding: 16px; }
        }
    </style>
</head>

<body>

<div class="app-shell">

    {{-- ═══════════════════ DESKTOP NAV RAIL ═══════════════════ --}}
    <aside class="nav-rail">

        <div class="nav-brand">B</div>

        <nav class="nav-items">

            @auth
                @if(auth()->user()->is_admin)

                    <a href="{{ route('dashboard') }}"
                       class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                       title="Dashboard">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                            <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                            <rect x="3" y="14" width="7" height="7" rx="1.5"/>
                            <rect x="14" y="14" width="7" height="7" rx="1.5"/>
                        </svg>
                        Dashboard
                    </a>

                    <a href="{{ route('users.index') }}"
                       class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                       title="Users">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        Users
                    </a>

                @endif

                <a href="{{ route('chat.index') }}"
                   class="nav-link {{ request()->routeIs('chat.*') ? 'active' : '' }}"
                   title="Chat">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    Chat
                </a>

            @endauth

        </nav>

        <div class="nav-spacer"></div>

        {{-- Logout --}}
        @auth
        <form method="POST" action="{{ route('logout') }}" style="width:100%;padding:0 8px;">
            @csrf
            <button type="submit" class="nav-link danger" style="width:100%" title="Logout">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Logout
            </button>
        </form>

        {{-- Profile avatar --}}
        <div class="nav-profile" title="{{ auth()->user()->name }}">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            <span class="presence"></span>
        </div>
        @endauth

    </aside>

    {{-- ═══════════════════ CONTENT COLUMN ═══════════════════ --}}
    <div class="main-content">

        {{-- Mobile top bar --}}
        <div class="mobile-topbar">
            <div class="mobile-topbar-brand">
                <div class="mobile-brand-dot">B</div>
                Baseline Chat
            </div>
            @auth
            <div class="topbar-profile">
                <div class="topbar-avatar">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    <span class="presence"></span>
                </div>
                <span class="topbar-name">{{ auth()->user()->name }}</span>
            </div>
            @endauth
        </div>

        {{-- Desktop top bar --}}
        <div class="topbar">
            <span class="topbar-title">
                @if(request()->routeIs('dashboard'))      Dashboard
                @elseif(request()->routeIs('users.*'))    Users
                @elseif(request()->routeIs('chat.*'))     Chat
                @else                                      Baseline Chat
                @endif
            </span>

            @auth
            <div class="topbar-profile">
                <div class="topbar-avatar">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    <span class="presence"></span>
                </div>
                <span class="topbar-name">{{ auth()->user()->name }}</span>
            </div>
            @endauth
        </div>

        {{-- Page slot --}}
        <div class="page-area {{ request()->routeIs('chat.*') ? 'no-pad' : '' }}">
            {{ $slot }}
        </div>

        {{-- Mobile bottom nav --}}
        <nav class="mobile-bottomnav">
            @auth
                @if(auth()->user()->is_admin)
                    <a href="{{ route('dashboard') }}"
                       class="mob-nav-btn {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                            <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                            <rect x="3" y="14" width="7" height="7" rx="1.5"/>
                            <rect x="14" y="14" width="7" height="7" rx="1.5"/>
                        </svg>
                        Dash
                    </a>

                    <a href="{{ route('users.index') }}"
                       class="mob-nav-btn {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                        </svg>
                        Users
                    </a>
                @endif

                <a href="{{ route('chat.index') }}"
                   class="mob-nav-btn {{ request()->routeIs('chat.*') ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    Chat
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="mob-nav-btn danger">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Logout
                    </button>
                </form>
            @endauth
        </nav>

    </div>

</div>

@livewireScripts
</body>
</html>