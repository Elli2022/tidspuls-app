<?php

namespace App\Http\Requests\Api;

use App\Rules\ValidPersonnummer;
use App\Support\PersonnummerNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class LoginApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->input('personnummer');
        if (! is_string($raw)) {
            return;
        }

        $canonical = PersonnummerNormalizer::canonical($raw);
        if ($canonical !== null) {
            $this->merge(['personnummer' => $canonical]);
        }
    }

    public function rules(): array
    {
        return [
            'personnummer' => ['required', 'string', new ValidPersonnummer()],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:128'],
        ];
    }
}
