<?php

namespace App\Http\Requests\Admin;

use App\Models\CourseEnquiry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        ];
    }
}
