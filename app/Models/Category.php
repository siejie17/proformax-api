<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    // Fillable attributes (mass assignment)
    protected $fillable = [
        'category',
        'building_type_id',
        'cost_per_meter_squared',
    ];

    // Relationships
    public function buildingType()
    {
        return $this->belongsTo(BuildingType::class);
    }
}
