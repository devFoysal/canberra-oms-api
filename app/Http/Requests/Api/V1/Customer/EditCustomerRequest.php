<?php

namespace App\Http\Requests\Api\V1\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditCustomerRequest extends FormRequest
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
        $customerId = $this->route('id'); // get customer ID from route
        return [
            'name'     => 'required|string|max:255',
            'mobile'   => [
                'required',
                'string',
                'max:20',
                Rule::unique('customers', 'mobile_number')->ignore($customerId), // ignore current record
                'regex:/^01[0-9]{9}$/',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->ignore($customerId),
            ],
            'shopName' => 'nullable|string|max:255',
            'address'  => 'required|string|max:500',
            'assignSalesPerson'  => 'nullable|integer',
        ];
    }
}
