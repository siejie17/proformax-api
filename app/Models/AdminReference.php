<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminReference extends Model
{
    protected $fillable = ['title', 'description', 'category', 'file_url', 'created_by', 'updated_by'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
