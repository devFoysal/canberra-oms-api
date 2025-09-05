<?php

namespace App\Http\Resources\Api\V1\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\V1\SalesRepresentative\{
    SalesRepresentativeResource
};
use App\Http\Resources\Api\V1\Customer\{
    CustomerResource
};
use Carbon\Carbon;

class OrderItemCollectionResource extends JsonResource
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
            "thumbnail" => asset($this->product->thumbnail),
            "name" => $this->product_name,
            "price" => $this->price,
            "quantity" => $this->quantity,
            "originalQuantity" => $this->quantity,
        ];
    }
}
