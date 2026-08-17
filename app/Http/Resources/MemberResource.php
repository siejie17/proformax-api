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
        $role = $this->role ?: $this->custom_role ?: $this->user?->effective_role_label;         // from the users table

        return [
            'membership' => [
                'id'        => (string) $this->id,
                'projectId' => $this->project_id,
                'userId'    => (string) $this->user_id,
                'role'      => $role,
                'roleId'    => $this->role ? $this->role_id : null,
            ],
            'user'    => $this->user ? new UserResource($this->user) : ['id' => (string) $this->user_id],
            'isOwner' => $this->project?->user_id === $this->user_id,
            'role'    => $role,
        ];
    }
}
