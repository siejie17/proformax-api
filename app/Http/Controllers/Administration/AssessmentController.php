<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\AssessmentItemReview;
use App\Models\AssessmentReview;
use App\Models\FacilitatorAssignment;
use App\Models\Project;
use App\Models\Recommendation;
use App\Models\User;
use App\Services\AssessmentScoreService;
use App\Services\CertificateService;
use App\Services\NotificationDeliveryService;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AssessmentController extends Controller
{
    public function __construct(
        private readonly AssessmentScoreService $scoreService,
        private readonly CertificateService $certificates,
        private readonly NotificationDeliveryService $notifications,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Project::with([
            'owner:id,first_name,last_name,email',
            'facilitatorAssignments' => fn ($q) => $q->where('status', 'active')->with('facilitator:id,first_name,last_name,email'),
        ]);

        if ($request->user()->system_role === 'facilitator_admin') {
            $query->whereHas('facilitatorAssignments', fn ($q) => $q
                ->where('user_id', $request->user()->id)
                ->where('status', 'active'));
        }

        $query->when($request->filled('status'), function ($q) use ($request) {
            $status = (string) $request->string('status');
            if ($status === 'awaiting_verification') {
                $q->whereIn('assessment_status', ['submitted', 'pending_verification']);
            } elseif ($status === 'changes_requested') {
                $q->where('assessment_status', 'requires_changes');
            } else {
                $q->where('assessment_status', $status);
            }
        })
            ->when($request->filled('q'), function ($q) use ($request) {
                $search = '%' . $request->string('q')->trim() . '%';
                $q->where(fn ($inner) => $inner->where('name', 'like', $search)
                    ->orWhereHas('owner', fn ($owner) => $owner->where('email', 'like', $search)));
            });

        return response()->json($query->latest('created_at')->paginate(20));
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        $this->ensureAccess($request, $project);
        $project->load([
            'owner',
            'attachments',
            'reviews.reviewer:id,first_name,last_name,email',
            'facilitatorAssignments' => fn ($q) => $q
                ->where('status', 'active')
                ->with('facilitator:id,first_name,last_name,email'),
        ]);

        $recommendation = Recommendation::where('is_active', true)
            ->whereRaw('LOWER(certification_level) = ?', [mb_strtolower((string) $project->target_certification)])
            ->first();

        return response()->json([
            'assessment' => $project,
            'recommendation' => $recommendation,
            'score_review' => $this->scoreService->breakdown($project),
            'certificate' => $this->certificates->payload($project->certificates()->latest('id')->first()),
        ]);
    }

    public function assign(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);
        $facilitator = User::findOrFail($data['user_id']);

        if ($facilitator->system_role !== 'facilitator_admin') {
            return response()->json(['message' => 'The selected user is not a Facilitator Admin.'], 422);
        }

        $assignment = FacilitatorAssignment::updateOrCreate(
            ['project_id' => $project->id, 'user_id' => $facilitator->id],
            ['appointed_by' => $request->user()->id, 'status' => 'active', 'appointed_at' => now(), 'revoked_at' => null],
        );
        ActivityLogger::record($request->user(), 'facilitator_assigned', $project, 'success', ['facilitator_id' => $facilitator->id]);

        $this->notifications->deliver(
            $facilitator,
            'Facilitator assignment',
            'You were assigned to review '.$project->name.'.',
            '/facilitator',
        );

        return response()->json($assignment->load('facilitator'));
    }

    public function revoke(Request $request, FacilitatorAssignment $assignment): JsonResponse
    {
        $facilitator = $assignment->facilitator;
        $assignment->update(['status' => 'revoked', 'revoked_at' => now()]);
        ActivityLogger::record($request->user(), 'facilitator_assignment_revoked', $assignment->project, 'success', ['facilitator_id' => $assignment->user_id]);

        $this->notifications->deliver(
            $facilitator,
            'Facilitator assignment revoked',
            'Your assignment to '.$assignment->project->name.' was revoked.',
            '/facilitator',
        );

        return response()->json(['message' => 'Appointment revoked.']);
    }

    public function replace(Request $request, FacilitatorAssignment $assignment): JsonResponse
    {
        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);
        $facilitator = User::findOrFail($data['user_id']);

        if ($facilitator->system_role !== 'facilitator_admin') {
            return response()->json(['message' => 'The selected user is not a Facilitator Admin.'], 422);
        }

        if ((int) $assignment->user_id === (int) $facilitator->id && $assignment->status === 'active') {
            return response()->json($assignment->load('facilitator'));
        }

        $previousFacilitator = $assignment->facilitator;
        $replacement = DB::transaction(function () use ($request, $assignment, $facilitator) {
            $assignment->update(['status' => 'revoked', 'revoked_at' => now()]);

            return FacilitatorAssignment::updateOrCreate(
                ['project_id' => $assignment->project_id, 'user_id' => $facilitator->id],
                ['appointed_by' => $request->user()->id, 'status' => 'active', 'appointed_at' => now(), 'revoked_at' => null],
            );
        });

        ActivityLogger::record($request->user(), 'facilitator_assignment_changed', $assignment->project, 'success', [
            'from_facilitator_id' => $assignment->user_id,
            'to_facilitator_id' => $facilitator->id,
        ]);

        $this->notifications->deliver(
            $previousFacilitator,
            'Facilitator assignment changed',
            'Your assignment to '.$assignment->project->name.' was reassigned.',
            '/facilitator',
        );
        $this->notifications->deliver(
            $facilitator,
            'Facilitator assignment',
            'You were assigned to review '.$assignment->project->name.'.',
            '/facilitator',
        );

        return response()->json($replacement->load('facilitator'));
    }

    public function review(Request $request, Project $project): JsonResponse
    {
        $this->ensureAccess($request, $project);
        $data = $request->validate([
            'action' => ['required', Rule::in(['verify', 'certify', 'reject', 'reopen'])],
            'remarks' => [
                Rule::requiredIf(fn () => $request->input('action') === 'reject'),
                'nullable',
                'string',
                'max:5000',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('action') !== 'reject') {
                        return;
                    }

                    $feedback = trim((string) $value);
                    $wordCount = count(preg_split('/\s+/u', $feedback, -1, PREG_SPLIT_NO_EMPTY));
                    if (mb_strlen($feedback) < 80 || $wordCount < 12) {
                        $fail('Change request feedback must be at least 80 characters and 12 words so the applicant receives clear, actionable guidance.');
                    }
                },
            ],
        ]);

        $scoreReview = $this->scoreService->breakdown($project);

        if ($data['action'] === 'certify') {
            $existingCertificate = $project->certificates()->where('status', 'issued')->latest('id')->first();
            if ($existingCertificate) {
                $existingCertificate = $this->certificates->ensurePdf($existingCertificate);

                return response()->json([
                    'message' => 'The project certificate has already been issued.',
                    'assessment' => $project->fresh(),
                    'review' => $existingCertificate->assessmentReview?->load('reviewer:id,first_name,last_name,email'),
                    'score_review' => $scoreReview,
                    'certificate' => $this->certificates->payload($existingCertificate),
                ]);
            }
        }

        if ($data['action'] === 'reopen') {
            if (! $request->user()->hasSystemRole('admin', 'super_admin', 'facilitator_admin')) {
                abort(403, 'Only an administrator or the appointed Facilitator Admin can undo a Require changes decision.');
            }

            if ($project->assessment_status !== 'requires_changes') {
                return response()->json([
                    'message' => 'Only a project requiring changes can be reopened.',
                ], 422);
            }
        }

        if (in_array($data['action'], ['verify', 'reject'], true)
            && in_array($project->assessment_status, ['verified', 'certified', 'requires_changes'], true)) {
            return response()->json([
                'message' => 'The Predicted assessment decision is final for this project version.',
            ], 422);
        }

        if ($data['action'] === 'certify') {
            if (! in_array($project->assessment_status, ['verified', 'certified'], true)) {
                return response()->json([
                    'message' => 'Verify the Predicted assessment before reviewing and certifying the Actual assessment.',
                ], 422);
            }

            if (! $scoreReview['all_actual_reviewed'] || $scoreReview['total_items'] === 0) {
                return response()->json([
                    'message' => 'Review and save the Actual score for every assessment item before certifying.',
                ], 422);
            }
        }

        if ($data['action'] === 'certify' && ($scoreReview['calculated_certification_level'] === null || $scoreReview['calculated_certification_level'] === 'Not Certified')) {
            return response()->json([
                'message' => 'The Actual total does not qualify for a configured certification level.',
            ], 422);
        }

        $previous = $project->assessment_status;
        $newStatus = match ($data['action']) {
            'verify' => 'verified',
            'certify' => 'certified',
            'reject' => 'requires_changes',
            'reopen' => AssessmentReview::query()
                ->where('project_id', $project->id)
                ->where('action', 'reject')
                ->where('new_status', 'requires_changes')
                ->latest('id')
                ->value('previous_status') ?: 'submitted',
        };
        if ($data['action'] === 'reopen' && ! in_array($newStatus, ['submitted', 'pending_verification', 'verified', 'certified'], true)) {
            $newStatus = 'submitted';
        }
        $approvedActualTotal = $data['action'] === 'certify' ? $scoreReview['actual_total'] : null;
        $certificationLevel = $data['action'] === 'certify'
            ? $scoreReview['calculated_certification_level']
            : null;

        $certificate = null;
        $alreadyIssued = false;
        $reviewRecord = DB::transaction(function () use ($request, $project, $data, $previous, $newStatus, $approvedActualTotal, $certificationLevel, $scoreReview, &$certificate, &$alreadyIssued) {
            if ($data['action'] === 'certify') {
                $project = Project::query()->lockForUpdate()->findOrFail($project->id);
                $certificate = $project->certificates()->where('status', 'issued')->latest('id')->first();
                if ($certificate) {
                    $alreadyIssued = true;

                    return $certificate->assessmentReview()->firstOrFail();
                }
            }

            $reviewRecord = AssessmentReview::create([
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'action' => $data['action'],
                'previous_status' => $previous,
                'new_status' => $newStatus,
                'approved_actual_total' => $approvedActualTotal,
                'certification_level' => $certificationLevel,
                'remarks' => $data['remarks'] ?? null,
            ]);

            $project->update($data['action'] === 'reopen'
                ? [
                    'assessment_status' => $newStatus,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'review_remarks' => null,
                ]
                : [
                    'assessment_status' => $newStatus,
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                    'review_remarks' => $data['remarks'] ?? null,
                ]);

            ActivityLogger::record($request->user(), 'assessment_' . $data['action'], $project, 'success', [
                'from_status' => $previous,
                'to_status' => $newStatus,
                'approved_actual_total' => $approvedActualTotal,
                'certification_level' => $certificationLevel,
                'remarks' => $data['remarks'] ?? null,
            ]);

            if ($data['action'] === 'certify') {
                $certificate = $this->certificates->issue(
                    $project->fresh(),
                    $reviewRecord,
                    $request->user(),
                    $scoreReview,
                );
            }

            return $reviewRecord;
        });

        if ($certificate) {
            $certificate = $this->certificates->ensurePdf($certificate);
        }

        if ($alreadyIssued) {
            return response()->json([
                'message' => 'The project certificate has already been issued.',
                'assessment' => $project->fresh(),
                'review' => $reviewRecord->load('reviewer:id,first_name,last_name,email'),
                'score_review' => $this->scoreService->breakdown($project->fresh()),
                'certificate' => $this->certificates->payload($certificate),
            ]);
        }

        $owner = $project->owner;
        if ($owner && (int) $owner->id !== (int) $request->user()->id) {
            $decision = match ($data['action']) {
                'verify' => ['Assessment verified', 'The Predicted assessment for '.$project->name.' was verified.'],
                'certify' => ['Assessment certified', 'The Actual assessment for '.$project->name.' was certified.'],
                'reject' => ['Assessment requires changes', 'Changes were requested for '.$project->name.'.'],
                'reopen' => ['Assessment review reopened', 'The review decision for '.$project->name.' was reopened.'],
            };
            $this->notifications->deliver($owner, $decision[0], $decision[1], '/projects/'.$project->id);
        }

        return response()->json([
            'message' => 'Assessment status updated.',
            'assessment' => $project->fresh(),
            'review' => $reviewRecord->load('reviewer:id,first_name,last_name,email'),
            'score_review' => $this->scoreService->breakdown($project->fresh()),
            'certificate' => $this->certificates->payload($certificate),
        ]);
    }

    public function rejectPredictedScoreUpdate(Request $request, Project $project): JsonResponse
    {
        $this->ensureAccess($request, $project);

        return response()->json([
            'message' => 'Predicted assessment values are read-only. Review the Actual assessment instead.',
        ], 422);
    }

    public function updateActualSelections(Request $request, Project $project): JsonResponse
    {
        $this->ensureAccess($request, $project);

        if (! in_array($project->assessment_status, ['verified', 'certified'], true)) {
            return response()->json([
                'message' => 'The Predicted assessment must be verified before the Actual assessment can be reviewed.',
            ], 422);
        }

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'distinct', 'exists:items,id'],
            'items.*.accepted_choice_keys' => ['present', 'array'],
            'items.*.accepted_choice_keys.*' => ['string', 'distinct', 'max:100'],
            'items.*.remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $before = $this->scoreService->breakdown($project);
        $items = collect($before['items'])->keyBy('item_id');

        foreach ($data['items'] as $index => $score) {
            $item = $items->get($score['item_id']);
            if (! $item) {
                throw ValidationException::withMessages([
                    "items.{$index}.item_id" => 'This item does not belong to the assessment.',
                ]);
            }

            $availableChoiceKeys = collect($item['actual_choices'])->pluck('choice_key');
            foreach ($score['accepted_choice_keys'] as $choiceIndex => $choiceKey) {
                if (! $availableChoiceKeys->contains($choiceKey)) {
                    throw ValidationException::withMessages([
                        "items.{$index}.accepted_choice_keys.{$choiceIndex}" => 'This Actual choice does not belong to the assessment item.',
                    ]);
                }
            }
        }

        $after = DB::transaction(function () use ($request, $project, $items, $before, $data) {
            $changes = [];

            foreach ($data['items'] as $score) {
                $itemId = (int) $score['item_id'];
                $item = $items->get($itemId);
                $previousValue = $item['actual_score'];
                $previousRemarks = $item['remarks'] ?? null;
                $remarks = trim((string) ($score['remarks'] ?? '')) ?: null;
                $acceptedChoiceKeys = collect($score['accepted_choice_keys'])->map(fn ($key) => (string) $key)->sort()->values();
                $previousAcceptedChoiceKeys = collect($item['actual_choices'])
                    ->where('accepted', true)
                    ->pluck('choice_key')
                    ->sort()
                    ->values();
                $newValue = min(
                    collect($item['actual_choices'])->whereIn('choice_key', $acceptedChoiceKeys)->sum('score'),
                    $item['max_score'],
                );

                $this->syncProjectActualAnswers($project, $itemId, $acceptedChoiceKeys->all());

                AssessmentItemReview::updateOrCreate(
                    ['project_id' => $project->id, 'item_id' => $itemId],
                    [
                        'original_score' => $item['submitted_actual_score'],
                        'reviewed_score' => $newValue,
                        'review_basis' => 'actual',
                        'accepted_actual_choice_keys' => $acceptedChoiceKeys->all(),
                        'submitted_actual_choice_keys' => $acceptedChoiceKeys->all(),
                        'remarks' => $remarks,
                        'reviewed_by' => $request->user()->id,
                    ],
                );

                $change = [
                    'item_id' => $itemId,
                    'previous_value' => $previousValue,
                    'new_value' => $newValue,
                    'previous_choice_keys' => $previousAcceptedChoiceKeys->all(),
                    'accepted_choice_keys' => $acceptedChoiceKeys->all(),
                    'remarks_changed' => $previousRemarks !== $remarks,
                ];
                $changes[] = $change;

                if ($previousAcceptedChoiceKeys->all() !== $acceptedChoiceKeys->all()) {
                    ActivityLogger::record($request->user(), 'assessment_actual_item_adjusted', $project, 'success', $change);
                }
            }

            $hasChangedValues = collect($changes)->contains(fn ($change) => $change['previous_choice_keys'] !== $change['accepted_choice_keys']);

            if ($hasChangedValues && $project->assessment_status === 'certified') {
                $previousStatus = $project->assessment_status;
                $this->certificates->revokeActive(
                    $project,
                    $request->user(),
                    'Actual assessment selections changed after certificate issuance.',
                );
                $project->update([
                    'assessment_status' => 'verified',
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'review_remarks' => null,
                ]);

                ActivityLogger::record($request->user(), 'assessment_approval_invalidated', $project, 'success', [
                    'from_status' => $previousStatus,
                    'to_status' => 'verified',
                    'reason' => 'Actual selections were changed after certification; Predicted verification remains valid.',
                ]);
            }

            $after = $this->scoreService->breakdown($project);

            ActivityLogger::record($request->user(), 'assessment_actual_review_saved', $project, 'success', [
                'from_total' => $before['actual_total'],
                'to_total' => $after['actual_total'],
                'reviewed_items' => $after['reviewed_items'],
                'changes' => $changes,
            ]);

            return $after;
        });

        return response()->json([
            'message' => 'Actual assessment selections and item remarks saved.',
            'assessment' => $project->fresh(),
            'score_review' => $after,
        ]);
    }

    private function syncProjectActualAnswers(Project $project, int $itemId, array $acceptedChoiceKeys): void
    {
        $choiceIds = collect($acceptedChoiceKeys)
            ->mapWithKeys(function (string $choiceKey) {
                [$type, $id] = array_pad(explode(':', $choiceKey, 2), 2, null);

                return $id !== null && ctype_digit($id) ? ["{$type}:{$id}" => (int) $id] : [];
            });

        $subitemIds = $choiceIds->filter(fn ($id, $key) => str_starts_with($key, 'subitem:'))->values();
        $optionIds = $choiceIds->filter(fn ($id, $key) => str_starts_with($key, 'option:'))->values();
        $selectionIds = $choiceIds->filter(fn ($id, $key) => str_starts_with($key, 'selection:'))->values();
        $customIds = $choiceIds->filter(fn ($id, $key) => str_starts_with($key, 'custom:'))->values();

        $validSubitemIds = DB::table('subitems')
            ->where('item_id', $itemId)
            ->whereIn('id', $subitemIds)
            ->pluck('id');
        $options = DB::table('options')
            ->join('option_groups', 'option_groups.id', '=', 'options.option_group_id')
            ->where('option_groups.item_id', $itemId)
            ->whereIn('options.id', $optionIds)
            ->get(['options.id', 'options.option_group_id']);
        $selections = DB::table('selections')
            ->join('selection_groups', 'selection_groups.id', '=', 'selections.selection_group_id')
            ->where('selection_groups.item_id', $itemId)
            ->whereIn('selections.id', $selectionIds)
            ->get(['selections.id', 'selections.selection_group_id']);
        $customAnswers = DB::table('actual_user_answers')
            ->where('project_id', $project->id)
            ->where('item_id', $itemId)
            ->whereIn('id', $customIds)
            ->whereNotNull('custom_answer')
            ->get(['id', 'custom_answer']);

        $optionGroupIds = DB::table('option_groups')->where('item_id', $itemId)->pluck('id');
        $selectionGroupIds = DB::table('selection_groups')->where('item_id', $itemId)->pluck('id');

        DB::table('actual_user_answers')
            ->where('project_id', $project->id)
            ->where(function ($query) use ($itemId, $optionGroupIds, $selectionGroupIds) {
                $query->where('item_id', $itemId)
                    ->orWhereIn('option_group_id', $optionGroupIds)
                    ->orWhereIn('selection_group_id', $selectionGroupIds);
            })
            ->delete();

        $base = ['project_id' => $project->id];
        if (Schema::hasColumn('actual_user_answers', 'user_id')) {
            $base['user_id'] = $project->user_id;
        }
        $answers = [];

        if (in_array("item:{$itemId}", $acceptedChoiceKeys, true)) {
            $answers[] = [...$base, 'item_id' => $itemId];
        }
        foreach ($validSubitemIds as $subitemId) {
            $answers[] = [...$base, 'item_id' => $itemId, 'subitem_id' => $subitemId];
        }
        foreach ($options as $option) {
            $answers[] = [...$base, 'option_group_id' => $option->option_group_id, 'option_id' => $option->id];
        }
        foreach ($selections as $selection) {
            $answers[] = [...$base, 'selection_group_id' => $selection->selection_group_id, 'selection_id' => $selection->id];
        }
        foreach ($customAnswers as $customAnswer) {
            $answers[] = [...$base, 'id' => $customAnswer->id, 'item_id' => $itemId, 'custom_answer' => $customAnswer->custom_answer];
        }

        if ($answers !== []) DB::table('actual_user_answers')->insert($answers);
    }

    private function ensureAccess(Request $request, Project $project): void
    {
        if (in_array($request->user()->system_role, ['admin', 'super_admin'], true)) return;

        $assigned = FacilitatorAssignment::where('project_id', $project->id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->exists();

        abort_unless($assigned, 403, 'You are not appointed to this project.');
    }

}
