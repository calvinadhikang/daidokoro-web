<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMenuRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'is_available' => ['boolean'],
            'is_recommended' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.config('media.menu_image_max_kb', 5120)],
            'remove_image' => ['sometimes', 'boolean'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'addon_groups' => ['nullable', 'array'],
            'addon_groups.*.name' => ['required', 'string', 'max:255'],
            'addon_groups.*.selection_type' => ['required', 'in:single,multiple'],
            'addon_groups.*.is_required' => ['boolean'],
            'addon_groups.*.options' => ['required', 'array', 'min:1'],
            'addon_groups.*.options.*.name' => ['required', 'string', 'max:255'],
            'addon_groups.*.options.*.price' => ['required', 'integer', 'min:0'],
            'addon_groups.*.options.*.is_available' => ['boolean'],
        ];
    }
}
