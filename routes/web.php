<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ResultsController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\Api\MessageReactionController;
use App\Http\Controllers\Api\ProjectAttachmentController;
use App\Http\Controllers\Api\ProjectMemberController;
use App\Http\Controllers\Api\ProjectMessageController as ApiProjectMessageController;   

use App\Http\Controllers\FormController;

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
    // Return the authenticated user (used by frontend at /api/me)
    Route::get('/me', function (Request $request) {
        return response()->json(
            $request->user()->toArray(),
            200,
            [],
            JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE
        );
    });
    // Search users to invite to a project (realtime picker).
    Route::get('/users', [UserController::class, 'search']);

     Route::middleware('project.member')->group(function () {
        Route::prefix('projects/{project}')->group(function () {
            Route::get('messages', [ApiProjectMessageController::class, 'index']);
            Route::post('messages', [ApiProjectMessageController::class, 'store']);
            Route::post('attachments', [ProjectAttachmentController::class, 'store']);
            Route::get('members', [ProjectMemberController::class, 'index']);
            Route::post('members', [ProjectMemberController::class, 'store']);

            // owner-only
        });
        Route::post('messages/{message}/reactions', [MessageReactionController::class, 'toggle'])->middleware('project.member');
        Route::delete('members/{userId}', [ProjectMemberController::class, 'destroy']);
    });

    Route::put('/user/update-profile-pic', [UserController::class, 'updateImage']);
    Route::patch('/user/profile', [UserController::class, 'updateProfile']);
    Route::put('/user/update-password', [UserController::class, 'updatePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/form-inputs', [FormController::class, 'getFormInputs']);
    Route::get('/projects/{projectId}', [ProjectController::class, 'showSelectedProject']);
    Route::get('/users/{userId}/projects', [ProjectController::class, 'getUserProjects']);
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