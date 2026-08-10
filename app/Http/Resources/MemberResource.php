<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // \Log::info('MemberResource toArray called', ['resource' => $this->resource]);
        return [
            'membership' => [
                'id'       => (string) $this->id,
                'projectId' => $this->project_id,
                'userId'   => (string) $this->user_id,
                'role'     => $this->role ? $this->role : null,
                'roleId'   => $this->role_id,
            ],
            'user'       => $this->user ? new UserResource($this->user) : ['id' => (string) $this->user_id],
            'isOwner'    => $this->project?->user_id === $this->user_id,
            'role'       => $this->role ? $this->role : null,
        ];
    }
}
