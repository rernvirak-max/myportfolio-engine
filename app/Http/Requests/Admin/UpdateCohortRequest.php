<?php

namespace App\Http\Requests\Admin;

use App\Models\Cohort;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCohortRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:160'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'schedule_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'format' => ['sometimes', 'required', Rule::in(Cohort::FORMATS)],
            'seats' => ['sometimes', 'required', 'integer', 'min:1', 'max:500'],
            'min_students' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:500'],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'nullable', Rule::in(['USD', 'KHR'])],
            'status' => ['sometimes', 'required', Rule::in(Cohort::STATUSES)],
        ];
    }
}
