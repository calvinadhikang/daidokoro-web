<?php

namespace App\Http\Requests;

use App\Models\SalesChannel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalesChannelEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $source = $this->input('source_event_id');

        $this->merge([
            'source_event_id' => ($source === '' || $source === null) ? null : $source,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'source_event_id' => [
                'nullable',
                'integer',
                Rule::exists('sales_channels', 'id')->where('type', SalesChannel::TYPE_EVENT),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'source_event_id' => 'event',
        ];
    }
}
