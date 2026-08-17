<?php

namespace App\Http\Resources;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // \Log::info('UserResource toArray called', ['resource' => $this]);
        return [
            'id'       => (string) $this->id,
            'fullName' => $this->first_name . ' ' . $this->last_name,
            'email'    => $this->email,
            'role'     => $this->role_id ? Role::find($this->role_id)?->display_name : $this->custom_role,
            // omit password fields; add avatar_url here if you have avatars
            'avatar'   => $this->profile_pic ?? null,
        ];
    }
}
