<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPersonnummer implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute format is invalid.');

            return;
        }

        $digits = preg_replace('/\D+/', '', $value);

        if ($digits === null || ! in_array(strlen($digits), [10, 12], true)) {
            $fail('The :attribute format is invalid.');

            return;
        }

        if (strlen($digits) === 12) {
            $digits = substr($digits, -10);
        }

        if (! $this->passesLuhn($digits)) {
            $fail('The :attribute format is invalid.');
        }
    }

    private function passesLuhn(string $digits): bool
    {
        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $num = (int) $digits[$i];
            $num *= ($i % 2 === 0) ? 2 : 1;
            $sum += $num > 9 ? $num - 9 : $num;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return $checkDigit === (int) $digits[9];
    }
}
