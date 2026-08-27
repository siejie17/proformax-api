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
        $role = $this->resource->relationLoaded('role')
            ? $this->resource->getRelation('role')
            : $this->resource->role()->first();

        return [
            'membership' => [
                'id' => (string) $this->id,
                'projectId' => $this->project_id,
                'userId' => (string) $this->user_id,
                'role' => $role?->name ?? 'member',
                'roleId' => $role?->id,
                'permissions' => $role?->permissions ?? [],
            ],
            'user' => $this->user ? new UserResource($this->user) : ['id' => (string) $this->user_id],
            'isOwner' => $this->project?->user_id === $this->user_id,
            'role' => $role?->name ?? 'member',
        ];
    }
}
