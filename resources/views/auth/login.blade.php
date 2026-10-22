<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In — {{ config('app.name', 'Baseline Chat') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --accent:      #6264a7;
            --accent-h:    #7b7dd6;
            --accent-dim:  rgba(98,100,167,.15);
            --accent-glow: rgba(98,100,167,.35);
            --text-1:      #ffffff;
            --text-2:      #c8c8d4;
            --text-3:      #8a8a9a;
            --danger:      #f85149;
            --online:      #92c353;
            --border:      rgba(255,255,255,.1);
        }

        html, body {
            height: 100%;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: #0e0e16;
            color: var(--text-1);
            -webkit-font-smoothing: antialiased;
        }

        /* ══════════════════════════════
           FULL SPLIT LAYOUT
        ══════════════════════════════ */
        .split-layout {
            min-height: 100vh;
            display: flex;
        }

        /* ── LEFT PANEL ── */
        .left-panel {
            flex: 1;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #0a0a14 0%, #0f0f22 50%, #13102a 100%);
            display: flex;
            flex-direction: column;
            padding: 40px 52px;
        }

        /* Animated mesh gradient background */
        .left-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 20%, rgba(98,100,167,.22) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 80% 80%, rgba(123,125,214,.14) 0%, transparent 60%),
                radial-gradient(ellipse 50% 40% at 50% 50%, rgba(80,60,180,.08) 0%, transparent 70%);
            pointer-events: none;
            animation: meshShift 12s ease-in-out infinite alternate;
        }

        @keyframes meshShift {
            0%   { opacity: .8; transform: scale(1); }
            100% { opacity: 1;  transform: scale(1.05); }
        }

        /* Grid overlay */
        .left-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(98,100,167,.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(98,100,167,.06) 1px, transparent 1px);
            background-size: 52px 52px;
            pointer-events: none;
        }

        /* Floating particles */
        .particles {
            position: absolute;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            background: var(--accent-h);
            opacity: 0;
            animation: particleFloat linear infinite;
        }

        @keyframes particleFloat {
            0%   { opacity: 0; transform: translateY(0) scale(0); }
            10%  { opacity: .6; }
            90%  { opacity: .2; }
            100% { opacity: 0; transform: translateY(-120vh) scale(1); }
        }

        .left-content {
            position: relative;
            z-index: 2;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* ── Company brand top ── */
        .company-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 56px;
        }

        .company-logo {
            width: 44px; height: 44px;
            border-radius: 11px;
            background: linear-gradient(135deg, #6264a7, #7b7dd6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1.2rem;
            color: #fff;
            box-shadow: 0 4px 16px var(--accent-glow);
            flex-shrink: 0;
        }

        .company-info-name {
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: -.01em;
        }

        .company-info-tag {
            font-size: .68rem;
            color: var(--accent-h);
            font-weight: 500;
            letter-spacing: .06em;
            text-transform: uppercase;
            margin-top: 2px;
        }

        /* ── Hero section ── */
        .hero {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-width: 520px;
        }

        .live-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(146,195,83,.12);
            border: 1px solid rgba(146,195,83,.3);
            color: #92c353;
            font-size: .7rem;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: 99px;
            margin-bottom: 28px;
            width: fit-content;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .live-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #92c353;
            animation: blink 1.8s ease infinite;
            box-shadow: 0 0 6px #92c353;
        }

        @keyframes blink {
            0%,100% { opacity:1; transform:scale(1); }
            50%      { opacity:.4; transform:scale(.8); }
        }

        .hero-title {
            font-size: 2.8rem;
            font-weight: 900;
            line-height: 1.15;
            letter-spacing: -.03em;
            color: #fff;
            margin-bottom: 22px;
        }

        .hero-title .gradient-text {
            background: linear-gradient(90deg, #7b7dd6 0%, #a89cf7 50%, #c8b8ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-desc {
            font-size: .95rem;
            color: var(--text-2);
            line-height: 1.75;
            margin-bottom: 40px;
        }

        /* ── Feature pills ── */
        .feature-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 44px;
        }

        .feature-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.1);
            padding: 8px 16px;
            border-radius: 99px;
            font-size: .8rem;
            color: var(--text-2);
            font-weight: 500;
            transition: all .2s;
        }

        .feature-pill:hover {
            background: var(--accent-dim);
            border-color: rgba(98,100,167,.4);
            color: #fff;
        }

        .feature-pill svg { color: var(--accent-h); flex-shrink: 0; }

        /* ── Chat preview mockup ── */
        .chat-preview {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 16px;
            padding: 20px;
            backdrop-filter: blur(10px);
            margin-bottom: 40px;
            animation: previewFloat 6s ease-in-out infinite;
        }

        @keyframes previewFloat {
            0%,100% { transform: translateY(0); }
            50%      { transform: translateY(-6px); }
        }

        .preview-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 14px;
            border-bottom: 1px solid rgba(255,255,255,.07);
            margin-bottom: 14px;
        }

        .preview-avatar {
            width: 32px; height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .75rem;
            color: #fff;
            flex-shrink: 0;
        }

        .preview-name { font-size: .82rem; font-weight: 600; color: #fff; }
        .preview-status { font-size: .7rem; color: #92c353; }

        .preview-messages { display: flex; flex-direction: column; gap: 8px; }

        .preview-msg {
            display: flex;
            gap: 8px;
            align-items: flex-end;
        }

        .preview-msg.mine { flex-direction: row-reverse; }

        .preview-msg-avatar {
            width: 24px; height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .6rem;
            color: #fff;
            flex-shrink: 0;
        }

        .preview-bubble {
            padding: 8px 14px;
            border-radius: 14px;
            font-size: .78rem;
            color: #fff;
            max-width: 220px;
            line-height: 1.4;
        }

        .preview-bubble.theirs {
            background: rgba(255,255,255,.08);
            border-bottom-left-radius: 4px;
        }

        .preview-bubble.mine {
            background: linear-gradient(135deg, #6264a7, #5558a1);
            border-bottom-right-radius: 4px;
        }

        .preview-time {
            font-size: .6rem;
            color: var(--text-3);
            margin-top: 2px;
            flex-shrink: 0;
        }

        /* ── Stats row ── */
        .stats-row {
            display: flex;
            gap: 28px;
            padding-top: 28px;
            border-top: 1px solid rgba(255,255,255,.07);
        }

        .stat-val {
            font-size: 1.4rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -.02em;
        }

        .stat-lbl {
            font-size: .7rem;
            color: var(--text-3);
            margin-top: 2px;
            font-weight: 500;
        }

        /* ── Testimonial ── */
        .testimonial {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 14px;
            padding: 20px;
            margin-top: 28px;
        }

        .testimonial-text {
            font-size: .85rem;
            color: var(--text-2);
            line-height: 1.6;
            font-style: italic;
            margin-bottom: 14px;
        }

        .testimonial-text::before { content: '"'; color: var(--accent-h); font-size: 1.2rem; }
        .testimonial-text::after  { content: '"'; color: var(--accent-h); font-size: 1.2rem; }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .testimonial-avatar {
            width: 30px; height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6264a7, #a89cf7);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .7rem;
            color: #fff;
        }

        .testimonial-name { font-size: .8rem; font-weight: 600; color: #fff; }
        .testimonial-role { font-size: .7rem; color: var(--text-3); }

        /* ══════════════════════════════
           RIGHT PANEL — Login form
        ══════════════════════════════ */
        .right-panel {
            width: 460px;
            flex-shrink: 0;
            background: #12121e;
            border-left: 1px solid rgba(255,255,255,.07);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 44px;
            position: relative;
            overflow: hidden;
        }

        .right-panel::before {
            content: '';
            position: absolute;
            bottom: -150px; right: -150px;
            width: 350px; height: 350px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(98,100,167,.12) 0%, transparent 70%);
            pointer-events: none;
        }

        .login-box {
            width: 100%;
            max-width: 340px;
            position: relative;
            z-index: 1;
            animation: slideUp .45s cubic-bezier(.22,1,.36,1) both;
        }

        @keyframes slideUp {
            from { opacity:0; transform: translateY(28px); }
            to   { opacity:1; transform: translateY(0); }
        }

        /* Login header */
        .login-header { text-align: center; margin-bottom: 36px; }

        .login-logo-wrap {
            position: relative;
            width: 60px; height: 60px;
            margin: 0 auto 18px;
        }

        .login-logo {
            width: 60px; height: 60px;
            border-radius: 18px;
            background: linear-gradient(135deg, #6264a7, #7b7dd6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1.6rem;
            color: #fff;
            box-shadow: 0 0 0 3px rgba(98,100,167,.2), 0 8px 28px var(--accent-glow);
        }

        .login-logo-ring {
            position: absolute;
            inset: -6px;
            border-radius: 24px;
            border: 1.5px solid rgba(98,100,167,.35);
            animation: ringPulse 2.5s ease infinite;
        }

        @keyframes ringPulse {
            0%,100% { opacity:.7; transform:scale(1); }
            50%      { opacity:.2; transform:scale(1.07); }
        }

        .login-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -.02em;
            margin-bottom: 6px;
        }

        .login-sub {
            font-size: .82rem;
            color: var(--text-3);
        }

        /* Session status */
        .session-status {
            background: rgba(146,195,83,.1);
            border: 1px solid rgba(146,195,83,.3);
            color: #92c353;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: .82rem;
            font-weight: 500;
            margin-bottom: 20px;
        }

        /* Form fields */
        .field { margin-bottom: 16px; }

        .field-label {
            display: block;
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .09em;
            color: var(--text-3);
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
            z-index: 1;
            transition: color .15s;
        }

        .field-input {
            width: 100%;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 10px;
            padding: 12px 42px 12px 40px;
            color: #fff;
            font-size: .88rem;
            font-family: inherit;
            outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }

        .field-input:focus {
            background: rgba(98,100,167,.1);
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-dim);
        }

        .input-wrap:focus-within .input-icon { color: var(--accent-h); }
        .field-input::placeholder { color: rgba(255,255,255,.25); }

        /* Password toggle */
        .pw-toggle {
            position: absolute;
            right: 11px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 5px;
            cursor: pointer;
            color: var(--text-3);
            display: flex;
            align-items: center;
            border-radius: 5px;
            transition: color .15s, background .15s;
            z-index: 1;
        }

        .pw-toggle:hover {
            color: #fff;
            background: rgba(255,255,255,.08);
        }

        /* Errors */
        .field-error {
            font-size: .75rem;
            color: var(--danger);
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Remember + forgot */
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
            font-size: .8rem;
            color: var(--text-3);
            user-select: none;
        }

        .remember-label input[type="checkbox"] {
            width: 15px; height: 15px;
            accent-color: var(--accent);
            cursor: pointer;
        }

        .forgot-link {
            font-size: .78rem;
            color: var(--accent-h);
            text-decoration: none;
            font-weight: 500;
            transition: color .15s;
            white-space: nowrap;
        }

        .forgot-link:hover { color: #fff; }

        /* Submit button */
        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, #6264a7, #7b7dd6);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 13px;
            font-size: .925rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            transition: all .2s;
            box-shadow: 0 4px 20px var(--accent-glow);
            letter-spacing: .01em;
            position: relative;
            overflow: hidden;
        }

        .submit-btn::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,.1), transparent);
            opacity: 0;
            transition: opacity .2s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px var(--accent-glow);
        }

        .submit-btn:hover::after { opacity: 1; }
        .submit-btn:active { transform: translateY(0); }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
            font-size: .72rem;
            color: var(--text-3);
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,.08);
        }

        /* Security note */
        .security-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: .72rem;
            color: var(--text-3);
            margin-top: 18px;
            padding: 10px;
            background: rgba(255,255,255,.03);
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,.06);
        }

        .security-note svg { color: #92c353; }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 24px;
            font-size: .7rem;
            color: var(--text-3);
            line-height: 1.7;
        }

        .login-footer a {
            color: var(--accent-h);
            text-decoration: none;
        }

        .login-footer a:hover { text-decoration: underline; }

        /* Status bar */
        .status-bar {
            position: absolute;
            bottom: 18px;
            left: 0; right: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: .68rem;
            color: var(--text-3);
        }

        .status-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: #92c353;
            animation: blink 2s ease infinite;
            box-shadow: 0 0 5px #92c353;
        }

        /* Responsive */
        @media (max-width: 960px) {
            .left-panel { display: none; }
            .right-panel { width: 100%; border-left: none; background: #0e0e16; }
        }
    </style>
</head>
<body>

<div class="split-layout">

    {{-- ══════════════════════════════════
         LEFT — Marketing panel
    ══════════════════════════════════ --}}
    <div class="left-panel">

        {{-- Animated particles --}}
        <div class="particles" id="particles"></div>

        <div class="left-content">

            {{-- Company brand --}}
            <div class="company-brand">
                <div class="company-logo">B</div>
                <div>
                    <div class="company-info-name">Baseline IT Development</div>
                    <div class="company-info-tag">Enterprise Suite · v2.0</div>
                </div>
            </div>

            {{-- Hero --}}
            <div class="hero">

                <div class="live-badge">
                    <span class="live-dot"></span>
                    Live &amp; Operational
                </div>

                <h1 class="hero-title">
                    Connect Your Team.<br>
                    <span class="gradient-text">Communicate Faster.</span>
                </h1>

                <p class="hero-desc">
                    Baseline Chat is the internal communication platform built exclusively
                    for Baseline IT Development — real-time messaging, smart notifications,
                    file sharing and team presence all in one place.
                </p>

                {{-- Feature pills --}}
                <div class="feature-pills">
                    <div class="feature-pill">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Real-time Messaging
                    </div>
                    <div class="feature-pill">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        File &amp; Photo Sharing
                    </div>
                    <div class="feature-pill">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Team Presence
                    </div>
                    <div class="feature-pill">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Read Receipts
                    </div>
                    <div class="feature-pill">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Emoji Reactions
                    </div>
                    <div class="feature-pill">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        End-to-End Encrypted
                    </div>
                </div>

                {{-- Live chat preview mockup --}}
                <div class="chat-preview">
                    <div class="preview-header">
                        <div class="preview-avatar" style="background:linear-gradient(135deg,#6264a7,#a89cf7)">VS</div>
                        <div>
                            <div class="preview-name">Vibhor Saini</div>
                            <div class="preview-status">● Available</div>
                        </div>
                    </div>
                    <div class="preview-messages">
                        <div class="preview-msg">
                            <div class="preview-msg-avatar" style="background:linear-gradient(135deg,#6264a7,#a89cf7)">VS</div>
                            <div>
                                <div class="preview-bubble theirs">Hey! Did you check the latest deploy?</div>
                                <div class="preview-time">9:41 AM</div>
                            </div>
                        </div>
                        <div class="preview-msg mine">
                            <div class="preview-msg-avatar" style="background:linear-gradient(135deg,#38d9c0,#2bb8a8)">B</div>
                            <div style="text-align:right">
                                <div class="preview-bubble mine">Yes! Looks great 🚀 All tests passed.</div>
                                <div class="preview-time">9:42 AM ✓✓</div>
                            </div>
                        </div>
                        <div class="preview-msg">
                            <div class="preview-msg-avatar" style="background:linear-gradient(135deg,#6264a7,#a89cf7)">VS</div>
                            <div>
                                <div class="preview-bubble theirs">Perfect. Let's push it to prod 👍</div>
                                <div class="preview-time">9:42 AM</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="stats-row">
                    <div>
                        <div class="stat-val">99.9%</div>
                        <div class="stat-lbl">Uptime SLA</div>
                    </div>
                    <div>
                        <div class="stat-val">&lt;50ms</div>
                        <div class="stat-lbl">Latency</div>
                    </div>
                    <div>
                        <div class="stat-val">E2E</div>
                        <div class="stat-lbl">Encrypted</div>
                    </div>
                    <div>
                        <div class="stat-val">24/7</div>
                        <div class="stat-lbl">Support</div>
                    </div>
                </div>

                {{-- Testimonial --}}
                <div class="testimonial">
                    <div class="testimonial-text">
                        Baseline Chat transformed how our teams collaborate. Everything in one place, fast, and reliable.
                    </div>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">RK</div>
                        <div>
                            <div class="testimonial-name">Rahul Kumar</div>
                            <div class="testimonial-role">Project Manager, Baseline IT Development</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════
         RIGHT — Login form
    ══════════════════════════════════ --}}
    <div class="right-panel">
        <div class="login-box">

            {{-- Header --}}
            <div class="login-header">
                <div class="login-logo-wrap">
                    <div class="login-logo">B</div>
                    <div class="login-logo-ring"></div>
                </div>
                <div class="login-title">Welcome back!</div>
                <div class="login-sub">Sign in to your Baseline account</div>
            </div>

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
                        <svg class="input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <input id="email" class="field-input" type="email" name="email"
                               value="{{ old('email') }}" placeholder="you@baseline-it.com"
                               autofocus autocomplete="username">
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
                        <svg class="input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input id="password" class="field-input" type="password" name="password"
                               placeholder="Enter your password" autocomplete="current-password">
                        <button type="button" class="pw-toggle" id="pwToggle" title="Show password" aria-label="Toggle password visibility">
                            <svg id="eyeShow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg id="eyeHide" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
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
                        <a href="{{ route('password.request') }}" class="forgot-link">Forgot password?</a>
                    @endif
                </div>

                {{-- Submit --}}
                <button type="submit" class="submit-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                        <polyline points="10 17 15 12 10 7"/>
                        <line x1="15" y1="12" x2="3" y2="12"/>
                    </svg>
                    Sign In to Baseline
                </button>

            </form>

            {{-- Security note --}}
            <div class="security-note">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                Secured with enterprise-grade encryption
            </div>

            <div class="login-footer">
                © {{ date('Y') }} Baseline IT Development. All rights reserved.<br>
                <a href="mailto:support@baseline-it.com">Contact Support</a> &nbsp;·&nbsp;
                <a href="#">Privacy Policy</a>
            </div>

        </div>

        <div class="status-bar">
            <span class="status-dot"></span>
            All systems operational
        </div>
    </div>

</div>

<script>
    // Password show/hide
    const pwToggle = document.getElementById('pwToggle');
    const pwInput  = document.getElementById('password');
    const eyeShow  = document.getElementById('eyeShow');
    const eyeHide  = document.getElementById('eyeHide');

    pwToggle.addEventListener('click', () => {
        const hidden = pwInput.type === 'password';
        pwInput.type = hidden ? 'text' : 'password';
        eyeShow.style.display = hidden ? 'none'  : '';
        eyeHide.style.display = hidden ? ''      : 'none';
    });

    // Animated particles
    (function() {
        const container = document.getElementById('particles');
        if (!container) return;

        function createParticle() {
            const p = document.createElement('div');
            p.className = 'particle';
            const size = Math.random() * 4 + 2;
            const left = Math.random() * 100;
            const duration = Math.random() * 15 + 10;
            const delay = Math.random() * 10;
            p.style.cssText = `
                width:${size}px; height:${size}px;
                left:${left}%;
                bottom:-10px;
                animation-duration:${duration}s;
                animation-delay:${delay}s;
                opacity:0;
            `;
            container.appendChild(p);
            setTimeout(() => p.remove(), (duration + delay) * 1000);
        }

        // Initial burst
        for (let i = 0; i < 18; i++) createParticle();
        // Ongoing
        setInterval(createParticle, 800);
    })();

    // ── Click bubble burst effect ──────────────────────────────
    (function () {
        // Inject keyframes once
        const style = document.createElement('style');
        style.textContent = `
            .click-bubble {
                position: fixed;
                border-radius: 50%;
                pointer-events: none;
                z-index: 99999;
                transform: translate(-50%, -50%) scale(0);
                animation: bubbleBurst .6s cubic-bezier(.22,1,.36,1) forwards;
            }
            @keyframes bubbleBurst {
                0%   { transform: translate(-50%,-50%) scale(0);   opacity: .9; }
                60%  { transform: translate(-50%,-50%) scale(1);   opacity: .5; }
                100% { transform: translate(-50%,-50%) scale(1.4); opacity: 0;  }
            }
            .click-ring {
                position: fixed;
                border-radius: 50%;
                pointer-events: none;
                z-index: 99999;
                transform: translate(-50%,-50%) scale(0);
                border: 2px solid;
                animation: ringExpand .7s cubic-bezier(.22,1,.36,1) forwards;
            }
            @keyframes ringExpand {
                0%   { transform: translate(-50%,-50%) scale(0);   opacity: .8; }
                100% { transform: translate(-50%,-50%) scale(2.5); opacity: 0;  }
            }
            .click-spark {
                position: fixed;
                pointer-events: none;
                z-index: 99999;
                border-radius: 50%;
                animation: sparkFly var(--dur, .5s) ease-out forwards;
            }
            @keyframes sparkFly {
                0%   { transform: translate(0,0) scale(1); opacity: 1; }
                100% { transform: translate(var(--tx),var(--ty)) scale(0); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        const PALETTE = [
            '#7b7dd6', '#6264a7', '#a89cf7',
            '#92c353', '#00bcf2', '#c8b8ff',
            '#ff79c6', '#f8d84e'
        ];

        document.addEventListener('click', (e) => {
            const x = e.clientX;
            const y = e.clientY;
            const color = PALETTE[Math.floor(Math.random() * PALETTE.length)];

            // 1. Central filled bubble
            const bubble = document.createElement('div');
            bubble.className = 'click-bubble';
            const size = 28 + Math.random() * 22;
            bubble.style.cssText = `
                left:${x}px; top:${y}px;
                width:${size}px; height:${size}px;
                background: radial-gradient(circle at 35% 35%, ${color}cc, ${color}44);
                box-shadow: 0 0 16px ${color}88;
            `;
            document.body.appendChild(bubble);

            // 2. Expanding ring
            const ring = document.createElement('div');
            ring.className = 'click-ring';
            const ringSize = size * 1.1;
            ring.style.cssText = `
                left:${x}px; top:${y}px;
                width:${ringSize}px; height:${ringSize}px;
                border-color:${color};
                box-shadow: 0 0 8px ${color}66;
            `;
            document.body.appendChild(ring);

            // 3. Mini sparks flying outward
            const sparkCount = 7 + Math.floor(Math.random() * 5);
            for (let i = 0; i < sparkCount; i++) {
                const spark = document.createElement('div');
                spark.className = 'click-spark';
                const angle  = (360 / sparkCount) * i + Math.random() * 20;
                const dist   = 40 + Math.random() * 50;
                const rad    = angle * Math.PI / 180;
                const tx     = Math.cos(rad) * dist;
                const ty     = Math.sin(rad) * dist;
                const sSize  = 4 + Math.random() * 5;
                const dur    = (.35 + Math.random() * .3).toFixed(2);
                const sColor = PALETTE[Math.floor(Math.random() * PALETTE.length)];
                spark.style.cssText = `
                    left:${x}px; top:${y}px;
                    width:${sSize}px; height:${sSize}px;
                    background: ${sColor};
                    box-shadow: 0 0 6px ${sColor};
                    --tx:${tx}px; --ty:${ty}px; --dur:${dur}s;
                `;
                document.body.appendChild(spark);
                setTimeout(() => spark.remove(), dur * 1000 + 100);
            }

            // 4. Extra smaller bubbles around origin
            for (let i = 0; i < 3; i++) {
                const mini = document.createElement('div');
                mini.className = 'click-bubble';
                const mSize = 8 + Math.random() * 12;
                const mOffset = 20 + Math.random() * 30;
                const mAngle  = Math.random() * 360;
                const mRad    = mAngle * Math.PI / 180;
                const mColor  = PALETTE[Math.floor(Math.random() * PALETTE.length)];
                mini.style.cssText = `
                    left:${x + Math.cos(mRad) * mOffset}px;
                    top:${y + Math.sin(mRad) * mOffset}px;
                    width:${mSize}px; height:${mSize}px;
                    background: radial-gradient(circle at 35% 35%, ${mColor}cc, ${mColor}44);
                    box-shadow: 0 0 8px ${mColor}88;
                    animation-duration: .5s;
                    animation-delay: ${(i * .06).toFixed(2)}s;
                `;
                document.body.appendChild(mini);
                setTimeout(() => mini.remove(), 700);
            }

            // Cleanup main elements
            setTimeout(() => { bubble.remove(); ring.remove(); }, 700);
        });
    })();
</script>

</body>
</html>
