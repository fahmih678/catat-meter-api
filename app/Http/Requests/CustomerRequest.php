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
class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Implement your authorization logic here
    }

    public function rules(): array
    {
        $customerId = $this->route('customer') ?? $this->route('id');
        $pamId = $this->input('pam_id');

        return [
            'pam_id' => ['required', 'exists:pams,id'],
            'area_id' => ['required', 'exists:areas,id'],
            'tariff_group_id' => ['required', 'exists:tariff_groups,id'],
            'customer_number' => [
                'nullable',
                'string',
                'max:50',
                'unique:customers,customer_number,' . $customerId . ',id,pam_id,' . $pamId
            ],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'pam_id.required' => 'PAM is required',
            'pam_id.exists' => 'Selected PAM does not exist',
            'area_id.required' => 'Area is required',
            'area_id.exists' => 'Selected area does not exist',
            'tariff_group_id.required' => 'Tariff group is required',
            'tariff_group_id.exists' => 'Selected tariff group does not exist',
            'customer_number.unique' => 'Customer number must be unique within the PAM',
            'customer_number.max' => 'Customer number must not exceed 50 characters',
            'name.required' => 'Customer name is required',
            'name.max' => 'Customer name must not exceed 255 characters',
            'address.required' => 'Customer address is required',
            'phone.max' => 'Phone number must not exceed 20 characters',
            'status.in' => 'Status must be either active or inactive',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate that area belongs to the same PAM
            if ($this->filled('pam_id') && $this->filled('area_id')) {
                $area = \App\Models\Area::find($this->input('area_id'));
                if ($area && $area->pam_id != $this->input('pam_id')) {
                    $validator->errors()->add('area_id', 'Selected area does not belong to the selected PAM');
                }
            }

            // Validate that tariff group belongs to the same PAM
            if ($this->filled('pam_id') && $this->filled('tariff_group_id')) {
                $tariffGroup = \App\Models\TariffGroup::find($this->input('tariff_group_id'));
                if ($tariffGroup && $tariffGroup->pam_id != $this->input('pam_id')) {
                    $validator->errors()->add('tariff_group_id', 'Selected tariff group does not belong to the selected PAM');
                }
            }
        });
    }
}
