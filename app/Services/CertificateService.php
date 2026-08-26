<?php

namespace App\Services;

use App\Models\AssessmentReview;
use App\Models\Project;
use App\Models\ProjectCertificate;
use App\Models\User;
use App\Support\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateService
{
    public function issue(Project $project, AssessmentReview $review, User $issuer, array $scoreReview): ProjectCertificate
    {
        $existing = $project->certificates()->where('status', 'issued')->latest('id')->first();
        if ($existing) {
            return $existing;
        }

        $project->loadMissing(['owner', 'buildingType', 'category', 'classification', 'structure', 'location']);
        $certificate = ProjectCertificate::create([
            'project_id' => $project->id,
            'assessment_review_id' => $review->id,
            'certificate_number' => $this->certificateNumber(),
            'verification_code' => (string) Str::uuid(),
            'certification_level' => $review->certification_level,
            'approved_actual_score' => $review->approved_actual_total,
            'maximum_score' => collect($scoreReview['items'])->sum('max_score'),
            'status' => 'issued',
            'project_snapshot' => $this->snapshot($project),
            'issued_by' => $issuer->id,
            'issued_at' => now(),
            'template_version' => 1,
        ]);

        ActivityLogger::record($issuer, 'certificate_issued', $certificate, 'success', [
            'project_id' => $project->id,
            'certificate_number' => $certificate->certificate_number,
            'certification_level' => $certificate->certification_level,
            'approved_actual_score' => $certificate->approved_actual_score,
        ]);

        return $certificate;
    }

    public function ensurePdf(ProjectCertificate $certificate): ProjectCertificate
    {
        if ($certificate->pdf_path && Storage::disk('local')->exists($certificate->pdf_path)) {
            return $certificate;
        }

        $certificate->loadMissing('issuer');
        $verificationUrl = rtrim(config('app.frontend_url'), '/')
            .'/certificates/verify/'.$certificate->verification_code;
        $qrCode = QrCode::create($verificationUrl)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::Medium)
            ->setSize(180)
            ->setMargin(4);
        $qrDataUri = (new SvgWriter)->write($qrCode)->getDataUri();
        $contents = Pdf::loadView('certificates.project', [
            'certificate' => $certificate,
            'snapshot' => $certificate->project_snapshot,
            'verificationUrl' => $verificationUrl,
            'qrDataUri' => $qrDataUri,
        ])->setPaper('a4', 'landscape')->output();
        $path = 'certificates/'.$certificate->certificate_number.'.pdf';

        if (! Storage::disk('local')->put($path, $contents)) {
            throw new \RuntimeException('Unable to store the generated certificate.');
        }

        $certificate->update([
            'pdf_path' => $path,
            'pdf_sha256' => hash('sha256', $contents),
        ]);

        return $certificate->fresh();
    }

    public function revokeActive(Project $project, User $actor, string $reason): ?ProjectCertificate
    {
        $certificate = $project->certificates()->where('status', 'issued')->latest('id')->first();
        if (! $certificate) {
            return null;
        }

        $certificate->update([
            'status' => 'revoked',
            'revoked_by' => $actor->id,
            'revoked_at' => now(),
            'revocation_reason' => $reason,
        ]);

        ActivityLogger::record($actor, 'certificate_revoked', $certificate, 'success', [
            'project_id' => $project->id,
            'certificate_number' => $certificate->certificate_number,
            'reason' => $reason,
        ]);

        return $certificate;
    }

    public function payload(?ProjectCertificate $certificate): ?array
    {
        if (! $certificate) {
            return null;
        }

        $snapshot = $certificate->project_snapshot;

        return [
            'id' => $certificate->id,
            'certificate_number' => $certificate->certificate_number,
            'verification_code' => $certificate->verification_code,
            'certification_level' => $certificate->certification_level,
            'approved_actual_score' => $certificate->approved_actual_score,
            'maximum_score' => $certificate->maximum_score,
            'status' => $certificate->status,
            'project_name' => $snapshot['project_name'] ?? null,
            'owner_name' => $snapshot['owner_name'] ?? null,
            'building_type' => $snapshot['building_type'] ?? null,
            'location' => $snapshot['location'] ?? null,
            'issued_at' => $certificate->issued_at?->toISOString(),
            'valid_until' => $certificate->valid_until?->toISOString(),
            'revoked_at' => $certificate->revoked_at?->toISOString(),
            'revocation_reason' => $certificate->revocation_reason,
            'pdf_sha256' => $certificate->pdf_sha256,
        ];
    }

    private function snapshot(Project $project): array
    {
        $ownerName = trim(($project->owner?->first_name ?? '').' '.($project->owner?->last_name ?? ''));
        $category = $project->getRelation('category');
        $location = $project->getRelation('location');

        return [
            'project_name' => $project->name,
            'owner_name' => $ownerName,
            'building_type' => $project->buildingType?->name,
            'building_type_code' => $project->buildingType?->code,
            'classification' => $project->classification?->name,
            'category' => $category?->category ?? $project->getRawOriginal('category'),
            'structure' => $project->structure?->name,
            'location' => $location?->location_name ?? $project->getRawOriginal('location'),
            'project_year' => $project->year,
            'target_certification' => $project->target_certification,
        ];
    }

    private function certificateNumber(): string
    {
        do {
            $number = 'PFMX-'.now()->format('Y').'-'.Str::upper(Str::random(8));
        } while (ProjectCertificate::where('certificate_number', $number)->exists());

        return $number;
    }
}
