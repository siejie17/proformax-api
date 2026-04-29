<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuildingClassification extends Model
{
    public function buildingType()
    {
        return $this->belongsTo(BuildingType::class);
    }

    public function selectionGroups()
    {
        return $this->hasMany(SelectionGroup::class, 'classification_id');
    }
}
