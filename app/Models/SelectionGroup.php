<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SelectionGroup extends Model
{
    public function selections()
    {
        return $this->hasMany(Selection::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
