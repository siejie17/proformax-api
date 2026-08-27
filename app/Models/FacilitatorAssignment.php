<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilitatorAssignment extends Model
{
    protected $fillable = ['project_id', 'user_id', 'appointed_by', 'status', 'appointed_at', 'revoked_at'];

    protected $casts = ['appointed_at' => 'datetime', 'revoked_at' => 'datetime'];

    public function project() { return $this->belongsTo(Project::class); }
    public function facilitator() { return $this->belongsTo(User::class, 'user_id'); }
    public function appointer() { return $this->belongsTo(User::class, 'appointed_by'); }
}
