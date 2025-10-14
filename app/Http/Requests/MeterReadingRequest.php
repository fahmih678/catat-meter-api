<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @method bool has(string $key)
 * @method void merge(array $input)
 * @property mixed $current_reading
 * @property mixed $previous_reading
 * @property mixed $reading_at
 */
class MeterReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Implement your authorization logic here
    }

    public function rules(): array
    {
        return [
            'pam_id' => ['required', 'exists:pams,id'],
            'meter_id' => ['required', 'exists:meters,id'],
            'registered_month_id' => ['required', 'exists:registered_months,id'],
            'previous_reading' => ['required', 'numeric', 'min:0'],
            'current_reading' => ['required', 'numeric', 'min:0'],
            'volume_usage' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'photo_url' => ['nullable', 'string', 'max:500'],
            'reading_by' => ['nullable', 'exists:users,id'],
            'reading_at' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'meter_id.required' => 'Meter is required',
            'meter_id.exists' => 'Selected meter does not exist',
            'registered_month_id.required' => 'Registered month Id is required',
            'reading_at.required' => 'Reading date is required',
            'reading_at.date' => 'Reading date must be a valid date',
            'current_reading.required' => 'Current reading is required',
            'current_reading.numeric' => 'Current reading must be a number',
            'current_reading.min' => 'Current reading must be at least 0',
            'previous_reading.numeric' => 'Previous reading must be a number',
            'previous_reading.min' => 'Previous reading must be at least 0',
            'volume_usage.numeric' => 'Volume usage must be a number',
            'volume_usage.min' => 'Volume usage must be at least 0',
            'reading_by.required' => 'Reading ID is required',
            'photo_url.max' => 'Photo URL must not exceed 500 characters',
        ];
    }

    protected function prepareForValidation()
    {
        // Auto-calculate usage if not provided
        if ($this->has('current_reading') && $this->has('previous_reading') && !$this->has('volume_usage')) {
            $usage = max(0, $this->current_reading - $this->previous_reading);
            $this->merge(['volume_usage' => $usage]);
        }

        // Set period format if not provided
        if ($this->has('reading_at')) {
            $date = \Carbon\Carbon::parse($this->reading_at);
            $this->merge(['reading_at' => $date->format('Y-m-d')]);
        }
    }
}
