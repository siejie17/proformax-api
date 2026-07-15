<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
});

Route::get('/verify-email/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = User::findOrFail($id);

    $expires = $request->query('expires');

    // Check expiration manually
    if ($expires && now()->timestamp > $expires) {
        return view('auth.verification-expired', ['email' => $user->email]);
    }

    // Check if the hash matches
    if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        return view('auth.verification-failed', ['email' => $user->email]);
    }

    if ($user->hasVerifiedEmail()) {
        return view('auth.verification-already', ['email' => $user->email]);
    }

    if ($user->markEmailAsVerified()) {
        event(new Verified($user));
    }

    return view('auth.verification-success');
})->name('verification.verify');

Route::post('/verification/resend', function (Request $request) {
    $request->validate([
        'email' => 'required|email|exists:users,email',
    ]);

    $user = User::where('email', $request->email)->firstOrFail();

    if ($user->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email is already verified.'], 400);
    }

    // Laravel built-in method sends the verification email
    $user->sendEmailVerificationNotification();

    return response()->json(['message' => 'Verification email resent.']);
})->name('verification.resend');

Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])
    ->name('password.reset');

// routes/web.php
Route::post('/reset-password', [AuthController::class, 'resetPassword'])
    ->name('password.update');

// routes/web.php
Route::get('/reset-password/success', function () {
    return view('auth.password-reset-success');
})->name('password.reset.success');

Route::get('/link-expired', function () {
    if (! session('reset_expired')) {
        abort(404); // show 404 page
    }

    return view('link-expired');
})->name('link.expired');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/me', function (Request $request) {
        return response()->json($request->user());
    });

    Route::get('/users/{userId}/projects', [ProjectController::class, 'getUserProjects']);
});