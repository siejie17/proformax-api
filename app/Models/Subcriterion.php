<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Subcriterion extends Model
{
    protected $fillable = ['name', 'criteria_id'];
    
    public function criterion()
    {
        $foreignKey = Schema::hasColumn('subcriteria', 'criterion_id') ? 'criterion_id' : 'criteria_id';

        return $this->belongsTo(Criterion::class, $foreignKey);
    }

    public function items()
    {
        $foreignKey = Schema::hasColumn('items', 'subcriterion_id') ? 'subcriterion_id' : 'subcriteria_id';

        return $this->hasMany(Item::class, $foreignKey);
    }
}
