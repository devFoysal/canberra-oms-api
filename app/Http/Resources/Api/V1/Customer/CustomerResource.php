<?php

namespace App\Http\Resources\Api\V1\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class CustomerResource extends JsonResource
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
            'id' => $this->id,
            'name' => $this->name,
            'mobile' => $this->mobile_number,
            'email' => $this->email,
            'shopName' => $this->shop_name,
            'address' => $this->address,
            'totalOrder' => $this->orders_count,
            'lastOrderDate' => $this->orders_max_created_at
                ? Carbon::parse($this->orders_max_created_at)->format('d M, Y')
                : null,            // 'avatar' => $this->avatar,
            // 'status' => $this->status,
            'salesRepresentative' => [
                "fullName" => $this->user->full_name,
                "email" => $this->user->email,
                "mobileNumber" => $this->user->mobile_number,
                "territory" => $this->user?->salesRepresentative?->territory
            ],
        ];
    }
}
