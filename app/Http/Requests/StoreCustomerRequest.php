<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesPhoneInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    use NormalizesPhoneInput;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->preparePhoneInput('phone', 'phone_country');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $phoneRules = $this->phoneValidationRules('phone', 'phone_country');

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone_country' => $phoneRules['phone_country'],
            'phone' => [
                ...$phoneRules['phone'],
                Rule::unique('customers', 'phone'),
            ],
        ];
    }
}
