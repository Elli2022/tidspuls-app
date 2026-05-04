<?php

namespace App\Http\Requests\Api;

use App\Models\User;
use App\Rules\ValidPersonnummer;
use App\Support\PersonnummerNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterApiRequest extends FormRequest
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
            'organization_name' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
            'personnummer' => ['required', 'string', 'max:32', new ValidPersonnummer(), 'unique:'.User::class],
        ];
    }
}
