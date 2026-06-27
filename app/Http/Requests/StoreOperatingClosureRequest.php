<?php

namespace App\Http\Requests;

use App\Models\OperatingClosure;
use App\Services\StoreHoursService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOperatingClosureRequest extends FormRequest
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
        $today = app(StoreHoursService::class)->today();

        return [
            'starts_at' => ['required', 'date', "after_or_equal:{$today}"],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'label' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $startsAt = $this->input('starts_at');
            $endsAt = $this->input('ends_at');

            if (! is_string($startsAt) || ! is_string($endsAt)) {
                return;
            }

            if (OperatingClosure::overlaps($startsAt, $endsAt)) {
                $validator->errors()->add(
                    'starts_at',
                    'This date range overlaps with an existing closed period.',
                );
            }
        });
    }
}
