<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => (string) $this->id,
            'filename'   => $this->original_name,
            'mimeType'   => $this->mime_type,
            'kind'       => $this->kind,
            'size'       => (int) $this->size,
            'uploadedBy' => (string) $this->user_id,
            'uploadedAt' => $this->uploaded_at?->toISOString(),
            'url'        => $this->url,
            'downloadUrl'=> $this->url ? $this->url . '/download' : null,
        ];
    }
}
