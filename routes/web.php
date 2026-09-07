<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateController;
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
use App\Http\Controllers\NotificationController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
});

Route::get('/verify-email/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = User::findOrFail($id);

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
})->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/verification/resend', [AuthController::class, 'resendVerification'])
    ->middleware('throttle:verification-resend')
    ->name('verification.resend');

Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])
    ->name('password.reset');

// routes/web.php
Route::post('/reset-password', [AuthController::class, 'resetPassword'])
    ->middleware('throttle:password-reset')
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

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:registration');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:password-recovery');
Route::get('/certificates/verify/{verificationCode}', [CertificateController::class, 'verify']);

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
    Route::get('/projects/unread-counts', [ApiProjectMessageController::class, 'unreadCounts']);
    Route::get('/projects/{project}/certificate', [CertificateController::class, 'show']);
    Route::get('/projects/{project}/certificate/download', [CertificateController::class, 'download']);

    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    Route::prefix('projects/{project}')->group(function () {
        Route::get('messages', [ApiProjectMessageController::class, 'index'])
            ->middleware('project.permission:view_messages');
        Route::get('messages/changes', [ApiProjectMessageController::class, 'changes'])
            ->middleware('project.permission:view_messages');
        Route::post('messages/read', [ApiProjectMessageController::class, 'markRead'])
            ->middleware('project.permission:view_messages');
        Route::get('members', [ProjectMemberController::class, 'index'])
            ->middleware('project.permission:view_members');
        Route::post('messages', [ApiProjectMessageController::class, 'store'])
            ->middleware(['project.permission:send_messages', 'throttle:project-messages']);
        Route::patch('messages/{message}', [ApiProjectMessageController::class, 'update'])
            ->middleware('project.permission:send_messages');
        Route::delete('messages/{message}', [ApiProjectMessageController::class, 'destroy'])
            ->middleware('project.permission:send_messages');
        Route::post('attachments', [ProjectAttachmentController::class, 'store'])
            ->middleware('project.permission:upload_attachments');
        Route::delete('attachments/{attachment}', [ProjectAttachmentController::class, 'destroy']);
        Route::post('members', [ProjectMemberController::class, 'store'])
            ->middleware('project.permission:manage_members');
        Route::patch('members/{userId}/role', [ProjectMemberController::class, 'updateRole'])
            ->middleware('project.permission:manage_roles');
        Route::delete('members/{userId}', [ProjectMemberController::class, 'destroy'])
            ->middleware('project.permission:manage_members');
    });
    Route::post('messages/{message}/reactions', [MessageReactionController::class, 'toggle'])
        ->middleware(['project.permission:send_messages', 'throttle:project-reactions']);

    Route::put('/user/update-profile-pic', [UserController::class, 'updateImage']);
    Route::patch('/user/profile', [UserController::class, 'updateProfile']);
    Route::put('/user/update-password', [UserController::class, 'updatePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/form-inputs', [FormController::class, 'getFormInputs']);
    Route::get('/projects/{projectId}', [ProjectController::class, 'showSelectedProject']);
    Route::get('/users/{userId}/projects', [ProjectController::class, 'getUserProjects']);
    Route::get('/users/{userId}/projects/actual-ratings', [ProjectController::class, 'getUserActualRatings']);
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
