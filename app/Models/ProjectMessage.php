<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectMessage extends Model
{
    use SoftDeletes;

    protected $fillable = ['project_id', 'user_id', 'body', 'attachment_id', 'reply_to_id', 'is_system'];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function attachment(): BelongsTo { return $this->belongsTo(Attachment::class); }
    public function replyTo(): BelongsTo { return $this->belongsTo(self::class, 'reply_to_id'); }
    public function replies(): HasMany { return $this->hasMany(self::class, 'reply_to_id'); }
    public function reactions(): HasMany { return $this->hasMany(MessageReaction::class, 'message_id'); }
}
