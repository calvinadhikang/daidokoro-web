<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'addon_option_ids' => ['nullable', 'array'],
            'addon_option_ids.*' => ['integer', 'exists:menu_addon_options,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
