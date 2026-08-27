<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AssessmentReview extends Model
{
    protected $fillable = [
        'project_id', 'user_id', 'action', 'previous_status', 'new_status',
        'approved_actual_total', 'certification_level', 'remarks',
    ];

    public function reviewer() { return $this->belongsTo(User::class, 'user_id'); }

    public function certificate(): HasOne
    {
        return $this->hasOne(ProjectCertificate::class);
    }
}
