<?php

namespace App\Http\Requests\Api\V1\SalesRepresentative;

use Illuminate\Foundation\Http\FormRequest;

class CreateSalesRepresentativeRequest extends FormRequest
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
            'fullName' => 'sometimes|string|max:255',
            'email' => 'nullable|email',
            'mobileNumber' => 'nullable|string',
            'territory' => 'nullable|string',
        ];
    }
}
