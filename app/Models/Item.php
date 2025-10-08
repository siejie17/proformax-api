<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = ['description', 'info', 'marks', 'subitems_exist', 'subcriterion_id', 'criterion_id'];
    
    public function criterion()
    {
        return $this->belongsTo(Criterion::class);
    }
    
    public function subcriterion()
    {
        return $this->belongsTo(Subcriterion::class);
    }

    public function subitems()
    {
        return $this->hasMany(Subitem::class, 'item_id');
    }

    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class);
    }
}
