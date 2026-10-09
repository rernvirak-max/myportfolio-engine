<?php

namespace App\Http\Requests\Admin;

use App\Models\Cohort;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCohortRequest extends FormRequest
{
    use ValidatesCohortPaymentOptions;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'schedule_text' => ['nullable', 'string', 'max:255'],
            'format' => ['required', Rule::in(Cohort::FORMATS)],
            'seats' => ['required', 'integer', 'min:1', 'max:500'],
            'min_students' => ['nullable', 'integer', 'min:1', 'max:500'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', Rule::in(['USD', 'KHR'])],
            'status' => ['required', Rule::in(Cohort::STATUSES)],
            ...$this->paymentOptionRules(sometimes: false),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->addPaymentOptionChecks($validator, $this->paymentValuesFromInput());
    }
}
