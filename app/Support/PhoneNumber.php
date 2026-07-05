<?php

namespace App\Support;

class PhoneNumber
{
    public const COUNTRY_CODE = '62';

    public const DISPLAY_PREFIX = '+62';

    public static function normalize(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (str_starts_with($digits, self::COUNTRY_CODE)) {
            return $digits;
        }

        return self::COUNTRY_CODE.$digits;
    }

    public static function toLocalInput(string $normalizedPhone): string
    {
        if (str_starts_with($normalizedPhone, self::COUNTRY_CODE)) {
            return substr($normalizedPhone, strlen(self::COUNTRY_CODE));
        }

        return $normalizedPhone;
    }

    public static function formatForDisplay(string $normalizedPhone): string
    {
        $local = self::toLocalInput($normalizedPhone);

        return self::DISPLAY_PREFIX.' '.$local;
    }
}
