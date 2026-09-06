<?php

namespace App\Http\Controllers;

use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use App\Support\ActivityLogger;

class AuthController extends Controller
{
    /**
     * Handle user registration.
     */
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name'  => 'required|string|max:50',
            'email'      => 'required|string|email|unique:users',
            'password'   => [
                'required',
                'string',
                PasswordRule::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ]);
    
        DB::beginTransaction();
    
        try {
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                "role_id" => 4,
            ]);
    
            $user->sendEmailVerificationNotification();
    
            DB::commit();

            ActivityLogger::record($user, 'account_created', $user);
    
            return response()->json([
                'message' => 'User registered successfully. Please check your email for verification link.',
                'user' => $user,
            ], 201);
    
        } catch (\Exception $e) {
    
            DB::rollBack();
    
            Log::error('Registration failed: ' . $e->getMessage());
    
            return response()->json([
                'message' => 'Registration failed. Please try again later.'
            ], 500);
        }
    }

    /**
     * Handle user login.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please verify your email before signing in.',
                'reason' => 'email_unverified',
                'user' => $user,
            ], 403);
        }

        if (! $user->email_verified_at) {
            return response()->json([
                'message' => 'Please verify your email before logging in. Check your inbox for the verification link.',
                'code' => 'email_not_verified',
            ], 403);
        }

        if (! $user->email_verified_at) {
            return response()->json([
                'message' => 'Please verify your email before logging in. Check your inbox for the verification link.',
                'code' => 'email_not_verified',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        ActivityLogger::record($user, 'user_login', $user);

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Resend an email verification link without revealing account state.
     */
    public function resendVerification(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            return response()->json([
                'message' => 'No account found for this email.',
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'This email is already verified. You can log in.',
            ], 200);
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Exception $e) {
            Log::error('Resend verification failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Unable to send the verification email. Please try again later.',
            ], 500);
        }

        return response()->json([
            'message' => 'Verification link sent. Please check your email.',
        ], 200);
    }

    // Forgot password (send reset link)
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            Password::sendResetLink($request->only('email'));
        } catch (\Throwable $exception) {
            Log::error('Password reset link delivery failed.', [
                'exception' => $exception,
            ]);
        }

        return response()->json([
            'message' => 'If an account exists for that email, a password reset link has been sent.',
        ]);
    }

    /**
     * Reset a password from the browser form.
     */
    public function resetPassword(Request $request)
    {
        return $this->performPasswordReset($request, false);
    }

    /**
     * Reset a password through the JSON API.
     */
    public function resetPasswordApi(Request $request)
    {
        return $this->performPasswordReset($request, true);
    }

    private function performPasswordReset(Request $request, bool $jsonResponse)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => [
                'required',
                'string',
                'confirmed',
                PasswordRule::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PasswordReset) {
            return $jsonResponse
                ? response()->json(['message' => 'Password reset successfully.'])
                : view('auth.password-reset-success');
        }

        if ($jsonResponse) {
            return response()->json([
                'message' => 'Unable to reset password.',
                'errors' => [
                    'token' => ['The password reset token is invalid or expired.'],
                ],
            ], 422);
        }

        return redirect()->route('link.expired')->with('reset_expired', true);
    }

    public function logout(Request $request)
    {
        ActivityLogger::record($request->user(), 'user_logout', $request->user());
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function showResetForm(Request $request, $token = null)
    {
        $email = $request->query('email');

        // check token validity in DB
        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (! $record || ! Hash::check($token, $record->token)) {
            return redirect()->route('link.expired')->with('reset_expired', true);
        }

        // Check expiry (default 60 minutes from created_at)
        if (Carbon::parse($record->created_at)
            ->addMinutes(config('auth.passwords.users.expire'))
            ->isPast()
        ) {
            return redirect()->route('link.expired')->with('reset_expired', true);
        }

        // Token valid → show form
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }
}
