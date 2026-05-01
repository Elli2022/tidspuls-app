<?php

namespace App\Http\Requests\Api;

use App\Rules\ValidPersonnummer;
use Illuminate\Foundation\Http\FormRequest;

class LoginApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
