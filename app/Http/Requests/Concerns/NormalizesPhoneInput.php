<?php

namespace App\Http\Requests\Concerns;

use App\Support\PhoneNumber;
use Closure;

trait NormalizesPhoneInput
{
    protected function preparePhoneInput(string $phoneField, string $countryField): void
    {
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
}
