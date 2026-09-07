<?php

use App\Http\Controllers\Administration\ActivityLogController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Administration\AssessmentController;
use App\Http\Controllers\Administration\DashboardController;
use App\Http\Controllers\Administration\RecommendationController;
use App\Http\Controllers\Administration\ReferenceController;
use App\Http\Controllers\Administration\UserManagementController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\MessageReactionController;
use App\Http\Controllers\Api\ProjectAttachmentController;
use App\Http\Controllers\Api\ProjectMemberController;
use App\Http\Controllers\Api\ProjectMessageController as ApiProjectMessageController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\ResultsController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:registration');

Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
    ->middleware('throttle:verification-resend')
    ->name('verification.send');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:password-recovery');
Route::post('/reset-password', [AuthController::class, 'resetPasswordApi'])
    ->middleware('throttle:password-reset');
Route::get('/certificates/verify/{verificationCode}', [CertificateController::class, 'verify']);

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
    Route::get('/roles', [UserController::class, 'roles']);
    Route::get('/projects/unread-counts', [ApiProjectMessageController::class, 'unreadCounts']);
    Route::get('/projects/{project}/certificate', [CertificateController::class, 'show']);
    Route::get('/projects/{project}/certificate/download', [CertificateController::class, 'download']);

    // Serve/download project attachments (authorization checked inside the controller).
    Route::get('/media/{filename}', [MediaController::class, 'show']);
    Route::get('/media/{filename}/download', [MediaController::class, 'download']);

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
    Route::get('/push/config', [PushSubscriptionController::class, 'config']);
    Route::post('/push/subscriptions', [PushSubscriptionController::class, 'store']);
    Route::delete('/push/subscriptions', [PushSubscriptionController::class, 'destroy']);
    Route::put('/user/update-password', [UserController::class, 'updatePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/form-inputs', [FormController::class, 'getFormInputs']);
    Route::get('/analytics', [AnalyticsController::class, 'index']);
    Route::get('/projects/{projectId}', [ProjectController::class, 'showSelectedProject'])
        ->middleware('project.viewer');
    Route::get('/users/{userId}/projects', [ProjectController::class, 'getUserProjects']);
    Route::get('/users/{userId}/projects/actual-ratings', [ProjectController::class, 'getUserActualRatings']);
    Route::get('/users/{userId}/projects/added-by-me', [ProjectController::class, 'getUserAddedMemberProjects']);
    Route::get('/users/{userId}/projects/added-to-me', [ProjectController::class, 'getUserAddedProjects']);
    Route::get('/users/{userId}', [UserController::class, 'getUserById']);
    Route::get('/users/{userId}/preferences', [UserController::class, 'getPreferences']);
    Route::patch('/users/{userId}/preferences', [UserController::class, 'updatePreferences']);
    // Read-only knowledge content for every authenticated user. Mutations remain
    // protected inside the administration routes below.
    Route::get('/content/references', [ReferenceController::class, 'publicIndex']);
    Route::get('/content/recommendations', [RecommendationController::class, 'publicIndex']);
    Route::post('/results', [ResultsController::class, 'getResults']);
    Route::post('/submit-assessment', [ResultsController::class, 'submitAssessment']);
    Route::post('/projects/update-actual-cost', [ProjectController::class, 'updateActualCost']);
    Route::get('/projects/{projectId}/certification-cost', [ProjectController::class, 'getProjectCertificationCost']);
    Route::post('/projects/{projectId}/save-actual-changes', [ProjectController::class, 'saveProjectActualChanges']);
    Route::post('/assessment/prediction-cost', [ResultsController::class, 'getRealTimePrediction']);

    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    Route::prefix('administration')->group(function () {
        Route::middleware('system.role:super_admin')->prefix('super-admin')->group(function () {
            Route::get('/activity-logs', [ActivityLogController::class, 'index']);
            Route::get('/users', [UserManagementController::class, 'index']);
            Route::patch('/users/{user}', [UserManagementController::class, 'updateAccount']);
            Route::patch('/users/{user}/role', [UserManagementController::class, 'updateRole']);
            Route::delete('/users/{user}', [UserManagementController::class, 'destroy']);
        });

        Route::middleware('system.role:admin,super_admin')->prefix('admin')->group(function () {
            Route::get('/dashboard', DashboardController::class);
            Route::get('/users', [UserManagementController::class, 'index']);
            Route::patch('/users/{user}/role', [UserManagementController::class, 'updateRole']);
            Route::delete('/users/{user}', [UserManagementController::class, 'destroy']);
            Route::put('/references/upload', [ReferenceController::class, 'upload']);
            Route::apiResource('references', ReferenceController::class)->except(['show']);
            Route::patch('/recommendation-section', [RecommendationController::class, 'updateSection']);
            Route::apiResource('recommendations', RecommendationController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::get('/assessments', [AssessmentController::class, 'index']);
            Route::get('/assessments/{project}', [AssessmentController::class, 'show']);
            Route::post('/assessments/{project}/assign', [AssessmentController::class, 'assign']);
            Route::patch('/assignments/{assignment}', [AssessmentController::class, 'replace']);
            Route::patch('/assessments/{project}/scores', [AssessmentController::class, 'rejectPredictedScoreUpdate']);
            Route::patch('/assessments/{project}/actual-selections', [AssessmentController::class, 'updateActualSelections']);
            Route::post('/assessments/{project}/review', [AssessmentController::class, 'review']);
            Route::delete('/assessments/{project}/certificate', [CertificateController::class, 'revoke']);
            Route::delete('/assignments/{assignment}', [AssessmentController::class, 'revoke']);
        });

        Route::middleware('system.role:facilitator_admin')->prefix('facilitator')->group(function () {
            Route::get('/assessments', [AssessmentController::class, 'index']);
            Route::get('/assessments/{project}', [AssessmentController::class, 'show']);
            Route::patch('/assessments/{project}/scores', [AssessmentController::class, 'rejectPredictedScoreUpdate']);
            Route::patch('/assessments/{project}/actual-selections', [AssessmentController::class, 'updateActualSelections']);
            Route::post('/assessments/{project}/review', [AssessmentController::class, 'review']);
        });
    });
});
