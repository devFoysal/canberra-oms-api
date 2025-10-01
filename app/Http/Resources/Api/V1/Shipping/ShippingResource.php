<?php

namespace App\Http\Resources\Api\V1\Shipping;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\V1\Customer\{
    CustomerResource
};

class ShippingResource extends JsonResource
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
            'trackingNumber' => $this?->tracking_number,
            'estimatedDeliveryDate' => $this?->estimated_delivery_date,
            'deliveryDate' => $this?->delivery_date,
            'note' => $this?->note ?? "N/A",
            'status' => status_label($this->status),
        ];
    }
}
