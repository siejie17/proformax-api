<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    public function optionGroup()
    {
        return $this->belongsTo(OptionGroup::class);
    }

    public function classification()
    {
        return $this->belongsTo(BuildingClassification::class);
    }

    public function buildingClassification()
    {
        return $this->classification();
    }

    public function item()
    {
        return $this->optionGroup->item;
    }
}
