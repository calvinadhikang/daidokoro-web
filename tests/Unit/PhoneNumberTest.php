<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhoneNumberTest extends TestCase
{
    #[DataProvider('normalizeProvider')]
    public function test_normalize_indonesian_phone_numbers(
        string $input,
        ?string $expected,
    ): void {
        $this->assertSame($expected, PhoneNumber::normalize($input));
    }

    /**
     * @return array<string, array{0: string, 1: string|null}>
     */
    public static function normalizeProvider(): array
    {
        return [
            'local without leading zero' => ['81234567890', '6281234567890'],
            'local with leading zero' => ['081234567890', '6281234567890'],
            'with country code' => ['6281234567890', '6281234567890'],
            'with plus prefix' => ['+6281234567890', '6281234567890'],
            'with spaces and dashes' => ['0812-3456-7890', '6281234567890'],
        ];
    }

    public function test_format_for_display(): void
    {
        $this->assertSame(
            '+62 81234567890',
            PhoneNumber::formatForDisplay('6281234567890'),
        );
    }
}
