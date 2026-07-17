<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Account Disabled — {{ config('app.name', 'Baseline Chat') }}</title>
    @vite(['resources/css/app.css'])
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
            --text-1:      #f0f0f8;
            --text-2:      #8888aa;
            --text-3:      #4a4a6a;
            --danger:      #e05b5b;
            --danger-dim:  rgba(224,91,91,.12);
        }

        html, body {
            height: 100%;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text-1);
            -webkit-font-smoothing: antialiased;
        }

        .page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        /* Brand */
        .brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            margin-bottom: 32px;
        }

        .brand-icon {
            width: 54px; height: 54px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--accent), #5e3de8);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 1.4rem; color: #fff;
            box-shadow: 0 0 28px rgba(98,100,167,.4);
        }

        .brand-name {
            font-size: 1.3rem; font-weight: 800;
            color: var(--text-1); letter-spacing: -.02em;
        }

        /* Card */
        .card {
            width: 100%; max-width: 420px;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 36px 32px;
            box-shadow: 0 24px 60px rgba(0,0,0,.5);
            text-align: center;
        }

        .icon-wrap {
            width: 64px; height: 64px;
            border-radius: 50%;
            background: var(--danger-dim);
            border: 1px solid rgba(224,91,91,.3);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            color: var(--danger);
        }

        .card h1 {
            font-size: 1.25rem; font-weight: 800;
            color: var(--text-1); margin-bottom: 10px;
        }

        .card p {
            font-size: .9rem; color: var(--text-2);
            line-height: 1.6; margin-bottom: 28px;
        }

        .card p strong { color: var(--text-1); }

        .back-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 24px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-2);
            font-size: .875rem; font-weight: 600;
            text-decoration: none;
            transition: background .15s, color .15s, border-color .15s;
        }

        .back-btn:hover {
            background: rgba(255,255,255,.06);
            color: var(--text-1);
            border-color: rgba(255,255,255,.15);
        }
    </style>
</head>
<body>
<div class="page">

    <div class="brand">
        <div class="brand-icon">B</div>
        <span class="brand-name">{{ config('app.name', 'Baseline Chat') }}</span>
    </div>

    <div class="card">
        <div class="icon-wrap" aria-hidden="true">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
            </svg>
        </div>

        <h1>Account Disabled</h1>

        <p>
            Your account has been <strong>disabled by an administrator</strong>.<br>
            Please contact your admin to regain access.
        </p>

        <a href="{{ route('login') }}" class="back-btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 5l-7 7 7 7"/>
            </svg>
            Back to Login
        </a>
    </div>

</div>
</body>
</html>
