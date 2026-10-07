<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $courseId = $this->route('course')?->id ?? $this->route('course');

        return [
            'title' => ['sometimes', 'required', 'string', 'max:160'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:180',
                'alpha_dash',
                Rule::unique('courses', 'slug')->ignore($courseId),
            ],
            'summary' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'hours' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:1000'],
            'languages' => ['sometimes', 'nullable', 'array'],
            'languages.*' => ['string', 'max:40'],
            'level' => ['sometimes', 'nullable', 'string', 'max:100'],
            'min_students' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:500'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }
}
