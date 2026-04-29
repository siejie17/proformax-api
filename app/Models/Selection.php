<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Selection extends Model
{
    public function selectionGroup()
    {
        return $this->belongsTo(SelectionGroup::class);
    }

    public function classification()
    {
        return $this->belongsTo(BuildingClassification::class);
    }
}
