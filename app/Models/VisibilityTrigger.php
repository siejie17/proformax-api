<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisibilityTrigger extends Model
{
    protected $fillable = [
        'three_d_object_id',
        'trigger_type',
        'trigger_id',
    ];

    public function object()
    {
        return $this->belongsTo(ThreeDObject::class);
    }
}
