<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Forgot Password — {{ config('app.name', 'Baseline Chat') }}</title>
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

        .bg-grid {
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(98,100,167,.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(98,100,167,.06) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 40%, transparent 100%);
            pointer-events: none; z-index: 0;
        }

        .orb {
            position: fixed; border-radius: 50%; filter: blur(80px);
            pointer-events: none; z-index: 0;
            animation: orb-float 8s ease-in-out infinite;
        }
        .orb-1 { width:500px;height:500px;background:radial-gradient(circle,rgba(98,100,167,.22) 0%,transparent 70%);top:-150px;left:-100px;animation-delay:0s; }
        .orb-2 { width:400px;height:400px;background:radial-gradient(circle,rgba(123,125,214,.15) 0%,transparent 70%);bottom:-100px;right:-80px;animation-delay:-4s; }

        @keyframes orb-float {
            0%,100% { transform: translateY(0) scale(1); }
            50%      { transform: translateY(-30px) scale(1.05); }
        }

        .page {
            position: relative; z-index: 1; min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 24px 16px;
        }

        .card {
            width: 100%; max-width: 420px;
            animation: cardIn .4s cubic-bezier(.22,1,.36,1) both;
        }

        @keyframes cardIn {
            from { opacity:0; transform: translateY(24px) scale(.97); }
            to   { opacity:1; transform: translateY(0) scale(1); }
        }

        .brand {
            display: flex; flex-direction: column; align-items: center;
            margin-bottom: 32px; text-align: center;
        }

        .brand-logo {
            width:56px;height:56px;border-radius:16px;background:var(--accent);
            display:flex;align-items:center;justify-content:center;
            font-weight:900;font-size:1.5rem;color:#fff;margin-bottom:16px;
            box-shadow:0 0 0 4px var(--accent-dim),0 8px 32px var(--accent-glow);
            position:relative;
        }
        .brand-logo::after {
            content:'';position:absolute;inset:-6px;border-radius:20px;
            border:1px solid rgba(98,100,167,.3);
            animation:pulse-ring 2.5s ease infinite;
        }
        @keyframes pulse-ring {
            0%,100% { opacity:.6;transform:scale(1); }
            50%      { opacity:.2;transform:scale(1.06); }
        }
        .brand-name { font-size:1.375rem;font-weight:800;color:var(--text-1);letter-spacing:-.01em; }
        .brand-sub  { font-size:.8125rem;color:var(--text-2);margin-top:4px; }

        .card-body {
            background:var(--panel);border:1px solid var(--border);border-radius:20px;
            padding:32px;backdrop-filter:blur(12px);
            box-shadow:0 24px 64px rgba(0,0,0,.5),0 0 0 1px rgba(255,255,255,.04) inset;
        }

        .card-title { font-size:1.125rem;font-weight:700;color:var(--text-1);margin-bottom:4px; }
        .card-sub   { font-size:.8125rem;color:var(--text-2);margin-bottom:24px;line-height:1.5; }

        /* Success state */
        .success-state {
            text-align: center;
            padding: 8px 0 16px;
        }
        .success-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 64px; height: 64px; border-radius: 50%;
            background: rgba(87,199,90,.12); border: 1px solid rgba(87,199,90,.3);
            font-size: 28px; margin-bottom: 18px;
            animation: pop .4s cubic-bezier(.22,1,.36,1) both;
        }
        @keyframes pop { from{transform:scale(.6);opacity:0;} to{transform:scale(1);opacity:1;} }
        .success-title { font-size: 1.1rem; font-weight: 700; color: #57c75a; margin-bottom: 8px; }
        .success-msg   { font-size: .85rem; color: var(--text-2); line-height: 1.6; margin-bottom: 20px; }
        .success-email { color: var(--text-1); font-weight: 600; }

        .alert-success {
            background:rgba(87,199,90,.1);border:1px solid rgba(87,199,90,.3);
            color:#57c75a;padding:10px 14px;border-radius:10px;
            font-size:.8125rem;font-weight:500;margin-bottom:20px;
        }

        .field { margin-bottom: 18px; }

        .field-label {
            display:block;font-size:.72rem;font-weight:700;
            text-transform:uppercase;letter-spacing:.07em;
            color:var(--text-2);margin-bottom:7px;
        }

        .input-wrap { position: relative; }

        .input-icon {
            position:absolute;left:13px;top:50%;transform:translateY(-50%);
            color:var(--text-3);pointer-events:none;transition:color .15s;
        }

        .field-input {
            width:100%;background:var(--surface);border:1px solid var(--border);
            border-radius:10px;padding:12px 14px 12px 40px;color:var(--text-1);
            font-size:.9rem;outline:none;transition:border-color .15s,box-shadow .15s;
        }
        .field-input:focus { border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-dim); }
        .input-wrap:focus-within .input-icon { color:var(--accent-h); }
        .field-input::placeholder { color:var(--text-3); }
        .field-input-error { border-color: var(--danger) !important; }

        .field-error {
            font-size:.78rem;color:var(--danger);margin-top:5px;
            display:flex;align-items:center;gap:5px;
        }

        .submit-btn {
            width:100%;background:var(--accent);color:#fff;border:none;
            border-radius:10px;padding:13px;font-size:.9375rem;font-weight:700;
            cursor:pointer;display:flex;align-items:center;justify-content:center;
            gap:8px;transition:background .15s,transform .1s,box-shadow .15s;
            box-shadow:0 4px 16px var(--accent-glow);letter-spacing:.01em;
        }
        .submit-btn:hover { background:var(--accent-h);transform:translateY(-1px);box-shadow:0 6px 24px var(--accent-glow); }
        .submit-btn:active { transform:translateY(0); }

        .back-link {
            display:block;text-align:center;margin-top:18px;
            font-size:.8125rem;color:var(--text-2);text-decoration:none;
            transition:color .15s;
        }
        .back-link:hover { color:var(--text-1); }

        .status-bar {
            display:flex;align-items:center;justify-content:center;
            gap:6px;margin-top:20px;font-size:.72rem;color:var(--text-3);
        }
        .status-dot {
            width:6px;height:6px;border-radius:50%;background:var(--online);
            animation:blink 2s ease infinite;
        }
        @keyframes blink { 0%,100%{opacity:1;}50%{opacity:.4;} }
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

            <div class="card-body">

                @if (session('status') === 'success')

                    {{-- ✅ SUCCESS STATE — form hidden, email sent confirmation --}}
                    <div class="success-state">
                        <div class="success-icon">✉️</div>
                        <div class="success-title">Check your inbox!</div>
                        <p class="success-msg">
                            We sent a password reset link to<br>
                            <span class="success-email">{{ old('email', request('email')) }}</span>
                            <br><br>
                            Click the link in the email to set a new password. It expires in <strong style="color:var(--text-1);">60 minutes</strong>.
                        </p>
                        <p style="font-size:.78rem;color:var(--text-3);margin-bottom:18px;">
                            Didn't get it? Check your spam folder or
                            <a href="{{ route('password.request') }}" style="color:var(--accent-h);text-decoration:none;">try again</a>.
                        </p>
                    </div>

                @else

                    {{-- FORM STATE --}}
                    <div class="card-title">Forgot your password?</div>
                    <div class="card-sub">
                        Enter the email address linked to your account and we'll send you a reset link.
                    </div>

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="field">
                            <label class="field-label" for="email">Email Address</label>
                            <div class="input-wrap">
                                <svg class="input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                                <input
                                    id="email"
                                    class="field-input {{ $errors->has('email') ? 'field-input-error' : '' }}"
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    placeholder="you@company.com"
                                    required autofocus autocomplete="username"
                                >
                            </div>
                            @if ($errors->has('email'))
                                <p class="field-error">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    {{ $errors->first('email') }}
                                </p>
                            @endif
                        </div>

                        <button type="submit" class="submit-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            Send Reset Link
                        </button>

                    </form>

                @endif

                <a href="{{ route('login') }}" class="back-link">
                    ← Back to sign in
                </a>

            </div>

            <div class="status-bar">
                <span class="status-dot"></span>
                All systems operational
            </div>

        </div>
    </div>

</body>
</html>
