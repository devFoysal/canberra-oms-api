<?php

namespace App\Http\Requests\Api\V1\Shipping;

use Illuminate\Foundation\Http\FormRequest;

class CreateShippingRequest extends FormRequest
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
            'date'         => 'required|string|date_format:Y-m-d',
            'note'  => 'nullable|string',
        ];
    }
}
