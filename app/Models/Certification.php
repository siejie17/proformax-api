<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    public function buildingType()
    {
        return $this->belongsTo(BuildingType::class);
    }
}
