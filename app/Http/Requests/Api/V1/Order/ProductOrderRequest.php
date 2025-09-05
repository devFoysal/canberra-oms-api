<?php

namespace App\Http\Requests\Api\V1\Order;

use Illuminate\Foundation\Http\FormRequest;

class ProductOrderRequest extends FormRequest
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
            'customer'      => 'nullable|exists:customers,id',
            'subtotal'         => 'required|numeric|min:0',
            'tax'              => 'nullable|numeric|min:0',
            'total'            => 'required|numeric|min:0',
            'items'            => 'required|array|min:1',
            // 'items.*.id'   => 'required|exists:products,id',
            // 'items.*.name' => 'required|string',
            // 'items.*.price'        => 'required|numeric|min:0',
            // 'items.*.quantity'     => 'required|integer|min:1',
            // 'items.*.total'        => 'required|numeric|min:0',
        ];
    }
}
