<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Structure extends Model
{
    protected $fillable = [
        'name',
        'code',
    ];

    // protected $casts = [
    //     'availability' => 'array',
    // ];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function buildingType()
    {
        return $this->belongsTo(BuildingType::class);
    }
}