<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @method string|null route(string $parameter)
 * @method mixed input(string $key, mixed $default = null)
 * @method bool has(string $key)
 * @method bool filled(string $key)
 * @method void merge(array $input)
 */
class AuthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 6 characters long',
        ];
    }
}
