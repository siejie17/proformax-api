<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    public function parentLocation()
    {
        return $this->belongsTo(Location::class, 'parent_location_id');
    }

    // Optional: define children (regions of this state)
    public function childLocations()
    {
        return $this->hasMany(Location::class, 'parent_location_id');
    }
}