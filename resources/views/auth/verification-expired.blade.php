<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Link Expired</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Card Container -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8 transform transition-all duration-300 hover:shadow-2xl">
            <!-- Icon -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                
                <!-- Title -->
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Verification Link Expired</h1>
                
                <!-- Description -->
                <p class="text-gray-600 leading-relaxed">
                    Your verification link has expired for 
                    <span class="font-semibold text-gray-800 bg-gray-100 px-2 py-1 rounded-md text-sm">{{ $email }}</span>
                </p>
            </div>

            <!-- Divider -->
            <div class="border-t border-gray-200 my-6"></div>

            <!-- Form -->
            <form method="POST" action="{{ route('verification.resend') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                
                <!-- Resend Button -->
                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold py-3 px-6 rounded-lg shadow-lg hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-blue-200 transform transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] hover:shadow-xl"
                >
                    <span class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span>Resend Verification Email</span>
                    </span>
                </button>
            </form>

            <!-- Footer Note -->
            <div class="mt-6 pt-6 border-t border-gray-200">
                <p class="text-sm text-gray-500 text-center">
                    Check your spam folder if you don't receive the email within a few minutes.
                </p>
            </div>
        </div>

        <!-- Additional Help -->
        <div class="text-center mt-6">
            <p class="text-sm text-gray-500">
                Need help? 
                <a href="#" class="text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">Contact Support</a>
            </p>
        </div>
    </div>
</body>
</html>
