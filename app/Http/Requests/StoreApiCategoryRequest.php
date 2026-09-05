<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApiCategoryRequest extends FormRequest
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
        $category = $this->route('category');
        $ignoreId = $category instanceof Category ? $category->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($ignoreId),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && Category::isHardcodedRecommended($value)) {
                        $fail('The Recommended label is reserved.');
                    }
                },
            ],
        ];
    }
}
