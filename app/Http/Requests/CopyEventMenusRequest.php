<?php

namespace App\Http\Requests;

use App\Models\SalesChannel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CopyEventMenusRequest extends FormRequest
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
        $current = $this->route('salesChannel');
        $currentId = $current instanceof SalesChannel ? $current->id : $current;

        return [
            'source_event_id' => [
                'required',
                'integer',
                Rule::exists('sales_channels', 'id')->where('type', SalesChannel::TYPE_EVENT),
                Rule::notIn([$currentId]),
            ],
            'menu_ids' => ['required', 'array', 'min:1'],
            'menu_ids.*' => ['required', 'integer', 'distinct', 'exists:menus,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'source_event_id.required' => 'Choose an event to copy from.',
            'source_event_id.not_in' => 'Choose a different event to copy from.',
            'menu_ids.required' => 'Choose at least one menu.',
            'menu_ids.min' => 'Choose at least one menu.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'source_event_id' => 'event',
            'menu_ids' => 'menus',
            'menu_ids.*' => 'menu',
        ];
    }
}
