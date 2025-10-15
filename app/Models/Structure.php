<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Structure extends Model
{
    protected $fillable = [
        'name',
        'code',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}