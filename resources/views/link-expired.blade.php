<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Expired</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .float-animation {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes fadeIn {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.8s ease-out forwards;
        }

        .fade-in-delay {
            animation: fadeIn 0.8s ease-out 0.3s forwards;
            opacity: 0;
        }

        .fade-in-delay-2 {
            animation: fadeIn 0.8s ease-out 0.6s forwards;
            opacity: 0;
        }
    </style>
</head>

<body class="antialiased bg-gray-100">
    <div
        class="min-h-screen bg-gradient-to-br from-slate-50 via-green-50 to-emerald-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full text-center">
            <!-- Main Content Card -->
            <div class="bg-white/80 backdrop-blur-sm rounded-3xl shadow-2xl py-10 border border-white/50 fade-in-delay">
                <!-- Animated Icon -->
                <div class="fade-in mb-8">
                    <div
                        class="mx-auto h-24 w-24 bg-gradient-to-r from-red-500 to-orange-500 rounded-full flex items-center justify-center mb-6 shadow-xl">
                        <svg class="h-12 w-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.232 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                </div>

                <!-- Title -->
                <h1 class="text-4xl font-bold text-gray-800 mb-4 leading-tight">
                    Link Expired
                </h1>

                <!-- Subtitle -->
                <div class="text-lg text-gray-600 leading-relaxed">
                    <p class="mb-2">⏰ This password reset link has expired.</p>
                </div>
            </div>

            <!-- Footer Message -->
            <div class="mt-8 fade-in-delay-2">
                <p class="text-sm text-gray-500">
                    Security links expire for your protection
                </p>
            </div>

            <!-- Decorative Elements -->
            <div class="absolute top-20 left-20 w-20 h-20 bg-green-200 rounded-full opacity-20 animate-pulse"></div>
            <div
                class="absolute bottom-20 right-20 w-16 h-16 bg-emerald-200 rounded-full opacity-20 animate-pulse delay-1000">
            </div>
            <div
                class="absolute top-1/3 right-10 w-12 h-12 bg-green-300 rounded-full opacity-10 animate-pulse delay-500">
            </div>
        </div>
    </div>
</body>

</html>
