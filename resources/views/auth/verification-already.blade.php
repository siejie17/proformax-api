<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProFormaX - Email Already Verified</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="antialiased">
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 flex items-center justify-center p-4">
        <div class="max-w-md w-full">
            <!-- Card Container -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
                <!-- Icon -->
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    
                    <!-- Title -->
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">Already Verified</h1>
                    
                    <!-- Description -->
                    <div class="text-gray-600 leading-relaxed">
                        <p class="mb-3">The email address</p>
                        <p class="font-semibold text-blue-700 bg-blue-50 px-3 py-2 rounded-lg mb-3 break-all text-sm">
                            {{ $email ?? 'your email' }}
                        </p>
                        <p>was already verified earlier.</p>
                    </div>
                </div>

                <!-- Divider -->
                <div class="border-t border-gray-200 mt-6 mb-4"></div>

                <!-- Footer Note -->
                <div>
                    <p class="text-sm text-gray-500 text-center">
                        You're all set! No further action needed
                    </p>
                </div>

                <div class="border-t border-gray-200 mt-4 mb-2"></div>
            </div>
        </div>
    </div>
</body>

</html>