<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AssignChannelMenusRequest extends FormRequest
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
            'menus' => ['required', 'array', 'min:1'],
            'menus.*.menu_id' => ['required', 'integer', 'exists:menus,id'],
            'menus.*.price_override' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
