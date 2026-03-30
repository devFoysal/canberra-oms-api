<?php

namespace App\Http\Resources\Api\V1\Location;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationCollectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
        return [
            'id' => $this->id,
            'lat' => $this->latitude,
            'lng' => $this->longitude,
            'time' => $this->timestamp
        ];
    }
}
