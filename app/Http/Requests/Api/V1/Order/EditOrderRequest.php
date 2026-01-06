<?php

namespace App\Http\Requests\Api\V1\Order;

use Illuminate\Foundation\Http\FormRequest;

class EditOrderRequest extends FormRequest
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
            'subtotal'         => 'required|numeric|min:0',
            'tax'              => 'nullable|numeric|min:0',
            'total'            => 'required|numeric|min:0',
            'items'            => 'required|array|min:1',
            'saveChange'       => 'nullable|boolean',
            'discount.type' => 'nullable|string|in:percentage,fixed',
            'discount.value' => 'nullable|numeric|min:0',
        ];
    }
}
