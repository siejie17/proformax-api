<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ResultsController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);

// Resend verification link
Route::post('/email/verification-notification', function (Request $request) {
    if ($request->user()->hasVerifiedEmail()) {
        return response()->json(['message' => 'Already verified']);
    }

    $request->user()->sendEmailVerificationNotification();
    return response()->json(['message' => 'Verification link sent!']);
})->middleware(['auth:sanctum'])->name('verification.send');

// Example of route that requires verified email
Route::get('/profile', function (Request $request) {
    return response()->json($request->user());
})->middleware(['auth:sanctum', 'verified']);

Route::middleware('auth:sanctum')->put('/user/update-profile-pic', [UserController::class, 'updateImage']);

Route::middleware('auth:sanctum')->group(function () {
    Route::patch('/user/profile', [UserController::class, 'updateProfile']);
    Route::put('/user/update-password', [UserController::class, 'updatePassword']);
});

Route::post('/login', [AuthController::class, 'login']);

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/categories', [CategoryController::class, 'index']);

Route::post('/results', [ResultsController::class, 'getResults']);