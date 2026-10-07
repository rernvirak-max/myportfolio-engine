<?php

namespace App\Http\Requests\Admin;

use App\Models\Cohort;
use App\Models\CourseEnquiry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCourseEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', Rule::in(CourseEnquiry::STATUSES)],
            'admin_note' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'cohort_id' => ['sometimes', 'nullable', 'integer', Rule::exists('cohorts', 'id')],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->exists('cohort_id') || $this->input('cohort_id') === null) {
                return;
            }

            /** @var CourseEnquiry|null $enquiry */
            $enquiry = $this->route('courseEnquiry');
            if (! $enquiry) {
                return;
            }

            $cohort = Cohort::query()->find($this->integer('cohort_id'));
            if (! $cohort) {
                return;
            }

            if ($enquiry->course_id && (int) $cohort->course_id !== (int) $enquiry->course_id) {
                $validator->errors()->add(
                    'cohort_id',
                    'The selected class does not belong to this enquiry\'s course.',
                );
            }
        });
    }
}
