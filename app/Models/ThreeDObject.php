<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThreeDObject extends Model
{
    public function triggers()
    {
        return $this->hasMany(VisibilityTrigger::class);
    }
}
