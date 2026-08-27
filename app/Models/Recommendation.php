<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recommendation extends Model
{
    protected $fillable = ['certification_level', 'title', 'content', 'is_active', 'created_by', 'updated_by'];

    protected $casts = ['is_active' => 'boolean'];
}
