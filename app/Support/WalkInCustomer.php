<?php

namespace App\Support;

class WalkInCustomer
{
    public const PHONE_PREFIX = 'walkin:';

    public static function isWalkInStored(string $phone): bool
    {
        return str_starts_with(trim($phone), self::PHONE_PREFIX);
    }

    public static function buildStoredPhone(string $queueKey): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9._-]/', '', trim($queueKey)) ?? '';

        return self::PHONE_PREFIX.($safe !== '' ? $safe : 'pending');
    }
}
