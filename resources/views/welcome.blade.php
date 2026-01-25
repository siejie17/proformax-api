<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>ProFormaX - Welcome</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            text-align: center;
            max-width: 800px;
        }

        .logo {
            width: 100px;
            height: 100px;
            /* background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%); */
            border-radius: 20px;
            margin: 0 auto 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.2);
            position: relative;
            transition: transform 0.3s ease;
        }

        .logo img {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .logo span {
            color: white;
            font-size: 48px;
            font-weight: bold;
        }

        h1 {
            font-size: 56px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 30px;
            line-height: 1.2;
        }

        .brand {
            background: linear-gradient(135deg, #30a91d 0%, #32a450 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            font-size: 24px;
            color: #64748b;
            font-weight: 300;
            line-height: 1.6;
            margin-bottom: 50px;
        }

        .dots {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-top: 50px;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            animation: pulse 2s ease-in-out infinite;
        }

        .dot:nth-child(1) {
            background: #25eb46;
            animation-delay: 0s;
        }

        .dot:nth-child(2) {
            background: #46e561;
            animation-delay: 0.2s;
        }

        .dot:nth-child(3) {
            background: #10b981;
            animation-delay: 0.4s;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.5;
                transform: scale(0.8);
            }
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 40px;
            }

            .subtitle {
                font-size: 18px;
            }

            .logo {
                width: 80px;
                height: 80px;
                margin-bottom: 40px;
            }

            .logo span {
                font-size: 36px;
            }

            .logo img {
                width: 50px;
                height: 50px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="logo">
            <img src="{{ asset('proformax-logo.png') }}" alt="Image">
        </div>

        <h1>
            Welcome to <span class="brand">ProFormaX</span>
        </h1>

        <p class="subtitle">
            Please access the mobile application to try the features
        </p>

        <div class="dots">
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        </div>
    </div>
</body>

</html>
