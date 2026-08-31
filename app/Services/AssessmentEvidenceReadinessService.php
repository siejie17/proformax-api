<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssessmentEvidenceReadinessService
{
    private const READY_ACTION = 'assessment_evidence_ready';

    private const INCOMPLETE_ACTION = 'assessment_evidence_incomplete';

    public function __construct(
        private readonly AssessmentScoreService $scores,
        private readonly NotificationDeliveryService $notifications,
    ) {}

    public function sync(Project $project, ?User $actor = null): bool
    {
        $transition = DB::transaction(function () use ($project, $actor) {
            $lockedProject = Project::query()->lockForUpdate()->findOrFail($project->id);
            $predictedItems = collect($this->scores->breakdown($lockedProject)['items'])
                ->filter(fn (array $item) => collect($item['predicted_choices'])
                    ->contains(fn (array $choice) => (bool) $choice['selected']));
            $isReady = $predictedItems->isNotEmpty()
                && $predictedItems->every(fn (array $item) => ! empty($item['evidence']));

            $lastAction = ActivityLog::query()
                ->where('target_type', $lockedProject->getMorphClass())
                ->where('target_id', $lockedProject->id)
                ->whereIn('action', [self::READY_ACTION, self::INCOMPLETE_ACTION])
                ->latest('id')
                ->value('action');

            if (($isReady && $lastAction === self::READY_ACTION)
                || (! $isReady && $lastAction !== self::READY_ACTION)) {
                return null;
            }

            ActivityLogger::record(
                $actor,
                $isReady ? self::READY_ACTION : self::INCOMPLETE_ACTION,
                $lockedProject,
                'success',
                [
                    'predicted_items' => $predictedItems->count(),
                    'items_with_evidence' => $predictedItems->filter(fn (array $item) => ! empty($item['evidence']))->count(),
                ],
            );

            return $isReady ? $predictedItems->count() : false;
        });

        if (! is_int($transition)) {
            return false;
        }

        $this->notifyReviewers($project->fresh(), $transition);

        return true;
    }

    private function notifyReviewers(Project $project, int $predictedItemCount): void
    {
        $administratorIds = User::query()
            ->whereIn('system_role', ['admin', 'super_admin'])
            ->pluck('id');
        $facilitatorIds = $project->facilitatorAssignments()
            ->where('status', 'active')
            ->pluck('user_id');
        $recipientIds = $administratorIds->merge($facilitatorIds)->unique()->values();

        foreach (User::query()->whereIn('id', $recipientIds)->get() as $recipient) {
            try {
                $this->notifications->deliver(
                    $recipient,
                    'Assessment evidence ready for review',
                    "All {$predictedItemCount} Predicted GBI items for {$project->name} now have supporting evidence and are ready for Actual review.",
                    $recipient->hasSystemRole('facilitator_admin') ? '/facilitator' : '/admin/assessments',
                );
            } catch (\Throwable $exception) {
                Log::warning('Unable to deliver assessment evidence readiness notification.', [
                    'project_id' => $project->id,
                    'recipient_id' => $recipient->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }
}
