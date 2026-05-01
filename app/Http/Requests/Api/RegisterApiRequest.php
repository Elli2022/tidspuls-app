<?php

namespace App\Http\Requests\Api;

use App\Models\User;
use App\Rules\ValidPersonnummer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
            'personnummer' => ['required', 'string', 'max:32', new ValidPersonnummer(), 'unique:'.User::class],
        ];
    }
}
