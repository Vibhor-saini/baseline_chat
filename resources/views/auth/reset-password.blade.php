<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset Password — {{ config('app.name', 'Baseline Chat') }}</title>
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
            background: var(--bg); color: var(--text-1);
            -webkit-font-smoothing: antialiased;
        }

        .bg-grid {
            position:fixed;inset:0;
            background-image:
                linear-gradient(rgba(98,100,167,.06) 1px,transparent 1px),
                linear-gradient(90deg,rgba(98,100,167,.06) 1px,transparent 1px);
            background-size:48px 48px;
            mask-image:radial-gradient(ellipse 80% 80% at 50% 50%,black 40%,transparent 100%);
            pointer-events:none;z-index:0;
        }

        .orb { position:fixed;border-radius:50%;filter:blur(80px);pointer-events:none;z-index:0;animation:orb-float 8s ease-in-out infinite; }
        .orb-1 { width:500px;height:500px;background:radial-gradient(circle,rgba(98,100,167,.22) 0%,transparent 70%);top:-150px;left:-100px;animation-delay:0s; }
        .orb-2 { width:400px;height:400px;background:radial-gradient(circle,rgba(123,125,214,.15) 0%,transparent 70%);bottom:-100px;right:-80px;animation-delay:-4s; }

        @keyframes orb-float { 0%,100%{transform:translateY(0) scale(1);}50%{transform:translateY(-30px) scale(1.05);} }

        .page {
            position:relative;z-index:1;min-height:100vh;
            display:flex;align-items:center;justify-content:center;padding:24px 16px;
        }

        .card { width:100%;max-width:420px;animation:cardIn .4s cubic-bezier(.22,1,.36,1) both; }

        @keyframes cardIn { from{opacity:0;transform:translateY(24px) scale(.97);}to{opacity:1;transform:translateY(0) scale(1);} }

        .brand { display:flex;flex-direction:column;align-items:center;margin-bottom:32px;text-align:center; }
        .brand-logo {
            width:56px;height:56px;border-radius:16px;background:var(--accent);
            display:flex;align-items:center;justify-content:center;
            font-weight:900;font-size:1.5rem;color:#fff;margin-bottom:16px;
            box-shadow:0 0 0 4px var(--accent-dim),0 8px 32px var(--accent-glow);position:relative;
        }
        .brand-logo::after { content:'';position:absolute;inset:-6px;border-radius:20px;border:1px solid rgba(98,100,167,.3);animation:pulse-ring 2.5s ease infinite; }
        @keyframes pulse-ring { 0%,100%{opacity:.6;transform:scale(1);}50%{opacity:.2;transform:scale(1.06);} }
        .brand-name { font-size:1.375rem;font-weight:800;color:var(--text-1);letter-spacing:-.01em; }
        .brand-sub  { font-size:.8125rem;color:var(--text-2);margin-top:4px; }

        .card-body {
            background:var(--panel);border:1px solid var(--border);border-radius:20px;
            padding:32px;backdrop-filter:blur(12px);
            box-shadow:0 24px 64px rgba(0,0,0,.5),0 0 0 1px rgba(255,255,255,.04) inset;
        }

        .card-title { font-size:1.125rem;font-weight:700;color:var(--text-1);margin-bottom:4px; }
        .card-sub   { font-size:.8125rem;color:var(--text-2);margin-bottom:24px; }

        .field { margin-bottom:18px; }

        .field-label {
            display:block;font-size:.72rem;font-weight:700;text-transform:uppercase;
            letter-spacing:.07em;color:var(--text-2);margin-bottom:7px;
        }

        .input-wrap { position:relative; }

        .input-icon {
            position:absolute;left:13px;top:50%;transform:translateY(-50%);
            color:var(--text-3);pointer-events:none;transition:color .15s;z-index:1;
        }

        .field-input {
            width:100%;background:var(--surface);border:1px solid var(--border);
            border-radius:10px;padding:12px 40px 12px 40px;color:var(--text-1);
            font-size:.9rem;outline:none;transition:border-color .15s,box-shadow .15s;
        }
        .field-input:focus { border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-dim); }
        .input-wrap:focus-within .input-icon { color:var(--accent-h); }
        .field-input::placeholder { color:var(--text-3); }

        /* Toggle password visibility button */
        .toggle-pw {
            position:absolute;right:12px;top:50%;transform:translateY(-50%);
            background:none;border:none;cursor:pointer;color:var(--text-3);
            padding:4px;transition:color .15s;
        }
        .toggle-pw:hover { color:var(--accent-h); }

        .field-error {
            font-size:.78rem;color:var(--danger);margin-top:5px;
            display:flex;align-items:center;gap:5px;
        }

        /* Password strength bar */
        .strength-bar {
            margin-top:8px;height:3px;border-radius:2px;
            background:var(--border);overflow:hidden;
        }
        .strength-fill {
            height:100%;border-radius:2px;width:0;
            transition:width .3s,background .3s;
        }
        .strength-label {
            font-size:.7rem;color:var(--text-3);margin-top:4px;
        }

        .submit-btn {
            width:100%;background:var(--accent);color:#fff;border:none;border-radius:10px;
            padding:13px;font-size:.9375rem;font-weight:700;cursor:pointer;
            display:flex;align-items:center;justify-content:center;gap:8px;
            transition:background .15s,transform .1s,box-shadow .15s;
            box-shadow:0 4px 16px var(--accent-glow);letter-spacing:.01em;
        }
        .submit-btn:hover { background:var(--accent-h);transform:translateY(-1px);box-shadow:0 6px 24px var(--accent-glow); }
        .submit-btn:active { transform:translateY(0); }

        .back-link {
            display:block;text-align:center;margin-top:18px;
            font-size:.8125rem;color:var(--text-2);text-decoration:none;transition:color .15s;
        }
        .back-link:hover { color:var(--text-1); }

        .status-bar { display:flex;align-items:center;justify-content:center;gap:6px;margin-top:20px;font-size:.72rem;color:var(--text-3); }
        .status-dot { width:6px;height:6px;border-radius:50%;background:var(--online);animation:blink 2s ease infinite; }
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

                <div class="card-title">Set a new password</div>
                <div class="card-sub">Choose something strong that you haven't used before.</div>

                <form method="POST" action="{{ route('password.store') }}">
                    @csrf

                    {{-- Token --}}
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    {{-- Email --}}
                    <div class="field">
                        <label class="field-label" for="email">Email Address</label>
                        <div class="input-wrap">
                            <svg class="input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <input
                                id="email"
                                class="field-input"
                                type="email"
                                name="email"
                                value="{{ old('email', $request->email) }}"
                                placeholder="you@company.com"
                                required autofocus autocomplete="username"
                                style="padding-right:14px;"
                            >
                        </div>
                        @if ($errors->has('email'))
                            <p class="field-error">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                {{ $errors->first('email') }}
                            </p>
                        @endif
                    </div>

                    {{-- New Password --}}
                    <div class="field">
                        <label class="field-label" for="password">New Password</label>
                        <div class="input-wrap">
                            <svg class="input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <input
                                id="password"
                                class="field-input"
                                type="password"
                                name="password"
                                placeholder="New password"
                                required autocomplete="new-password"
                                oninput="checkStrength(this.value)"
                            >
                            <button type="button" class="toggle-pw" onclick="toggleVisibility('password', this)" title="Show/hide password">
                                <svg id="eye-password" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
                        <div class="strength-label" id="strength-label"></div>
                        <p class="field-error" id="same-pass-error" style="display:none;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            New password cannot be the same as your current password.
                        </p>
                        @if ($errors->has('password'))
                            <p class="field-error">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                {{ $errors->first('password') }}
                            </p>
                        @endif
                    </div>

                    {{-- Confirm Password --}}
                    <div class="field">
                        <label class="field-label" for="password_confirmation">Confirm Password</label>
                        <div class="input-wrap">
                            <svg class="input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                            <input
                                id="password_confirmation"
                                class="field-input"
                                type="password"
                                name="password_confirmation"
                                placeholder="Confirm new password"
                                required autocomplete="new-password"
                                oninput="checkConfirm()"
                            >
                            <button type="button" class="toggle-pw" onclick="toggleVisibility('password_confirmation', this)" title="Show/hide password">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <p class="field-error" id="confirm-error" style="display:none;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            Passwords do not match.
                        </p>
                        @if ($errors->has('password_confirmation'))
                            <p class="field-error">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                {{ $errors->first('password_confirmation') }}
                            </p>
                        @endif
                    </div>

                    <button type="submit" class="submit-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Reset Password
                    </button>

                </form>

                <a href="{{ route('login') }}" class="back-link">← Back to sign in</a>

            </div>

            <div class="status-bar">
                <span class="status-dot"></span>
                All systems operational
            </div>

        </div>
    </div>

    <script>
        function toggleVisibility(fieldId, btn) {
            const input = document.getElementById(fieldId);
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.querySelector('svg').style.opacity = isHidden ? '0.4' : '1';
        }

        function checkConfirm() {
            const pw   = document.getElementById('password').value;
            const conf = document.getElementById('password_confirmation').value;
            const err  = document.getElementById('confirm-error');
            const confInput = document.getElementById('password_confirmation');
            if (conf && pw !== conf) {
                err.style.display = 'flex';
                confInput.style.borderColor = 'var(--danger)';
            } else {
                err.style.display = 'none';
                confInput.style.borderColor = '';
            }
        }

        // Also check on password field change
        document.getElementById('password').addEventListener('input', checkConfirm);

        // Check if new password same as old — real-time AJAX
        let samePassTimer = null;
        const pwInput     = document.getElementById('password');
        const emailInput  = document.getElementById('email');

        pwInput.addEventListener('input', function() {
            clearTimeout(samePassTimer);
            hideSamePassError();
            samePassTimer = setTimeout(() => checkSamePassword(), 500);
        });

        function checkSamePassword() {
            const pw    = pwInput.value;
            const email = emailInput.value;
            if (!pw || pw.length < 6) return;

            fetch('{{ route("password.check.same") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ email, password: pw }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.same) {
                    showSamePassError();
                } else {
                    hideSamePassError();
                }
            });
        }

        function showSamePassError() {
            const err = document.getElementById('same-pass-error');
            err.style.display = 'flex';
            pwInput.style.borderColor = 'var(--danger)';
        }

        function hideSamePassError() {
            const err = document.getElementById('same-pass-error');
            err.style.display = 'none';
            pwInput.style.borderColor = '';
        }

        // Block submit if same password
        document.querySelector('form').addEventListener('submit', function(e) {
            const pw   = document.getElementById('password').value;
            const conf = document.getElementById('password_confirmation').value;
            if (pw !== conf) {
                e.preventDefault();
                document.getElementById('confirm-error').style.display = 'flex';
                document.getElementById('password_confirmation').style.borderColor = 'var(--danger)';
            }
            if (document.getElementById('same-pass-error').style.display === 'flex') {
                e.preventDefault();
            }
        });

        function checkStrength(val) {
            const fill  = document.getElementById('strength-fill');
            const label = document.getElementById('strength-label');
            if (!val) { fill.style.width = '0'; label.textContent = ''; return; }

            let score = 0;
            if (val.length >= 8)               score++;
            if (/[A-Z]/.test(val))             score++;
            if (/[0-9]/.test(val))             score++;
            if (/[^A-Za-z0-9]/.test(val))      score++;

            const map = {
                1: { w:'25%', c:'#e05b5b', t:'Weak' },
                2: { w:'50%', c:'#e0a95b', t:'Fair' },
                3: { w:'75%', c:'#5ba8e0', t:'Good' },
                4: { w:'100%', c:'#57c75a', t:'Strong' },
            };
            const s = map[score] || map[1];
            fill.style.width      = s.w;
            fill.style.background = s.c;
            label.textContent     = s.t;
            label.style.color     = s.c;
        }
    </script>

</body>
</html>
