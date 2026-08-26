<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectCertificate extends Model
{
    protected $fillable = [
        'project_id',
        'assessment_review_id',
        'certificate_number',
        'verification_code',
        'certification_level',
        'approved_actual_score',
        'maximum_score',
        'status',
        'project_snapshot',
        'issued_by',
        'issued_at',
        'valid_until',
        'revoked_by',
        'revoked_at',
        'revocation_reason',
        'template_version',
        'pdf_path',
        'pdf_sha256',
    ];

    protected function casts(): array
    {
        return [
            'project_snapshot' => 'array',
            'approved_actual_score' => 'integer',
            'maximum_score' => 'integer',
            'issued_at' => 'datetime',
            'valid_until' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assessmentReview(): BelongsTo
    {
        return $this->belongsTo(AssessmentReview::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }
}
