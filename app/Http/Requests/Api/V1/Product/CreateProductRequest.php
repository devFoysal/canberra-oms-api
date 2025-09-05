<?php

namespace App\Http\Requests\Api\V1\Product;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'thumbnail' => 'nullable|file|image|mimes:jpg,jpeg,png,gif,webp|max:2048', // max 2MB
            'coverImage' => 'nullable|file|image|mimes:jpg,jpeg,png,gif,webp|max:4096', // max 4MB
            'shortDescription' => 'nullable|string',
            'description' => 'nullable|string',
            'purchasePrice' => 'nullable|numeric|min:0',
            'salePrice' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'categoryId' => 'nullable|integer|exists:categories,id',
            'status' => 'nullable|in:active,inactive',
        ];
    }
}
