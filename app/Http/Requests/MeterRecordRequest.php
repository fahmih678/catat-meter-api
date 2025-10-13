<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @method bool has(string $key)
 * @method void merge(array $input)
 * @property mixed $current_reading
 * @property mixed $previous_reading
 * @property mixed $reading_date
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
            'meter_id' => ['required', 'exists:meters,id'],
            'period' => ['required', 'string', 'max:10'],
            'reading_date' => ['required', 'date'],
            'current_reading' => ['required', 'numeric', 'min:0'],
            'previous_reading' => ['nullable', 'numeric', 'min:0'],
            'usage' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'reader_name' => ['nullable', 'string', 'max:255'],
            'photo_url' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'meter_id.required' => 'Meter is required',
            'meter_id.exists' => 'Selected meter does not exist',
            'period.required' => 'Period is required',
            'period.max' => 'Period must not exceed 10 characters',
            'reading_date.required' => 'Reading date is required',
            'reading_date.date' => 'Reading date must be a valid date',
            'current_reading.required' => 'Current reading is required',
            'current_reading.numeric' => 'Current reading must be a number',
            'current_reading.min' => 'Current reading must be at least 0',
            'previous_reading.numeric' => 'Previous reading must be a number',
            'previous_reading.min' => 'Previous reading must be at least 0',
            'usage.numeric' => 'Usage must be a number',
            'usage.min' => 'Usage must be at least 0',
            'reader_name.max' => 'Reader name must not exceed 255 characters',
            'photo_url.max' => 'Photo URL must not exceed 500 characters',
        ];
    }

    protected function prepareForValidation()
    {
        // Auto-calculate usage if not provided
        if ($this->has('current_reading') && $this->has('previous_reading') && !$this->has('usage')) {
            $usage = max(0, $this->current_reading - $this->previous_reading);
            $this->merge(['usage' => $usage]);
        }

        // Set period format if not provided
        if (!$this->has('period') && $this->has('reading_date')) {
            $date = \Carbon\Carbon::parse($this->reading_date);
            $this->merge(['period' => $date->format('Y-m')]);
        }
    }
}
