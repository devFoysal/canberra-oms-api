<?php

namespace App\Http\Resources\Api\V1\SalesRepresentative;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesRepresentativeResource extends JsonResource
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
            "fullName" => $this->full_name,
            "email" => $this->email,
            "mobileNumber" => $this->mobile_number,
            "empId" => $this->salesRepresentative?->employee_code,
            "territory" => $this->salesRepresentative?->territory,
            "status" => $this->status
        ];
    }
}
