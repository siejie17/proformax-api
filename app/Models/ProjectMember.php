<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMember extends Model
{
    protected $fillable = ['project_id', 'user_id', 'added_by', 'role_id', 'last_read_message_id'];

    protected $casts = [
        'role_id' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The role assigned to this project member.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Convenience accessor: returns the role name (e.g. 'member', 'developer').
     */
    public function getRoleNameAttribute(): string
    {
        $roleName = $this->role?->name ?? 'member';

        return $roleName;
    }
}
