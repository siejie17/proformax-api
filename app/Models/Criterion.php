<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Criterion extends Model
{
    protected $fillable = ['name', 'building_type_id'];
    
    public function buildingType()
    {
        return $this->belongsTo(BuildingType::class);
    }

    public function subcriteria()
    {
        $foreignKey = Schema::hasColumn('subcriteria', 'criterion_id') ? 'criterion_id' : 'criteria_id';

        return $this->hasMany(Subcriterion::class, $foreignKey);
    }

    public function items()
    {
        $subcriterionKey = Schema::hasColumn('items', 'subcriterion_id') ? 'subcriterion_id' : 'subcriteria_id';

        if (! Schema::hasColumn('items', 'criterion_id')) {
            return $this->hasMany(Item::class, $subcriterionKey)->whereRaw('1 = 0');
        }

        return $this->hasMany(Item::class, 'criterion_id')->whereNull($subcriterionKey);
    }
}
