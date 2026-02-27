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
            'password'   => 'required|string|min:6',
        ]);
    
        DB::beginTransaction();
    
        try {
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
    
            $user->sendEmailVerificationNotification();
    
            $token = $user->createToken('auth_token')->plainTextToken;
    
            DB::commit();
    
            return response()->json([
                'message' => 'User registered successfully. Please check your email for verification link.',
                'user' => $user,
                'token' => $token,
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

        if (! $user) {
            return response()->json([
                'message' => 'Invalid credentials',
                'errors' => [
                    'email' => ['No account found for this email.']
                ]
            ], 401);
        }

        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
                'errors' => [
                    'password' => ['Incorrect password.']
                ]
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    // Forgot password (send reset link)
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Reset link sent to your email.'])
            : response()->json(['message' => 'Unable to send reset link.'], 400);
    }

    // Reset password (using token)
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);
        
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));
 
                $user->save();
 
                event(new PasswordReset($user));
            }
        );

        // If the reset was successful
        if ($status === Password::PasswordReset) {
            return view('auth.password-reset-success');
        }
        
        // If the token was invalid or expired...
        if ($status === Password::INVALID_TOKEN) {
            return redirect()->route('link.expired')->with('reset_expired', true);
        }
    
        // For any other error (like user not found)
        return back()->withErrors(['email' => [__($status)]]);
    }

    public function logout(Request $request)
    {
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
