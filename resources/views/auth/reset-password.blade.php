<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="antialiased bg-gray-100">
    <div id="app">
        <div
            class="min-h-screen bg-gradient-to-br from-slate-50 via-green-50 to-emerald-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div
                        class="mx-auto h-16 w-16 bg-gradient-to-r from-green-600 to-emerald-600 rounded-full flex items-center justify-center mb-4 shadow-lg">
                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Reset Password</h2>
                    <p class="text-gray-600">Enter your new password below</p>
                </div>

                <!-- Form Card -->
                <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
                    <form method="POST" action="{{ route('password.update') }}" class="space-y-6">
                        @csrf

                        <!-- Hidden token field -->
                        <input type="hidden" name="token" value="{{ $token }}">

                        <!-- Email Field (readonly) -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address
                            </label>
                            <input id="email" name="email" type="email" value=""
                                readonly
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 text-gray-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200">
                            @error('email')
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- New Password Field -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                New Password
                            </label>
                            <div class="relative">
                                <input id="password" name="password" type="password" required
                                    autocomplete="new-password"
                                    class="w-full px-4 py-3 border rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 @error('password') border-red-300 @enderror"
                                    placeholder="Enter your new password">
                                <button type="button" onclick="togglePassword('password')"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                                    <svg id="password-eye" class="w-5 h-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Confirm Password Field -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                                Confirm Password
                            </label>
                            <div class="relative">
                                <input id="password_confirmation" name="password_confirmation" type="password" required
                                    autocomplete="new-password"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                    placeholder="Confirm your new password">
                                <button type="button" onclick="togglePassword('password_confirmation')"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                                    <svg id="password_confirmation-eye" class="w-5 h-5" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Password Requirements -->
                        <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                            <h4 class="text-sm font-medium text-green-900 mb-2">Password Requirements:</h4>
                            <ul class="text-sm text-green-800 space-y-1">
                                <li class="flex items-center">
                                    <svg class="w-3 h-3 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    At least 8 characters long
                                </li>
                                <li class="flex items-center">
                                    <svg class="w-3 h-3 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Contains uppercase and lowercase letters
                                </li>
                                <li class="flex items-center">
                                    <svg class="w-3 h-3 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Contains at least one number
                                </li>
                            </ul>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit"
                            class="w-full bg-gradient-to-r from-green-600 to-emerald-600 text-white font-semibold py-3 px-4 rounded-xl hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transform hover:scale-[1.02] transition-all duration-200 shadow-lg">
                            Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <script>
            function togglePassword(fieldId) {
                const field = document.getElementById(fieldId);
                const eye = document.getElementById(fieldId + '-eye');

                if (field.type === 'password') {
                    field.type = 'text';
                    eye.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L8.464 8.464m1.414 1.414L8.464 8.464"/>
        `;
                } else {
                    field.type = 'password';
                    eye.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        `;
                }
            }
        </script>
    </div>
</body>

</html>
