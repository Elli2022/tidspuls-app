<?php

namespace App\Support;

class PersonnummerNormalizer
{
    /**
     * Canonical form stored and used for login lookup: YYMMDD-CCCC (10-digit identity, check digit included).
     */
    public static function canonical(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $trimmed);
        if ($digits === null || $digits === '') {
            return null;
        }

        if (! in_array(strlen($digits), [10, 12], true)) {
            return null;
        }

        if (strlen($digits) === 12) {
            $digits = substr($digits, -10);
        }

        return substr($digits, 0, 6).'-'.substr($digits, 6, 4);
    }
}
