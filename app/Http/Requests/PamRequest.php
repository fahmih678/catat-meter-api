<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;

class PamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Implement your authorization logic here
    }

    public function rules(): array
    {
        $pamId = request()->route('pam') ?? request()->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'code' => [
                'nullable',
                'string',
                'max:20',
                'unique:pams,code,' . $pamId
            ],
            'logo_url' => ['nullable', 'url'],
            'status' => ['nullable', 'in:active,inactive'],
            'coordinate' => ['nullable', 'array'],
            'coordinate.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'coordinate.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'created_by' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'PAM name is required',
            'name.max' => 'PAM name must not exceed 255 characters',
            'phone.max' => 'Phone number must not exceed 20 characters',
            'code.unique' => 'PAM code must be unique',
            'code.max' => 'PAM code must not exceed 20 characters',
            'logo_url.url' => 'Logo URL must be a valid URL',
            'status.in' => 'Status must be either active or inactive',
            'coordinate.latitude.between' => 'Latitude must be between -90 and 90',
            'coordinate.longitude.between' => 'Longitude must be between -180 and 180',
        ];
    }
}
