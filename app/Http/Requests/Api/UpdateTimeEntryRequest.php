<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clocked_in_at' => ['required', 'date'],
            'clocked_out_at' => ['nullable', 'date', 'after:clocked_in_at'],
            'clock_in_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'clock_in_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'clock_out_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'clock_out_longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
