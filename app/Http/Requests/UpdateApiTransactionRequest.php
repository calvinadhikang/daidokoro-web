<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesPhoneInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateApiTransactionRequest extends FormRequest
{
    use NormalizesPhoneInput;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->preparePhoneInput('customer_phone', 'customer_phone_country');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            ...$this->phoneValidationRules('customer_phone', 'customer_phone_country'),
            'service_type' => ['nullable', 'in:dine_in,takeaway'],
        ];
    }
}
