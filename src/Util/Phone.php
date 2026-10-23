<?php

namespace App\Util;

final class Phone
{
    /**
     * Normalise un numéro tunisien en 8 chiffres (sans +216 ni espaces).
     */
    public static function normalize(string $input): string
    {
        $digits = preg_replace('/\D+/', '', $input) ?? '';

        if (str_starts_with($digits, '216') && strlen($digits) === 11) {
            $digits = substr($digits, 3);
        }

        return $digits;
    }

    public static function isValid(string $input): bool
    {
        return 1 === preg_match('/^[2-9]\d{7}$/', self::normalize($input));
    }

    public static function format(string $phone): string
    {
        $digits = self::normalize($phone);

        if (strlen($digits) !== 8) {
            return $phone;
        }

        return sprintf('%s %s %s', substr($digits, 0, 2), substr($digits, 2, 3), substr($digits, 5, 3));
    }
}
