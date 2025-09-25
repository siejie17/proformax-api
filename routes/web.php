<?php

use App\Http\Controllers\AuthController;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/verify-email/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = User::findOrFail($id);

    if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        return view('auth.verification-failed');
    }

    if ($user->hasVerifiedEmail()) {
        return view('auth.verification-already');
    }

    if ($user->markEmailAsVerified()) {
        event(new Verified($user));
    }

    return view('auth.verification-success');
})->middleware(['signed'])->name('verification.verify');

Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])
    ->name('password.reset');

// routes/web.php
Route::post('/reset-password', [AuthController::class, 'resetPassword'])
    ->name('password.update');

Route::get('/link-expired', function () {
    return view('link-expired');
})->name('password.expired');

