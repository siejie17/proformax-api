<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subitem extends Model
{
    protected $fillable = ['description', 'item_id'];
    
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class);
    }
}
