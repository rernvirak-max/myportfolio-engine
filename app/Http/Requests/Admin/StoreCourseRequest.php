<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('title')) {
            $this->merge(['slug' => Str::slug($this->string('title')->toString())]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:180', 'alpha_dash', Rule::unique('courses', 'slug')],
            'summary' => ['nullable', 'string', 'max:5000'],
            'hours' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['string', 'max:40'],
            'level' => ['nullable', 'string', 'max:100'],
            'min_students' => ['nullable', 'integer', 'min:1', 'max:500'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }
}
