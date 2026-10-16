<?php

namespace App\Http\Requests;

use App\Models\OmakaseSession;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportOmakaseMenusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $source = $this->input('source_session_id');

        $this->merge([
            'source_session_id' => ($source === '' || $source === null) ? null : $source,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $current = $this->route('omakaseSession');
        $currentId = $current instanceof OmakaseSession ? $current->id : $current;

        return [
            'source_session_id' => [
                'required',
                'integer',
                Rule::exists('omakase_session', 'id'),
                Rule::notIn([$currentId]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'source_session_id.required' => 'Choose a session to import from.',
            'source_session_id.not_in' => 'Choose a different session to import from.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'source_session_id' => 'session',
        ];
    }
}
