<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\Project;
use App\Services\AssessmentEvidenceReadinessService;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectAttachmentController extends Controller
{
    private const MAX_EVIDENCE_PER_ITEM = 5;

    public function __construct(
        private readonly CertificateService $certificates,
        private readonly AssessmentEvidenceReadinessService $evidenceReadiness,
    ) {}

    public function store(Request $request, Project $project)
    {
        $isOwner = (int) $project->user_id === (int) $request->user()->id;
        $isMember = $project->members()->where('user_id', $request->user()->id)->exists();
        if (! $isOwner && ! $isMember) {
            abort(403, 'You are not a member of this project.');
        }

        $data = $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:png,jpg,jpeg,webp,pdf,xlsx'],
            'assessment_item_id' => ['nullable', 'integer', 'exists:items,id'],
        ]);

        if (isset($data['assessment_item_id'])) {
            if (! $project->allowsActualReview()) {
                return response()->json([
                    'message' => 'Evidence can be submitted only after the Predicted assessment is submitted.',
                ], 422);
            }

            $evidenceCount = Attachment::where('project_id', $project->id)
                ->where('assessment_item_id', $data['assessment_item_id'])
                ->count();

            if ($evidenceCount >= self::MAX_EVIDENCE_PER_ITEM) {
                return response()->json([
                    'message' => 'A maximum of five evidence files can be submitted for each assessment item.',
                ], 422);
            }
        }

        $file  = $request->file('file');
        $path  = $file->store("projects/{$project->id}", 'public');
        $ext   = strtolower($file->getClientOriginalExtension());
        $kind  = in_array($ext, ['png', 'jpg', 'jpeg', 'webp']) ? 'image'
               : ($ext === 'pdf' ? 'pdf' : 'spreadsheet');

        $attachment = Attachment::create([
            'project_id'    => $project->id,
            'user_id'       => $request->user()->id,
            'assessment_item_id' => $data['assessment_item_id'] ?? null,
            'original_name' => $file->getClientOriginalName(),
            'filename'      => basename($path),
            'path'          => $path,
            'mime_type'     => $file->getMimeType(),
            'kind'          => $kind,
            'size'          => $file->getSize(),
        ]);

        if ($attachment->assessment_item_id) {
            $this->evidenceReadiness->sync($project, $request->user());
        }

        return (new AttachmentResource($attachment))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, Project $project, Attachment $attachment)
    {
        if ((int) $attachment->project_id !== (int) $project->id || ! $attachment->assessment_item_id) {
            abort(404);
        }

        $user = $request->user();
        $isOwner = (int) $project->user_id === (int) $user->id;
        $isMember = $project->members()->where('user_id', $user->id)->exists();
        $canDeleteOwnEvidence = ($isOwner || $isMember) && (int) $attachment->user_id === (int) $user->id;
        $isAdministrator = $user->hasSystemRole('admin', 'super_admin');
        $isAssignedFacilitator = $user->hasSystemRole('facilitator_admin')
            && $project->facilitatorAssignments()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->exists();

        if (! $canDeleteOwnEvidence && ! $isAdministrator && ! $isAssignedFacilitator) {
            abort(403, 'You are not allowed to remove this evidence.');
        }

        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        $this->evidenceReadiness->sync($project, $user);

        if ($project->assessment_status === 'certified') {
            $this->certificates->revokeActive(
                $project,
                $user,
                'Assessment evidence was removed after certificate issuance.',
            );
            $project->update([
                'assessment_status' => 'submitted',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_remarks' => null,
            ]);
        }

        return response()->json(['message' => 'Evidence removed.']);
    }
}
