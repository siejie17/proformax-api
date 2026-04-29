<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActualUserAnswer extends Model
{
    protected $fillable = [
        'project_id',
        'item_id',
        'option_group_id',
        'option_id',
        'selection_group_id',
        'selection_id',
        'subitem_id',
        'custom_answer',
    ];

    public $timestamps = false;

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

    public function selection()
    {
        return $this->belongsTo(Selection::class);
    }
}
