<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAnswer extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function subitem()
    {
        return $this->belongsTo(Subitem::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
