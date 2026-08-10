<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'level',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
        'level'       => 'integer',
    ];

    /**
     * Users that have this role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    /**
     * Project members that have this role.
     */
    public function projectMembers(): HasMany
    {
        return $this->hasMany(ProjectMember::class, 'role_id');
    }

    /**
     * Check whether this role has a given permission flag.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true);
    }

    /**
     * Check whether this role's level is at least the given level.
     */
    public function isAtLeast(int $level): bool
    {
        return $this->level >= $level;
    }
}
