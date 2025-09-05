<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SignUpRequest extends FormRequest
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
            'first_name' => 'required|string|max:50',
            'last_name' => 'nullable|string|max:50',
            'mobile_number' => 'nullable|string|max:15|unique:users',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'avatar' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // max 2MB
        ];
    }

    /**
     * Custom messages
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'email.unique' => 'Email has already been taken.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
