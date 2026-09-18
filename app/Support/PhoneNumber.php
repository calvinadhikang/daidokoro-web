<?php

namespace App\Support;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber as ParsedPhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class PhoneNumber
{
    public const DEFAULT_REGION = 'ID';

    public const COUNTRY_CODE = '62';

    public static function normalize(?string $phone, ?string $region = null): ?string
    {
        $parsed = self::parse($phone, $region);

        if ($parsed === null) {
            return null;
        }

        return ltrim(self::util()->format($parsed, PhoneNumberFormat::E164), '+');
    }

    public static function isValidStored(string $phone): bool
    {
        return self::parseStored($phone) !== null;
    }

    public static function region(string $normalizedPhone): string
    {
        $parsed = self::parseStored($normalizedPhone);
        $region = $parsed === null ? null : self::util()->getRegionCodeForNumber($parsed);

        return is_string($region) && $region !== '' ? $region : self::DEFAULT_REGION;
    }

    public static function callingCode(string $normalizedPhone): string
    {
        $parsed = self::parseStored($normalizedPhone);

        if ($parsed === null) {
            return self::COUNTRY_CODE;
        }

        return (string) $parsed->getCountryCode();
    }

    public static function toLocalInput(string $normalizedPhone): string
    {
        $parsed = self::parseStored($normalizedPhone);

        if ($parsed === null) {
            return $normalizedPhone;
        }

        $national = self::util()->format($parsed, PhoneNumberFormat::NATIONAL);

        return preg_replace('/\D/', '', $national) ?? $normalizedPhone;
    }

    public static function formatForDisplay(string $normalizedPhone): string
    {
        $parsed = self::parseStored($normalizedPhone);

        if ($parsed === null) {
            return $normalizedPhone;
        }

        $util = self::util();
        $region = $util->getRegionCodeForNumber($parsed) ?: self::DEFAULT_REGION;

        if ($region === self::DEFAULT_REGION) {
            return self::toLocalInput($normalizedPhone);
        }

        return $util->format($parsed, PhoneNumberFormat::INTERNATIONAL);
    }

    /**
     * @return array{
     *     phone_country: string,
     *     phone_calling_code: string,
     *     phone_display: string,
     *     phone_local: string
     * }
     */
    public static function metadata(string $normalizedPhone): array
    {
        return [
            'phone_country' => self::region($normalizedPhone),
            'phone_calling_code' => self::callingCode($normalizedPhone),
            'phone_display' => self::formatForDisplay($normalizedPhone),
            'phone_local' => self::toLocalInput($normalizedPhone),
        ];
    }

    /**
     * @return list<string>
     */
    public static function matchingValues(?string $phone, ?string $region = null): array
    {
        if ($phone === null || trim($phone) === '') {
            return [];
        }

        $normalized = self::normalize($phone, $region);
        $values = [$phone];

        if ($normalized !== null) {
            $values[] = $normalized;
            $values[] = '+'.$normalized;
            $values[] = self::formatForDisplay($normalized);
            $values[] = self::toLocalInput($normalized);
        }

        return array_values(array_unique(array_filter(
            $values,
            fn (string $value): bool => trim($value) !== '',
        )));
    }

    public static function normalizeRegion(?string $region): string
    {
        $region = strtoupper(trim((string) $region));

        if ($region === '' || ! in_array($region, self::util()->getSupportedRegions(), true)) {
            return self::DEFAULT_REGION;
        }

        return $region;
    }

    private static function parse(?string $phone, ?string $region = null): ?ParsedPhoneNumber
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $region = self::normalizeRegion($region);
        $raw = trim($phone);
        $digits = preg_replace('/\D/', '', $raw) ?? '';
        $util = self::util();

        $attempts = [];

        if (str_starts_with($raw, '+') || str_starts_with($raw, '00')) {
            $attempts[] = fn (): ParsedPhoneNumber => $util->parse($raw, $region);
        }

        if ($digits !== '' && ! str_starts_with($digits, '0') && ! self::looksLikeIndonesianNational($digits)) {
            $attempts[] = fn (): ParsedPhoneNumber => $util->parse('+'.$digits, 'ZZ');
        }

        $attempts[] = fn (): ParsedPhoneNumber => $util->parse($raw, $region);

        foreach ($attempts as $attempt) {
            try {
                $parsed = $attempt();

                if ($util->isValidNumber($parsed)) {
                    return $parsed;
                }
            } catch (NumberParseException) {
                continue;
            }
        }

        return null;
    }

    private static function parseStored(string $phone): ?ParsedPhoneNumber
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        try {
            $parsed = self::util()->parse('+'.$digits, 'ZZ');
        } catch (NumberParseException) {
            return self::parse($phone, self::DEFAULT_REGION);
        }

        return self::util()->isValidNumber($parsed) ? $parsed : self::parse($phone, self::DEFAULT_REGION);
    }

    private static function looksLikeIndonesianNational(string $digits): bool
    {
        return str_starts_with($digits, '8')
            && strlen($digits) >= 9
            && strlen($digits) <= 13;
    }

    private static function util(): PhoneNumberUtil
    {
        return PhoneNumberUtil::getInstance();
    }
}
