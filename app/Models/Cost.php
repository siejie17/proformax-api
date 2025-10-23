<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cost extends Model
{
    protected $fillable = [
        'project_id',
        'code',
        'description',
        'item_cost',
        'parent_id',
        'level',
    ];
    
    public $timestamps = false;

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function parent()
    {
        return $this->belongsTo(Cost::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Cost::class, 'parent_id');
    }
}
