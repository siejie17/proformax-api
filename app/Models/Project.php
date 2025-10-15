<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    public function buildingType()
    {
        return $this->belongsTo(BuildingType::class);
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
}
