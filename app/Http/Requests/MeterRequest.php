<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @method string|null route(string $parameter)
 * @method mixed input(string $key, mixed $default = null)
 * @method bool has(string $key)
 * @method void merge(array $input)
 * @property mixed $current_reading
 * @property mixed $previous_reading
 * @property mixed $reading_date
 */
class MeterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Implement your authorization logic here
    }

    public function rules(): array
    {
        $meterId = $this->route('meter') ?? $this->route('id');
        $customerId = $this->input('customer_id');

        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'meter_number' => [
                'nullable',
                'string',
                'max:50',
                'unique:meters,meter_number,' . $meterId
            ],
            'brand' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:analog,digital,smart'],
            'size' => ['required', 'string', 'max:20'],
            'capacity' => ['required', 'integer', 'min:1'],
            'installation_date' => ['required', 'date'],
            'last_calibration_date' => ['nullable', 'date'],
            'next_calibration_date' => ['nullable', 'date', 'after:last_calibration_date'],
            'initial_reading' => ['required', 'numeric', 'min:0'],
            'coordinate' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive,maintenance,damaged'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer is required',
            'customer_id.exists' => 'Selected customer does not exist',
            'meter_number.unique' => 'Meter number must be unique',
            'meter_number.max' => 'Meter number must not exceed 50 characters',
            'brand.required' => 'Meter brand is required',
            'brand.max' => 'Meter brand must not exceed 100 characters',
            'type.required' => 'Meter type is required',
            'type.in' => 'Meter type must be: analog, digital, or smart',
            'size.required' => 'Meter size is required',
            'size.max' => 'Meter size must not exceed 20 characters',
            'capacity.required' => 'Meter capacity is required',
            'capacity.integer' => 'Meter capacity must be a number',
            'capacity.min' => 'Meter capacity must be at least 1',
            'installation_date.required' => 'Installation date is required',
            'installation_date.date' => 'Installation date must be a valid date',
            'last_calibration_date.date' => 'Last calibration date must be a valid date',
            'next_calibration_date.date' => 'Next calibration date must be a valid date',
            'next_calibration_date.after' => 'Next calibration date must be after last calibration date',
            'initial_reading.required' => 'Initial reading is required',
            'initial_reading.numeric' => 'Initial reading must be a number',
            'initial_reading.min' => 'Initial reading must be at least 0',
            'status.in' => 'Status must be: active, inactive, maintenance, or damaged',
        ];
    }

    protected function prepareForValidation()
    {
        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge(['status' => 'inactive']);
        }
    }
}
