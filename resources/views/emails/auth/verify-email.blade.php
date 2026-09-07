<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Verify your email address</title>

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

        .logo {
            display: inline-block;
        }

        .logo img {
            max-height: 40px;
            display: block;
        }

        .logo-placeholder {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: #ffffff;
            font-size: 22px;
            font-weight: 700;
            font-family: Arial, sans-serif;
        }

        .icon-badge {
            margin: 8px auto 0;
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #ecfdf5;
            display: flex;
            align-items: center;
            justify-content: center;
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

        .fallback {
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
            </div>

            <div class="content">
                <h1>Verify your email address</h1>

                <p class="greeting">Hi {{ $user->first_name ?? 'there' }},</p>

                <p>
                    Thanks for creating an account with <strong>{{ config('app.name') }}</strong>.
                    Just one quick step left — verify your email address below to
                    activate your account and get started.
                </p>

                <div class="button-wrapper">
                    <a href="{{ $verificationUrl }}" class="button">
                        Verify Email Address
                    </a>
                </div>
                <p class="expiry-note">This link will expire in 60 minutes.</p>

                <hr class="divider">

                <p>
                    If you didn't create this account, you can safely ignore
                    this email — no further action is needed.
                </p>

                <div class="fallback">
                    <p>Button not working? Copy and paste this URL into your browser:</p>
                    <p class="fallback-link">{{ $verificationUrl }}</p>
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