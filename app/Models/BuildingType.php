<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuildingType extends Model
{
    public function criteria()
    {
        return $this->hasMany(Criterion::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }
}
