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
            --nav-bg: #1a1a24;
            --nav-width: 68px;
            --content-bg: #1e1e28;
            --surface: #252532;
            --surface-2: #2d2d3d;
            --border: #35354a;
            --accent: #6264a7;
            --accent-h: #7b7dd6;
            --accent-dim: rgba(98, 100, 167, .18);
            --text-1: #f0f0f5;
            --text-2: #9090b0;
            --text-3: #5a5a78;
            --online: #57c75a;
            --danger: #e05b5b;
            --radius: 10px;
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

        /* ── App shell ──────────────────────────── */
        .app-shell {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* ── Left nav rail ──────────────────────── */
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

        .nav-link.danger { color: var(--text-3); }

        .nav-link.danger:hover {
            background: rgba(224,91,91,.12);
            color: var(--danger);
        }

        .nav-spacer { flex: 1; }

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

        /* ── Main content ───────────────────────── */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: var(--content-bg);
        }

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

        .page-area {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .page-area.no-pad {
            overflow: hidden;
            padding: 0;
        }

        .page-area:not(.no-pad) { padding: 28px; }

        .page-area::-webkit-scrollbar { width: 5px; }
        .page-area::-webkit-scrollbar-track { background: transparent; }
        .page-area::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 4px;
        }

        /* ── Presence dots ──────────────────────── */
        .presence-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            border: 2px solid var(--surface);
            background: var(--text-3); /* default = offline grey */
            transition: background .4s;
        }

        .presence-dot.presence-online  { background: var(--online); }
        .presence-dot.presence-offline { background: var(--text-3); }

        /* Status dot in chat header */
        .status-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--text-3);
            transition: background .4s;
            vertical-align: middle;
            margin-right: 4px;
        }

        /* NOTE: color is controlled entirely by JS inline style.
           These classes only serve as online/offline state markers — no color override. */
        .status-dot.status-online  { /* color set by JS */ }
        .status-dot.status-offline { background: var(--text-3) !important; box-shadow: none !important; }

        /* ── WhatsApp message ticks ────────────────── */
        .tick { display: inline-block; vertical-align: middle; margin-left: 3px; flex-shrink: 0; }

        /* ── Message bubbles ────────────────────── */
        .msg-row { display: flex; align-items: flex-end; gap: 8px; margin: 2px 16px; position: relative; }
        /* msg-actions show/hide handled by JS (bubble-only hover) */
        /* First message of group gets normal top margin; continued messages collapse */
        .msg-continued { margin-top: 1px; }
        .msg-continued .msg-avatar { visibility: hidden; }
        .msg-mine  { flex-direction: row-reverse; }
        .msg-theirs { flex-direction: row; }

        /* Date separator */
        .date-separator {
            display: flex; align-items: center; justify-content: center;
            margin: 18px 16px 10px;
            position: relative;
        }
        .date-separator::before {
            content: ''; position: absolute; left: 0; right: 0; top: 50%;
            height: 1px; background: var(--border);
        }
        .date-separator-label {
            position: relative;
            background: var(--surface); /* same as messages-area bg */
            padding: 3px 12px;
            font-size: .72rem; font-weight: 600;
            color: var(--text-3);
            border-radius: 999px;
            border: 1px solid var(--border);
            letter-spacing: .03em;
        }

        /* Sender name above first bubble of a group */
        .msg-sender-name {
            font-size: .72rem; font-weight: 600;
            color: var(--accent-h);
            margin-bottom: 2px; padding: 0 4px;
        }

        .msg-avatar {
            width: 30px; height: 30px; border-radius: 50%;
            background: var(--accent); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: .75rem; font-weight: 700; flex-shrink: 0;
        }
        .msg-avatar-hidden { visibility: hidden; }
        .msg-avatar-mine { background: var(--surface-2); }

        .msg-body-wrap { max-width: 65%; display: flex; flex-direction: column; position: relative; }
        .msg-mine .msg-body-wrap  { align-items: flex-end; }
        .msg-theirs .msg-body-wrap { align-items: flex-start; }

        .fwd-label {
            font-size: .7rem; color: var(--accent-h);
            display: flex; align-items: center; gap: 4px;
            margin-bottom: 2px; padding: 0 4px;
        }

        .msg-bubble {
            padding: 8px 12px;
            border-radius: 12px;
            font-size: .88rem;
            line-height: 1.45;
            word-break: break-word;
            position: relative;
        }
        .bubble-mine   { background: var(--accent); color: #fff; border-bottom-right-radius: 4px; }
        .bubble-theirs { background: var(--surface-2); color: var(--text-1); border-bottom-left-radius: 4px; }
        .bubble-deleted { background: transparent !important; border: 1px solid var(--border); color: var(--text-3) !important; font-style: italic; }

        .deleted-text { display: flex; align-items: center; gap: 5px; font-size: .82rem; }

        .msg-time-wrap {
            display: flex; align-items: center; gap: 3px;
            justify-content: flex-end;
            margin-top: 3px; padding: 0 2px;
        }
        .msg-time { font-size: .68rem; color: rgba(255,255,255,.6); }
        .bubble-theirs .msg-time { color: var(--text-3); }

        /* ── Image & file bubbles ───────────────── */
        .msg-img-wrap { display: block; }
        .msg-image { max-width: 240px; max-height: 240px; border-radius: 8px; display: block; object-fit: cover; cursor: zoom-in; }

        .msg-file-wrap {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 12px;
            background: rgba(0,0,0,.15);
            border-radius: 8px;
            text-decoration: none; color: inherit;
            min-width: 180px;
        }
        .msg-file-icon { flex-shrink: 0; opacity: .8; }
        .msg-file-name { flex: 1; font-size: .82rem; word-break: break-all; }
        .msg-file-dl   { flex-shrink: 0; opacity: .7; }
        .msg-caption   { margin: 4px 0 0; font-size: .82rem; opacity: .85; }

        /* -- Message actions hover menu -- */
        .msg-actions {
            display: flex; gap: 4px;
            position: absolute; top: -44px;
            opacity: 0; pointer-events: none;
            transition: opacity .15s;
            background: var(--bg-float, #161628);
            border: 1px solid var(--border-dim, rgba(255,255,255,.08));
            border-radius: 10px;
            padding: 4px 6px;
            z-index: 20;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,.35);
        }
        .msg-actions--mine   { right: 0; }
        .msg-actions--theirs { left: 0; }
        /* Hover trigger removed from here — handled by JS mouseenter/leave */

        .msg-action-btn {
            background: none; border: none; cursor: pointer;
            color: var(--text-secondary, #9090b0); padding: 4px 5px;
            border-radius: 6px; display: flex; align-items: center; justify-content: center;
            transition: color .12s, background .12s;
            font-size: 17px; line-height: 1;
        }
        .msg-action-btn:hover { color: var(--text-primary, #eeeef5); background: var(--bg-hover, #212138); }
        .msg-action-btn--delete:hover { color: var(--danger, #ff5f72); background: rgba(255,95,114,.12); }
        .msg-actions-sep { width: 1px; height: 18px; background: var(--border-dim, rgba(255,255,255,.08)); margin: 0 2px; flex-shrink: 0; }
        /* ── Attachment preview ─────────────────── */
        .attachment-preview {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 16px;
            background: var(--surface-2);
            border-top: 1px solid var(--border);
        }
        .attachment-thumb { height: 48px; width: 48px; object-fit: cover; border-radius: 6px; }
        .attachment-file-name { font-size: .82rem; color: var(--text-2); flex: 1; }
        .attachment-remove {
            background: none; border: none; color: var(--text-3);
            cursor: pointer; font-size: 1rem; padding: 2px 6px;
            border-radius: 4px; transition: color .15s;
        }
        .attachment-remove:hover { color: var(--danger); }

        /* ── File input hidden ──────────────────── */
        .file-input-hidden { display: none; }

        .attach-btn {
            cursor: pointer; color: var(--text-2);
            display: flex; align-items: center; padding: 0 8px;
            transition: color .15s;
            flex-shrink: 0;
        }
        .attach-btn:hover { color: var(--accent-h); }

        /* ── Draft preview label in sidebar ─────────── */
        .draft-label {
            color: #e05a5a;
            font-size: .7rem;
            font-weight: 600;
        }
        .draft-text {
            color: var(--text-2);
            font-size: .78rem;
        }

        /* ── Unread badge ───────────────────────── */
        .conv-bottom-row { display: flex; align-items: center; gap: 6px; }
        .conv-preview    { flex: 1; margin: 0; }

        .unread-badge {
            min-width: 20px; height: 20px; padding: 0 5px;
            border-radius: 999px;
            background: var(--online);
            color: #fff;
            font-size: .65rem; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        /* ── Forward modal ──────────────────────── */
        .modal-backdrop {
            position: fixed; inset: 0;
            background: rgba(0,0,0,.6);
            display: flex; align-items: center; justify-content: center;
            z-index: 500;
        }
        .modal-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            width: 360px; max-height: 520px;
            display: flex; flex-direction: column;
            overflow: hidden;
        }
        .modal-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
        }
        .modal-title { margin: 0; font-size: 1rem; font-weight: 700; }
        .modal-close {
            background: none; border: none; cursor: pointer;
            color: var(--text-2); padding: 4px;
            border-radius: 6px; transition: color .15s;
        }
        .modal-close:hover { color: var(--danger); }
        .modal-extra-text { padding: 10px 16px 0; }
        .modal-search { padding: 10px 16px 12px; border-bottom: 1px solid var(--border); }
        .modal-search-input {
            width: 100%; background: var(--surface-2);
            border: 1px solid var(--border); border-radius: 8px;
            color: var(--text-1); padding: 8px 12px;
            font-size: .88rem; outline: none;
        }
        .modal-search-input:focus { border-color: var(--accent); }
        .modal-list { flex: 1; overflow-y: auto; padding: 8px 0; }
        .modal-conv-item {
            width: 100%; display: flex; align-items: center; gap: 12px;
            padding: 10px 20px; background: none; border: none;
            cursor: pointer; color: var(--text-1);
            transition: background .15s;
        }
        .modal-conv-item:hover { background: var(--surface-2); }
        .modal-conv-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: var(--accent); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .85rem; flex-shrink: 0;
        }
        .modal-conv-name { flex: 1; text-align: left; font-size: .9rem; }
        .modal-empty { padding: 24px; text-align: center; color: var(--text-3); }

        /* ── Pending nav badge ──────────────────── */
        .nav-request-link { margin-top: 8px; }

        .nav-request-icon-wrap {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-request-badge {
            position: absolute;
            top: -6px;
            right: -8px;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 999px;
            background: #ff4d6d;
            color: #fff;
            font-size: .62rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            box-shadow: 0 0 0 2px var(--nav-bg);
        }

        /* ── Mobile top bar ─────────────────────── */
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

        /* ── Mobile bottom nav ──────────────────── */
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

        /* ── Pending page ───────────────────────── */
        .pending-page {
            padding: 32px;
            width: 100%;
            height: 100%;
            overflow: auto;
        }

        .pending-page-header {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 28px;
        }

        .pending-page-icon {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            background: rgba(98,100,167,.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7b7dd6;
        }

        .pending-page-title {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            color: #fff;
        }

        .pending-page-subtitle {
            margin-top: 6px;
            color: #9090b0;
            font-size: .95rem;
        }

        .pending-grid { display: grid; gap: 18px; }

        .pending-card {
            background: #252532;
            border: 1px solid #35354a;
            border-radius: 22px;
            padding: 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            transition: border-color .2s, transform .2s;
        }

        .pending-card:hover {
            border-color: #6264a7;
            transform: translateY(-2px);
        }

        .pending-card-left {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .pending-avatar {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            background: #6264a7;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .pending-user-name { font-size: 1.1rem; font-weight: 700; color: #fff; }
        .pending-user-email { margin-top: 4px; color: #9090b0; font-size: .92rem; }

        .pending-user-time {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #77779a;
            font-size: .85rem;
        }

        .pending-status-badge {
            background: rgba(255,166,0,.12);
            color: #ffb347;
            padding: 10px 16px;
            border-radius: 999px;
            font-size: .85rem;
            font-weight: 600;
            border: 1px solid rgba(255,166,0,.2);
        }

        .pending-empty {
            height: 320px;
            border: 1px dashed #35354a;
            border-radius: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #9090b0;
        }

        /* ── Sent Requests sidebar banner ───────── */
        .sent-requests-banner {
            padding: 6px 12px;
            border-bottom: 1px solid var(--border);
        }

        .sent-requests-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            border-radius: var(--radius);
            background: rgba(98,100,167,.08);
            border: 1px solid rgba(98,100,167,.2);
            color: var(--text-2);
            font-size: .82rem;
            font-weight: 500;
            cursor: pointer;
            transition: background .15s, color .15s;
        }

        .sent-requests-btn:hover,
        .sent-requests-btn-active {
            background: rgba(98,100,167,.18);
            color: var(--accent-h);
            border-color: var(--accent);
        }

        .sent-req-left { display: flex; align-items: center; gap: 8px; }

        .sent-req-badge {
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            background: var(--accent);
            color: #fff;
            font-size: .7rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ── Active request item in sidebar ─────── */
        .request-toggle-active {
            background: var(--accent-dim) !important;
            color: var(--accent-h) !important;
        }

        /* ── Search action badges ───────────────── */
        .search-action-btn--open { background: var(--accent-dim); color: var(--accent-h); }
        .search-action-btn--accept { background: rgba(87,199,90,.15); color: #57c75a; border-color: rgba(87,199,90,.3); }

        .search-status-badge {
            font-size: .75rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid;
        }

        .search-status-badge--pending {
            background: rgba(255,179,71,.1);
            color: #ffb347;
            border-color: rgba(255,179,71,.3);
        }

        .search-clear-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-3);
            display: flex;
            align-items: center;
            padding: 2px;
            border-radius: 4px;
            transition: color .15s;
        }

        .search-clear-btn:hover { color: var(--text-1); }

        /* ── Profile dropdown — visibility is controlled by chat.css via .dropdown-open ── */
        /* Do NOT override it here; the .dropdown-open class in chat.css handles opacity/transform */

        /* ── Responsive ─────────────────────────── */
        @media (max-width: 768px) {
            .nav-rail { display: none; }
            .topbar   { display: none; }
            .mobile-topbar   { display: flex; }
            .mobile-bottomnav { display: flex; }
            .page-area:not(.no-pad) { padding: 16px; }
            .pending-page { padding: 18px; }
            .pending-page-title { font-size: 1.4rem; }
            .pending-card { flex-direction: column; align-items: flex-start; }
            .pending-card-left { width: 100%; }
            .pending-status { width: 100%; }
            .pending-status-badge { width: 100%; display: flex; justify-content: center; }
        }
    </style>
</head>

{{--
    IMPORTANT: data-user-id is read by chat.js to subscribe to the private
    user channel (user.{id}). Without this, realtime updates are silently
    disabled for that session.
--}}
<body @auth data-user-id="{{ auth()->id() }}" @endauth>
    <div class="app-shell">

        {{-- ══════════════ DESKTOP NAV RAIL ══════════════ --}}
        <aside class="nav-rail">

            <div class="nav-brand" aria-label="Baseline Chat">B</div>

            <nav class="nav-items" aria-label="Main navigation">
                @auth

                @if(auth()->user()->is_admin)
                <a href="{{ route('dashboard') }}"
                   wire:navigate
                   class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                   title="Dashboard"
                   aria-label="Dashboard">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="3" y="3" width="7" height="7" rx="1.5" />
                        <rect x="14" y="3" width="7" height="7" rx="1.5" />
                        <rect x="3" y="14" width="7" height="7" rx="1.5" />
                        <rect x="14" y="14" width="7" height="7" rx="1.5" />
                    </svg>
                    Dashboard
                </a>

                <a href="{{ route('users.index') }}"
                   wire:navigate
                   class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                   title="Users"
                   aria-label="Users">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                    Users
                </a>
                @endif

                <a href="{{ route('chat.index') }}"
                   wire:navigate
                   class="nav-link {{ request()->routeIs('chat.*') ? 'active' : '' }}"
                   title="Chat"
                   aria-label="Chat">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                    </svg>
                    Chat
                </a>



                @endauth
            </nav>

            <div class="nav-spacer"></div>

            @auth
            <form method="POST" action="{{ route('logout') }}" style="width:100%;padding:0 8px;">
                @csrf
                <button type="submit" class="nav-link danger" style="width:100%" title="Logout">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <polyline points="16 17 21 12 16 7" />
                        <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                    Logout
                </button>
            </form>

            <div class="nav-profile" title="{{ auth()->user()->name }}" aria-label="{{ auth()->user()->name }}"
                 id="navRailProfile">
                @if(auth()->user()->profile_image)
                    <img src="{{ Storage::url(auth()->user()->profile_image) }}"
                         alt="{{ auth()->user()->name }}"
                         id="navRailAvatarImg"
                         style="width:100%;height:100%;border-radius:50%;object-fit:cover;display:block;">
                @else
                    <span id="navRailAvatarInitials">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                @endif
                {{-- Self dot — always online while logged in --}}
                <span class="presence presence-online" aria-hidden="true"></span>
            </div>
            @endauth

        </aside>

        {{-- ══════════════ CONTENT COLUMN ══════════════ --}}
        <div class="main-content">

            {{-- Mobile top bar --}}
            <div class="mobile-topbar">
                <div class="mobile-topbar-brand">
                    <div class="mobile-brand-dot">B</div>
                    Baseline Chat
                </div>
            </div>

            {{-- Page slot --}}
            <div class="page-area {{ request()->routeIs('chat.*') ? 'no-pad' : '' }}">
                {{ $slot }}
            </div>

            {{-- Mobile bottom nav --}}
            <nav class="mobile-bottomnav" aria-label="Mobile navigation">
                @auth
                @if(auth()->user()->is_admin)
                <a href="{{ route('dashboard') }}"
                   wire:navigate
                   class="mob-nav-btn {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="3" y="3" width="7" height="7" rx="1.5" />
                        <rect x="14" y="3" width="7" height="7" rx="1.5" />
                        <rect x="3" y="14" width="7" height="7" rx="1.5" />
                        <rect x="14" y="14" width="7" height="7" rx="1.5" />
                    </svg>
                    Dash
                </a>

                <a href="{{ route('users.index') }}"
                   wire:navigate
                   class="mob-nav-btn {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                    </svg>
                    Users
                </a>
                @endif

                <a href="{{ route('chat.index') }}"
                   wire:navigate
                   class="mob-nav-btn {{ request()->routeIs('chat.*') ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                    </svg>
                    Chat
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="mob-nav-btn danger">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <polyline points="16 17 21 12 16 7" />
                            <line x1="21" y1="12" x2="9" y2="12" />
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
