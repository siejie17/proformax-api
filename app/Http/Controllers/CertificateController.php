<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectCertificate;
use App\Services\CertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    public function __construct(private readonly CertificateService $certificates) {}

    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorizeView($request, $project);

        return response()->json([
            'certificate' => $this->certificates->payload($project->certificates()->latest('id')->first()),
        ]);
    }

    public function download(Request $request, Project $project)
    {
        $this->authorizeView($request, $project);
        $certificate = $project->certificates()->where('status', 'issued')->latest('id')->first();
        abort_unless($certificate, 404, 'No active certificate has been issued for this project.');
        $certificate = $this->certificates->ensurePdf($certificate);

        return Storage::disk('local')->download(
            $certificate->pdf_path,
            $certificate->certificate_number.'.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public function verify(string $verificationCode): JsonResponse
    {
        $certificate = ProjectCertificate::where('verification_code', $verificationCode)->firstOrFail();
        $payload = $this->certificates->payload($certificate);

        return response()->json([
            'certificate' => collect($payload)->only([
                'certificate_number',
                'certification_level',
                'approved_actual_score',
                'maximum_score',
                'status',
                'project_name',
                'building_type',
                'location',
                'issued_at',
                'valid_until',
                'revoked_at',
                'revocation_reason',
            ])->all(),
        ]);
    }

    public function revoke(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);
        $certificate = $this->certificates->revokeActive($project, $request->user(), trim($data['reason']));
        abort_unless($certificate, 404, 'No active certificate has been issued for this project.');
        $project->update([
            'assessment_status' => 'verified',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_remarks' => null,
        ]);

        return response()->json([
            'message' => 'Certificate revoked.',
            'certificate' => $this->certificates->payload($certificate->fresh()),
        ]);
    }

    private function authorizeView(Request $request, Project $project): void
    {
        $user = $request->user();
        $authorized = $user->hasSystemRole('admin', 'super_admin')
            || (int) $project->user_id === (int) $user->id
            || $project->members()->where('user_id', $user->id)->exists()
            || ($user->hasSystemRole('facilitator_admin') && $project->facilitatorAssignments()
                ->where('user_id', $user->id)->where('status', 'active')->exists());

        abort_unless($authorized, 403, 'You are not allowed to view this certificate.');
    }
}
