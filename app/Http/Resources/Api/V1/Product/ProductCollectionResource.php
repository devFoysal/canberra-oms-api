<?php

namespace App\Http\Resources\Api\V1\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\V1\Category\CategoryResource;

class ProductCollectionResource extends JsonResource
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
            'sku' => $this->sku,
            'name' => $this->name,
            'thumbnail' => $this->thumbnail ? asset($this->thumbnail) : "",
            'coverImage' => $this->cover_image ? asset($this->cover_image) : "",
            'shortDescription' => $this->short_description,
            'description' => $this->description,
            'purchasePrice' => $this->purchase_price,
            'salePrice' => $this->sale_price,
            'stock' => $this->stock,
            'slug' => $this->slug,
            'category' => new CategoryResource($this->category),
            'categoryName' => $this->category?->name,
            'status' => $this->status,
        ];
    }
}
