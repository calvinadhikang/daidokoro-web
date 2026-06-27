<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateOperatingHoursRequest extends FormRequest
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
            'days' => ['required', 'array', 'size:7'],
            'days.*.day_of_week' => ['required', 'integer', 'between:0,6', 'distinct'],
            'days.*.is_closed' => ['boolean'],
            'days.*.session_1_starts_at' => ['nullable', 'date_format:H:i'],
            'days.*.session_1_ends_at' => ['nullable', 'date_format:H:i'],
            'days.*.session_2_starts_at' => ['nullable', 'date_format:H:i'],
            'days.*.session_2_ends_at' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('days', []) as $index => $day) {
                if ($day['is_closed'] ?? false) {
                    continue;
                }

                $sessionOneStart = $day['session_1_starts_at'] ?? null;
                $sessionOneEnd = $day['session_1_ends_at'] ?? null;

                if ($sessionOneStart === null || $sessionOneEnd === null) {
                    $validator->errors()->add(
                        "days.{$index}.session_1_starts_at",
                        'Session 1 start and end times are required when the day is open.',
                    );

                    continue;
                }

                if ($sessionOneStart >= $sessionOneEnd) {
                    $validator->errors()->add(
                        "days.{$index}.session_1_ends_at",
                        'Session 1 end time must be after the start time.',
                    );
                }

                $sessionTwoStart = $day['session_2_starts_at'] ?? null;
                $sessionTwoEnd = $day['session_2_ends_at'] ?? null;

                if ($sessionTwoStart === null && $sessionTwoEnd === null) {
                    continue;
                }

                if ($sessionTwoStart === null || $sessionTwoEnd === null) {
                    $validator->errors()->add(
                        "days.{$index}.session_2_starts_at",
                        'Both session 2 start and end times are required.',
                    );

                    continue;
                }

                if ($sessionTwoStart >= $sessionTwoEnd) {
                    $validator->errors()->add(
                        "days.{$index}.session_2_ends_at",
                        'Session 2 end time must be after the start time.',
                    );
                }

                if ($sessionTwoStart <= $sessionOneEnd) {
                    $validator->errors()->add(
                        "days.{$index}.session_2_starts_at",
                        'Session 2 must start after session 1 ends.',
                    );
                }
            }
        });
    }
}
