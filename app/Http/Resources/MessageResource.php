<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => (string) $this->id,
            'projectId'  => $this->project_id,
            'senderId'   => (string) $this->user_id,
            'message'    => $this->body,
            'attachment' => $this->attachment ? new AttachmentResource($this->attachment) : null,
            'replyToId'  => $this->reply_to_id ? (string) $this->reply_to_id : null,
            'isSystem'   => (bool) $this->is_system,
            'createdAt'  => $this->created_at?->toISOString(),
            // Flatten reactions to Record<emoji, userIds[]> like the frontend.
            'reactions'  => $this->when($this->relationLoaded('reactions'), fn () => $this->normalizeReactions()),
        ];
    }

    private function normalizeReactions(): array
    {
        $out = [];
        foreach ($this->reactions as $r) {
            $out[$r->emoji][] = (string) $r->user_id;
        }
        return $out;
    }
}
