<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subcriterion extends Model
{
    protected $fillable = ['name', 'criteria_id'];
    
    public function criterion()
    {
        return $this->belongsTo(Criterion::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
