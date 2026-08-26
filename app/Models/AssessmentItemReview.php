<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentItemReview extends Model
{
    protected $fillable = [
        'project_id',
        'item_id',
        'original_score',
        'reviewed_score',
        'review_basis',
        'accepted_actual_answer_ids',
        'accepted_actual_choice_keys',
        'submitted_actual_choice_keys',
        'remarks',
        'reviewed_by',
    ];

    protected $casts = [
        'original_score' => 'integer',
        'reviewed_score' => 'integer',
        'accepted_actual_answer_ids' => 'array',
        'accepted_actual_choice_keys' => 'array',
        'submitted_actual_choice_keys' => 'array',
    ];

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
