<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'building_type_id',
        'classification_id',
        'has_management',
        'category_id',
        'size',
        'year',
        'location_id',
        'structure_id',
        'cost_preview_way',
        'budget',
        'adjusted_cost',
        'rating',
        'target_certification',
        'created_at',
    ];

    public $timestamps = false;

    public function buildingType()
    {
        return $this->belongsTo(BuildingType::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function classification()
    {
        return $this->belongsTo(BuildingClassification::class, 'classification_id');
    }

    public function structure()
    {
        return $this->belongsTo(Structure::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class);
    }

    public function costs()
    {
        return $this->hasMany(Cost::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
