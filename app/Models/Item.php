<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Item extends Model
{
    protected $fillable = ['description', 'info', 'marks', 'subitems_exist', 'subcriterion_id', 'criterion_id'];

    public function criterion()
    {
        return $this->belongsTo(Criterion::class);
    }

    public function subcriterion()
    {
        $foreignKey = Schema::hasColumn('items', 'subcriterion_id') ? 'subcriterion_id' : 'subcriteria_id';

        return $this->belongsTo(Subcriterion::class, $foreignKey);
    }

    public function subitems()
    {
        return $this->hasMany(Subitem::class, 'item_id');
    }

    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class);
    }

    public function selectionGroups()
    {
        return $this->hasMany(SelectionGroup::class);
    }

    public function optionGroups()
    {
        return $this->hasMany(OptionGroup::class);
    }
}
