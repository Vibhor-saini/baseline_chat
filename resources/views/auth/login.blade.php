<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In — {{ config('app.name', 'Baseline Chat') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:          #13131a;
            --panel:       #1e1e2a;
            --surface:     #252535;
            --border:      #32324a;
            --accent:      #6264a7;
            --accent-h:    #7b7dd6;
            --accent-dim:  rgba(98,100,167,.18);
            --accent-glow: rgba(98,100,167,.35);
            --text-1:      #f0f0f8;
            --text-2:      #8888aa;
            --text-3:      #4a4a6a;
            --danger:      #e05b5b;
            --online:      #57c75a;
        }

        html, body {
            height: 100%;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text-1);
            -webkit-font-smoothing: antialiased;
        }

        /* ── Animated background grid ── */
        .bg-grid {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(98,100,167,.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(98,100,167,.06) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 40%, transparent 100%);
            pointer-events: none;
            z-index: 0;
        }

        /* Glowing orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
            animation: orb-float 8s ease-in-out infinite;
        }

        .orb-1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(98,100,167,.22) 0%, transparent 70%);
            top: -150px; left: -100px;
            animation-delay: 0s;
        }

        .orb-2 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(123,125,214,.15) 0%, transparent 70%);
            bottom: -100px; right: -80px;
            animation-delay: -4s;
        }

        @keyframes orb-float {
            0%, 100% { transform: translateY(0px) scale(1); }
            50%       { transform: translateY(-30px) scale(1.05); }
        }

        /* ── Page layout ── */
        .page {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        /* ── Login card ── */
        .card {
            width: 100%;
            max-width: 420px;
            animation: cardIn .4s cubic-bezier(.22,1,.36,1) both;
        }

        @keyframes cardIn {
            from { opacity:0; transform: translateY(24px) scale(.97); }
            to   { opacity:1; transform: translateY(0) scale(1); }
        }

        /* Brand */
        .brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 32px;
            text-align: center;
        }

        .brand-logo {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1.5rem;
            color: #fff;
            margin-bottom: 16px;
            box-shadow: 0 0 0 4px var(--accent-dim), 0 8px 32px var(--accent-glow);
            position: relative;
        }

        .brand-logo::after {
            content: '';
            position: absolute;
            inset: -6px;
            border-radius: 20px;
            border: 1px solid rgba(98,100,167,.3);
            animation: pulse-ring 2.5s ease infinite;
        }

        @keyframes pulse-ring {
            0%, 100% { opacity:.6; transform: scale(1); }
            50%       { opacity:.2; transform: scale(1.06); }
        }

        .brand-name {
            font-size: 1.375rem;
            font-weight: 800;
            color: var(--text-1);
            letter-spacing: -.01em;
        }

        .brand-sub {
            font-size: .8125rem;
            color: var(--text-2);
            margin-top: 4px;
        }

        /* Card body */
        .card-body {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            backdrop-filter: blur(12px);
            box-shadow: 0 24px 64px rgba(0,0,0,.5), 0 0 0 1px rgba(255,255,255,.04) inset;
        }

        .card-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-1);
            margin-bottom: 4px;
        }

        .card-sub {
            font-size: .8125rem;
            color: var(--text-2);
            margin-bottom: 24px;
        }

        /* Session status */
        .session-status {
            background: rgba(87,199,90,.1);
            border: 1px solid rgba(87,199,90,.3);
            color: #57c75a;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: .8125rem;
            font-weight: 500;
            margin-bottom: 20px;
        }

        /* Fields */
        .field { margin-bottom: 18px; }

        .field-label {
            display: block;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--text-2);
            margin-bottom: 7px;
        }

        .input-wrap { position: relative; }

        .input-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-3);
            pointer-events: none;
            transition: color .15s;
        }

        .field-input {
            width: 100%;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px 14px 12px 40px;
            color: var(--text-1);
            font-size: .9rem;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }

        .field-input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-dim);
        }

        .field-input:focus + .input-icon,
        .input-wrap:focus-within .input-icon {
            color: var(--accent-h);
        }

        /* Flip icon order for left placement */
        .input-wrap .input-icon { z-index: 1; }

        .field-input::placeholder { color: var(--text-3); }

        /* Error */
        .field-error {
            font-size: .78rem;
            color: var(--danger);
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Remember + forgot row */
        .meta-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
            gap: 8px;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: .8125rem;
            color: var(--text-2);
            user-select: none;
        }

        .remember-label input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--accent);
            cursor: pointer;
        }

        .forgot-link {
            font-size: .8rem;
            color: var(--accent-h);
            text-decoration: none;
            transition: color .15s;
            white-space: nowrap;
        }

        .forgot-link:hover { color: #fff; text-decoration: underline; }

        /* Submit */
        .submit-btn {
            width: 100%;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 13px;
            font-size: .9375rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background .15s, transform .1s, box-shadow .15s;
            box-shadow: 0 4px 16px var(--accent-glow);
            letter-spacing: .01em;
        }

        .submit-btn:hover {
            background: var(--accent-h);
            transform: translateY(-1px);
            box-shadow: 0 6px 24px var(--accent-glow);
        }

        .submit-btn:active { transform: translateY(0); }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 22px 0;
            color: var(--text-3);
            font-size: .75rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* Footer */
        .card-footer {
            text-align: center;
            margin-top: 22px;
            font-size: .78rem;
            color: var(--text-3);
        }

        /* Status indicators */
        .status-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 20px;
            font-size: .72rem;
            color: var(--text-3);
        }

        .status-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--online);
            animation: blink 2s ease infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50%       { opacity: .4; }
        }
    </style>
</head>

<body>

    <div class="bg-grid"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="page">
        <div class="card">

            {{-- Brand --}}
            <div class="brand">
                <div class="brand-logo">B</div>
                <div class="brand-name">{{ config('app.name', 'Baseline Chat') }}</div>
                <div class="brand-sub">Real-time team communication</div>
            </div>

            {{-- Card --}}
            <div class="card-body">

                <div class="card-title">Welcome back!</div>
                <div class="card-sub">Sign in to your account to continue</div>

                {{-- Session status --}}
                @if (session('status'))
                    <div class="session-status">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    {{-- Email --}}
                    <div class="field">
                        <label class="field-label" for="email">Email Address</label>
                        <div class="input-wrap">
                            <svg class="input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            <input
                                id="email"
                                class="field-input"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="you@company.com"
                                required
                                autofocus
                                autocomplete="username"
                            >
                        </div>
                        @if ($errors->has('email'))
                            <p class="field-error">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                {{ $errors->first('email') }}
                            </p>
                        @endif
                    </div>

                    {{-- Password --}}
                    <div class="field">
                        <label class="field-label" for="password">Password</label>
                        <div class="input-wrap">
                            <svg class="input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <input
                                id="password"
                                class="field-input"
                                type="password"
                                name="password"
                                placeholder="Your password"
                                required
                                autocomplete="current-password"
                            >
                        </div>
                        @if ($errors->has('password'))
                            <p class="field-error">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                {{ $errors->first('password') }}
                            </p>
                        @endif
                    </div>

                    {{-- Remember + Forgot --}}
                    <div class="meta-row">
                        <label class="remember-label" for="remember_me">
                            <input id="remember_me" type="checkbox" name="remember">
                            Remember me
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="forgot-link">
                                Forgot password?
                            </a>
                        @endif
                    </div>

                    {{-- Submit --}}
                    <button type="submit" class="submit-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                        Sign In
                    </button>

                </form>

            </div>

            {{-- Footer status --}}
            <div class="status-bar">
                <span class="status-dot"></span>
                All systems operational
            </div>

        </div>
    </div>

</body>
</html>