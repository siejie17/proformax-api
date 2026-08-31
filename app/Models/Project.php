<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    public const ACTUAL_REVIEW_STATUSES = [
        'submitted',
        'pending_verification',
        'verified',
        'requires_changes',
        'certified',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'building_type_id',
        'classification_id',
        'has_management',
        'category_id',
        'size',
        'year',
        'location_id',
        'structure_id',
        'cost_preview_way',
        'budget',
        'adjusted_cost',
        'rating',
        'target_certification',
        'created_at',
        'assessment_status',
        'reviewed_by',
        'reviewed_at',
        'review_remarks',
    ];

    public $timestamps = false;

    public function allowsActualReview(): bool
    {
        return in_array($this->assessment_status, self::ACTUAL_REVIEW_STATUSES, true);
    }

    public function buildingType()
    {
        return $this->belongsTo(BuildingType::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function classification()
    {
        return $this->belongsTo(BuildingClassification::class, 'classification_id');
    }

    public function structure()
    {
        return $this->belongsTo(Structure::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class);
    }

    public function costs()
    {
        return $this->hasMany(Cost::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ProjectMessage::class);
    }

    public function facilitatorAssignments(): HasMany
    {
        return $this->hasMany(FacilitatorAssignment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(AssessmentReview::class);
    }

    public function itemReviews(): HasMany
    {
        return $this->hasMany(AssessmentItemReview::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(ProjectCertificate::class);
    }

    public function latestCertificate(): HasOne
    {
        return $this->hasOne(ProjectCertificate::class)->latestOfMany();
    }
}
