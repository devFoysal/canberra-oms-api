<?php

namespace App\Http\Resources\Api\V1\User;

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
        // return parent::toArray($request);
        return [
            "id" => $this->id,
            "fullName"=> $this->full_name,
            "mobileNumber"=> $this->mobile_number,
            "avatar"=> $this->avatar ? asset($this->avatar): "",
            "email"=> $this->email,
            "last_login_at"=> $this->last_login_at,
            'roles' => $this->getRoleNames()
        ];
    }
}
