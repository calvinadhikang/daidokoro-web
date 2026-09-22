<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesPhoneInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreApiTransactionRequest extends FormRequest
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
            'sales_channel_id' => ['nullable', 'integer', 'exists:sales_channels,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_id' => ['required', 'integer', 'exists:menus,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'items.*.weight_grams' => ['nullable', 'integer', 'min:1', 'max:50000'],
            'items.*.addon_option_ids' => ['nullable', 'array'],
            'items.*.addon_option_ids.*' => ['integer', 'exists:menu_addon_options,id'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.min' => 'Select at least one menu item before creating the transaction.',
        ];
    }
}
