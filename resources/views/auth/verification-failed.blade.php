<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProFormaX - Email Verification Failed</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="antialiased">
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 flex items-center justify-center p-4">
        <div class="max-w-md w-full">
            <!-- Card Container -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
                <!-- Icon -->
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>

                    <!-- Title -->
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">Verification Failed</h1>

                    <!-- Description -->
                    <p class="text-gray-600 leading-relaxed">
                        The verification link is invalid or expired.<br>
                        Please try requesting a new verification email.
                    </p>
                </div>

                <!-- Divider -->
                <div class="border-t border-gray-200 my-6"></div>

                <!-- Action Button -->
                <form method="POST" action="{{ route('verification.resend') }}">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-red-600 to-rose-600 text-white font-semibold py-3 px-6 rounded-lg shadow-lg hover:from-red-700 hover:to-rose-700 focus:outline-none focus:ring-4 focus:ring-red-200 inline-flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 002 2v10a2 2 0 002 2z">
                            </path>
                        </svg>
                        <span>Request New Verification</span>
                    </button>
                </form>

                <!-- Footer Note -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <p class="text-sm text-gray-500 text-center">
                        Need help? Contact our support team
                    </p>
                </div>
            </div>

            <!-- Additional Help -->
            <div class="text-center mt-6">
                <p class="text-sm text-gray-500">
                    Still having issues?
                    <a href="#" class="text-red-600 hover:text-red-800 font-medium">Contact Support</a>
                </p>
            </div>
        </div>
    </div>
</body>

</html>
