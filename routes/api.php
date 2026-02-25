<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\ResultsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ThreeDController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);

// Resend verification link
Route::post('/email/verification-notification', function (Request $request) {
    if ($request->user()->hasVerifiedEmail()) {
        return response()->json(['message' => 'Already verified']);
    }
    $request->user()->sendEmailVerificationNotification();
    return response()->json(['message' => 'Verification link sent!']);
})->name('verification.send');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Example of route that requires verified email
Route::get('/profile', function (Request $request) {
    return response()->json($request->user());
})->middleware('verified');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::put('/user/update-profile-pic', [UserController::class, 'updateImage']);
    Route::patch('/user/profile', [UserController::class, 'updateProfile']);
    Route::put('/user/update-password', [UserController::class, 'updatePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/form-inputs', [FormController::class, 'getFormInputs']);
    Route::post('/results', [ResultsController::class, 'getResults']);
    Route::post('/submit-assessment', [ResultsController::class, 'submitAssessment']);
    Route::get('/users/{userId}/projects', [ProjectController::class, 'getUserProjects']);
    Route::get('/projects/{projectId}', [ProjectController::class, 'showSelectedProject']);
    Route::get('/users/{userId}', [UserController::class, 'getUserById']);
    Route::get('/users/{userId}/preferences', [UserController::class, 'getPreferences']);
    Route::patch('/users/{userId}/preferences', [UserController::class, 'updatePreferences']);
});