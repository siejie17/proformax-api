<?php

use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\MessageReactionController;
use App\Http\Controllers\Api\ProjectAttachmentController;
use App\Http\Controllers\Api\ProjectMemberController;
use App\Http\Controllers\Api\ProjectMessageController as ApiProjectMessageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\ResultsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectController;

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
    // Return the authenticated user (used by frontend at /api/me)
    Route::get('/me', function (Request $request) {
        return response()->json(
            $request->user()->load('role')->toArray(),
            200,
            [],
            JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE
        );
    });
    // Search users to invite to a project (realtime picker).
    Route::get('/users', [UserController::class, 'search']);

    // Serve/download project attachments (authorization checked inside the controller).
    Route::get('/media/{filename}', [MediaController::class, 'show']);
    Route::get('/media/{filename}/download', [MediaController::class, 'download']);

     // Read-only routes: any project member (viewer+) can access.
     Route::middleware('project.viewer')->group(function () {
         Route::prefix('projects/{project}')->group(function () {
             Route::get('messages', [ApiProjectMessageController::class, 'index']);
             Route::get('members', [ProjectMemberController::class, 'index']);
         });
     });

     // Write routes: editor+ (developer, quantity_surveyor, gbi_facilitator) or owner.
     Route::middleware('project.editor')->group(function () {
         Route::prefix('projects/{project}')->group(function () {
             Route::post('messages', [ApiProjectMessageController::class, 'store']);
             Route::post('attachments', [ProjectAttachmentController::class, 'store']);
             Route::post('members', [ProjectMemberController::class, 'store']);
             Route::post('members/{userId}/role', [ProjectMemberController::class, 'updateRole']);
         });
         Route::post('messages/{message}/reactions', [MessageReactionController::class, 'toggle']);
         Route::delete('members/{userId}', [ProjectMemberController::class, 'destroy']);
     });

    Route::put('/user/update-profile-pic', [UserController::class, 'updateImage']);
    Route::patch('/user/profile', [UserController::class, 'updateProfile']);
    Route::put('/user/update-password', [UserController::class, 'updatePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/form-inputs', [FormController::class, 'getFormInputs']);
    Route::get('/projects/{projectId}', [ProjectController::class, 'showSelectedProject']);
    Route::get('/users/{userId}/projects', [ProjectController::class, 'getUserProjects']);
    Route::get('/users/{userId}/projects/added-by-me', [ProjectController::class, 'getUserAddedMemberProjects']);
    Route::get('/users/{userId}/projects/added-to-me', [ProjectController::class, 'getUserAddedProjects']);
    Route::get('/users/{userId}', [UserController::class, 'getUserById']);
    Route::get('/users/{userId}/preferences', [UserController::class, 'getPreferences']);
    Route::patch('/users/{userId}/preferences', [UserController::class, 'updatePreferences']);
    Route::post('/results', [ResultsController::class, 'getResults']);
    Route::get('/projects/{projectId}', [ProjectController::class, 'showSelectedProject']);
    Route::post('/submit-assessment', [ResultsController::class, 'submitAssessment']);
    Route::post('/projects/update-actual-cost', [ProjectController::class, 'updateActualCost']);
    Route::get('/projects/{projectId}/certification-cost', [ProjectController::class, 'getProjectCertificationCost']);
    Route::post('/projects/{projectId}/save-actual-changes', [ProjectController::class, 'saveProjectActualChanges']);
    Route::post('/assessment/prediction-cost', [ResultsController:: class, 'getRealTimePrediction']);
});