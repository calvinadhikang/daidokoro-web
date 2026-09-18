<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhoneNumberTest extends TestCase
{
    #[DataProvider('normalizeProvider')]
    public function test_normalize_phone_numbers(
        string $input,
        ?string $region,
        ?string $expected,
    ): void {
        $this->assertSame($expected, PhoneNumber::normalize($input, $region));
    }

    /**
     * @return array<string, array{0: string, 1: string|null, 2: string|null}>
     */
    public static function normalizeProvider(): array
    {
        return [
            'id local without leading zero' => ['81234567890', 'ID', '6281234567890'],
            'id local with leading zero' => ['081234567890', 'ID', '6281234567890'],
            'id with country code' => ['6281234567890', 'ID', '6281234567890'],
            'id with plus prefix' => ['+6281234567890', 'ID', '6281234567890'],
            'id with spaces and dashes' => ['0812-3456-7890', 'ID', '6281234567890'],
            'sg national' => ['81234567', 'SG', '6581234567'],
            'sg e164' => ['+6581234567', 'SG', '6581234567'],
            'my national' => ['0123456789', 'MY', '60123456789'],
            'my e164' => ['+60123456789', null, '60123456789'],
        ];
    }

    public function test_format_for_display(): void
    {
        $this->assertSame(
            '081234567890',
            PhoneNumber::formatForDisplay('6281234567890'),
        );
        $this->assertSame(
            '+65 8123 4567',
            PhoneNumber::formatForDisplay('6581234567'),
        );
    }

    public function test_region_and_local_input(): void
    {
        $this->assertSame('ID', PhoneNumber::region('6281234567890'));
        $this->assertSame('081234567890', PhoneNumber::toLocalInput('6281234567890'));
        $this->assertSame('SG', PhoneNumber::region('6581234567'));
        $this->assertSame('81234567', PhoneNumber::toLocalInput('6581234567'));
    }

    public function test_matching_values_include_normalized_and_local_forms(): void
    {
        $values = PhoneNumber::matchingValues('081234567890');

        $this->assertContains('081234567890', $values);
        $this->assertContains('6281234567890', $values);
        $this->assertContains('+6281234567890', $values);
    }

    public function test_stored_singapore_number_is_not_parsed_as_indonesia(): void
    {
        $this->assertSame('6581234567', PhoneNumber::normalize('6581234567'));
        $this->assertSame('SG', PhoneNumber::region('6581234567'));
    }
}
