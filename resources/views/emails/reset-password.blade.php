<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Reset your password — {{ $appName }}</title>
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
    <![endif]-->
    <style>
        /* Reset */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Arial, sans-serif;
            background-color: #0f0f18;
            margin: 0;
            padding: 0;
        }

        .email-wrapper {
            background-color: #0f0f18;
            padding: 40px 16px;
        }

        .email-container {
            max-width: 560px;
            margin: 0 auto;
        }

        /* Header / Brand */
        .brand-header {
            text-align: center;
            padding-bottom: 28px;
        }

        .brand-logo {
            display: inline-block;
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #6264a7 0%, #7b7dd6 100%);
            border-radius: 14px;
            font-size: 22px;
            font-weight: 900;
            color: #ffffff;
            text-decoration: none;
            margin-bottom: 14px;
            text-align: center;
            line-height: 52px;
        }

        .brand-name {
            font-size: 20px;
            font-weight: 800;
            color: #f0f0f8;
            letter-spacing: -0.01em;
            display: block;
        }

        .brand-tagline {
            font-size: 12px;
            color: #6666aa;
            margin-top: 3px;
            display: block;
        }

        /* Card */
        .card {
            background-color: #1a1a28;
            border: 1px solid #2a2a42;
            border-radius: 20px;
            overflow: hidden;
        }

        /* Card top accent bar */
        .card-accent {
            background: linear-gradient(90deg, #6264a7 0%, #7b7dd6 50%, #6264a7 100%);
            height: 3px;
        }

        .card-body {
            padding: 36px 40px 32px;
        }

        /* Icon row */
        .icon-row {
            text-align: center;
            margin-bottom: 24px;
        }

        .lock-icon {
            display: inline-block;
            width: 56px;
            height: 56px;
            background: rgba(98, 100, 167, 0.15);
            border: 1px solid rgba(98, 100, 167, 0.3);
            border-radius: 50%;
            text-align: center;
            line-height: 56px;
            font-size: 24px;
        }

        /* Heading */
        .email-heading {
            font-size: 22px;
            font-weight: 800;
            color: #f0f0f8;
            text-align: center;
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }

        .email-subheading {
            font-size: 14px;
            color: #8888aa;
            text-align: center;
            margin-bottom: 28px;
            line-height: 1.6;
        }

        /* Divider */
        .divider {
            border: none;
            border-top: 1px solid #2a2a42;
            margin: 24px 0;
        }

        /* Body text */
        .email-text {
            font-size: 14px;
            color: #aaaacc;
            line-height: 1.7;
            margin-bottom: 16px;
        }

        .email-text strong {
            color: #f0f0f8;
        }

        /* CTA Button */
        .cta-wrapper {
            text-align: center;
            margin: 28px 0;
        }

        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #6264a7 0%, #7b7dd6 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 36px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.01em;
            box-shadow: 0 4px 20px rgba(98, 100, 167, 0.4);
        }

        /* Security notice box */
        .notice-box {
            background: rgba(98, 100, 167, 0.08);
            border: 1px solid rgba(98, 100, 167, 0.2);
            border-radius: 10px;
            padding: 14px 18px;
            margin: 24px 0;
        }

        .notice-box p {
            font-size: 12.5px;
            color: #7777aa;
            line-height: 1.6;
            margin: 0;
        }

        .notice-box p + p {
            margin-top: 6px;
        }

        .notice-box strong {
            color: #aaaacc;
        }

        /* Fallback URL */
        .fallback-url {
            background: #13131e;
            border: 1px solid #2a2a42;
            border-radius: 8px;
            padding: 12px 16px;
            margin: 20px 0;
            word-break: break-all;
        }

        .fallback-url p {
            font-size: 11px;
            color: #555577;
            margin-bottom: 6px;
        }

        .fallback-url a {
            font-size: 11px;
            color: #7b7dd6;
            text-decoration: none;
            word-break: break-all;
        }

        /* Footer */
        .email-footer {
            padding: 24px 40px;
            border-top: 1px solid #1e1e2e;
            text-align: center;
        }

        .footer-text {
            font-size: 11.5px;
            color: #444466;
            line-height: 1.7;
        }

        .footer-text a {
            color: #6264a7;
            text-decoration: none;
        }

        .footer-divider {
            display: inline-block;
            margin: 0 8px;
            color: #333355;
        }

        /* Responsive */
        @media only screen and (max-width: 600px) {
            .card-body { padding: 28px 24px 24px; }
            .email-footer { padding: 20px 24px; }
            .cta-button { padding: 13px 28px; font-size: 14px; }
            .email-heading { font-size: 19px; }
        }
    </style>
</head>
<body>

<div class="email-wrapper">
    <div class="email-container">

        {{-- Brand header --}}
        <div class="brand-header">
            <div>
                <span class="brand-logo">B</span>
            </div>
            <span class="brand-name">{{ $appName }}</span>
            <span class="brand-tagline">Real-time team communication</span>
        </div>

        {{-- Main card --}}
        <div class="card">
            <div class="card-accent"></div>

            <div class="card-body">

                {{-- Lock icon --}}
                <div class="icon-row">
                    <span class="lock-icon">🔐</span>
                </div>

                {{-- Heading --}}
                <h1 class="email-heading">Reset your password</h1>
                <p class="email-subheading">
                    Hi <strong style="color:#c8c8ee;">{{ $userName }}</strong>, we received a request to reset your {{ $appName }} password.
                </p>

                <hr class="divider">

                <p class="email-text">
                    Click the button below to choose a new password. This link is valid for <strong>{{ $expiresIn }} minutes</strong> and can only be used once.
                </p>

                {{-- CTA --}}
                <div class="cta-wrapper">
                    <a href="{{ $resetUrl }}" class="cta-button">
                        🔑 &nbsp;Reset My Password
                    </a>
                </div>

                {{-- Security notice --}}
                <div class="notice-box">
                    <p>🛡️ <strong>Didn't request this?</strong> You can safely ignore this email — your password will remain unchanged and this link will expire automatically.</p>
                    <p>⏱️ This link expires in <strong>{{ $expiresIn }} minutes</strong> from when it was requested.</p>
                </div>

                {{-- Fallback URL --}}
                <div class="fallback-url">
                    <p>If the button above doesn't work, paste this link into your browser:</p>
                    <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
                </div>

            </div>

            {{-- Footer --}}
            <div class="email-footer">
                <p class="footer-text">
                    This email was sent to you because a password reset was requested for your account.
                    <br>
                    © {{ date('Y') }} {{ $appName }}
                    <span class="footer-divider">·</span>
                    <a href="{{ config('app.url') }}">Visit app</a>
                    <span class="footer-divider">·</span>
                    <a href="{{ route('login') }}">Sign in</a>
                </p>
            </div>

        </div>

        {{-- Bottom note --}}
        <p style="text-align:center;font-size:11px;color:#333355;margin-top:20px;">
            If you have any trouble, contact your workspace admin.
        </p>

    </div>
</div>

</body>
</html>
