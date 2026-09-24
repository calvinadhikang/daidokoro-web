<?php

namespace App\Http\Requests\Concerns;

use App\Support\PhoneNumber;
use App\Support\WalkInCustomer;
use Closure;

trait NormalizesPhoneInput
{
    protected function preparePhoneInput(string $phoneField, string $countryField): void
    {
        $rawPhone = is_string($this->input($phoneField)) ? trim($this->input($phoneField)) : '';

        if ($rawPhone !== '' && WalkInCustomer::isWalkInStored($rawPhone)) {
            $this->merge([
                $countryField => PhoneNumber::DEFAULT_REGION,
                $phoneField => $rawPhone,
            ]);

            return;
        }

        $country = PhoneNumber::normalizeRegion(
            is_string($this->input($countryField)) ? $this->input($countryField) : null,
        );

        $normalized = PhoneNumber::normalize(
            is_string($this->input($phoneField)) ? $this->input($phoneField) : null,
            $country,
        );

        $this->merge([
            $countryField => $country,
            $phoneField => $normalized ?? $this->input($phoneField),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function phoneValidationRules(string $phoneField, string $countryField): array
    {
        return [
            $countryField => ['nullable', 'string', 'size:2'],
            $phoneField => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || ! PhoneNumber::isValidStored($value)) {
                        $fail('Enter a valid mobile number.');
                    }
                },
            ],
        ];
    }

    /**
     * Cashier API (Expo): require a phone value but allow queue labels and walk-in placeholders.
     *
     * @return array<string, mixed>
     */
    protected function cashierPhoneValidationRules(string $phoneField, string $countryField): array
    {
        return [
            $countryField => ['nullable', 'string', 'size:2'],
            $phoneField => ['required', 'string', 'max:64'],
        ];
    }
}
