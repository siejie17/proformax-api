<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Criterion extends Model
{
    protected $fillable = ['name', 'building_type_id'];
    
    public function buildingType()
    {
        return $this->belongsTo(BuildingType::class);
    }

    public function subcriteria()
    {
        return $this->hasMany(Subcriterion::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class); // items directly under criteria
    }
}
