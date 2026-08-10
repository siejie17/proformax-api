<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    protected $fillable = ['project_id', 'user_id', 'original_name', 'filename', 'path', 'mime_type', 'kind', 'size', 'uploaded_at'];

    protected $casts = ['size' => 'integer', 'uploaded_at' => 'datetime'];

    public function getUrlAttribute(): ?string
    {
        return $this->path ? url("/api/media/{$this->filename}") : null;
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
