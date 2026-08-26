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
            'editedAt'   => $this->updated_at && ! $this->updated_at->equalTo($this->created_at)
                ? $this->updated_at->toISOString()
                : null,
            // Flatten reactions to Record<emoji, userIds[]> like the frontend.
            'reactions'  => $this->when($this->relationLoaded('reactions'), fn () => $this->normalizeReactions()),
        ];
    }

    /**
     * Return the frontend reaction shape for mutation responses.
     *
     * @return array<string, list<string>>
     */
    public function reactionsShape(): array
    {
        $this->resource->loadMissing('reactions');

        return $this->normalizeReactions();
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
