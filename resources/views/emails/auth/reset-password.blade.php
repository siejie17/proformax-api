<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Reset your password</title>

    <style>
        body {
            margin: 0;
            padding: 0;
            background: #eef1f6;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1f2933;
            -webkit-font-smoothing: antialiased;
        }

        table {
            border-collapse: collapse;
        }

        .wrapper {
            width: 100%;
            padding: 56px 16px;
        }

        .container {
            max-width: 560px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 45px rgba(31, 41, 51, 0.08);
        }

        .accent-bar {
            height: 6px;
            background: linear-gradient(90deg, #10b981 0%, #059669 50%, #047857 100%);
        }

        .header {
            padding: 40px 32px 24px;
            text-align: center;
        }

        .logo img {
            max-height: 40px;
            display: block;
            margin: 0 auto;
        }

        .icon-badge {
            margin: 20px auto 0;
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #ecfdf5;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon-badge svg {
            display: block;
        }

        .content {
            padding: 8px 40px 40px;
            text-align: center;
        }

        h1 {
            margin: 0 0 14px;
            font-size: 26px;
            line-height: 1.3;
            color: #1f2933;
            font-weight: 700;
        }

        p {
            margin: 0 0 18px;
            font-size: 15px;
            line-height: 1.7;
            color: #667085;
            text-align: left;
        }

        .greeting {
            text-align: left;
            font-weight: 600;
            color: #1f2933;
        }

        .button-wrapper {
            margin: 32px 0 8px;
            text-align: center;
        }

        .button {
            display: inline-block;
            padding: 15px 36px;
            border-radius: 999px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: #ffffff !important;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.3);
        }

        .expiry-note {
            text-align: center;
            font-size: 13px;
            color: #98a2b3;
            margin: 4px 0 0;
        }

        .divider {
            border: none;
            border-top: 1px solid #eef0ee;
            margin: 32px 0 24px;
        }

        .security-note {
            padding: 16px 18px;
            background: #f7f8fb;
            border: 1px solid #eef0ee;
            border-radius: 12px;
            text-align: left;
        }

        .security-note p {
            margin: 0;
            font-size: 13px;
            color: #667085;
        }

        .fallback {
            margin-top: 16px;
            padding: 16px 18px;
            background: #f7f8fb;
            border: 1px solid #eef0ee;
            border-radius: 12px;
            word-break: break-all;
            text-align: left;
        }

        .fallback p {
            margin: 0;
            font-size: 12px;
        }

        .fallback-link {
            margin-top: 8px !important;
            color: #059669 !important;
        }

        .footer {
            padding: 28px 32px 36px;
            text-align: center;
        }

        .footer p {
            margin: 0 0 6px;
            font-size: 12px;
            color: #98a2b3;
        }

        .footer a {
            color: #98a2b3;
            text-decoration: underline;
        }

        @media only screen and (max-width: 600px) {
            .wrapper {
                padding: 24px 12px;
            }

            .content {
                padding: 8px 24px 32px;
            }

            .header {
                padding: 32px 24px 16px;
            }

            h1 {
                font-size: 22px;
            }

            .button {
                display: block;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="container">

            <div class="accent-bar"></div>

            <div class="header">
                <div class="logo">
                    <img src="{{ asset('proformax-logo.png') }}" alt="{{ config('app.name') }}">
                </div>

                <div class="icon-badge">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 15V17M6 21H18C19.1046 21 20 20.1046 20 19V13C20 11.8954 19.1046 11 18 11H6C4.89543 11 4 11.8954 4 13V19C4 20.1046 4.89543 21 6 21ZM16 11V7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7V11H16Z" stroke="#059669" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>

            <div class="content">
                <h1>Reset your password</h1>

                <p class="greeting">Hi {{ $user->first_name }},</p>

                <p>
                    We received a request to reset the password for your
                    <strong>{{ config('app.name') }}</strong> account. Click the
                    button below to create a new password.
                </p>

                <div class="button-wrapper">
                    <a href="{{ $resetUrl }}" class="button">
                        Reset Password
                    </a>
                </div>
                <p class="expiry-note">This link will expire in 60 minutes.</p>

                <hr class="divider">

                <div class="security-note">
                    <p>
                        If you didn't request a password reset, you can safely
                        ignore this email — your password will remain unchanged.
                    </p>
                </div>

                <div class="fallback">
                    <p>Button not working? Copy and paste this URL into your browser:</p>
                    <p class="fallback-link">{{ $resetUrl }}</p>
                </div>
            </div>

            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                <p><a href="#">Help Center</a> &nbsp;&middot;&nbsp; <a href="#">Contact Support</a></p>
            </div>

        </div>
    </div>
</body>
</html>