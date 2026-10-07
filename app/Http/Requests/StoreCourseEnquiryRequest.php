<?php

namespace App\Http\Requests;

use App\Models\CourseEnquiry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $trim = fn ($v) => is_string($v) ? trim($v) : $v;
        $this->merge([
            'name' => $trim($this->input('name')),
            'email' => $trim($this->input('email')),
            'contact' => $trim($this->input('contact')) ?: null,
            'message' => $trim($this->input('message')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:120'],
            'contact' => ['nullable', 'string', 'max:80', 'regex:/^(\+?[\d\s().-]{6,20}|(?:@|(?:https?:\/\/)?t\.me\/)?[A-Za-z][A-Za-z0-9_]{4,31})$/'],
            'language' => ['required', Rule::in(CourseEnquiry::LANGUAGES)],
            'format' => ['required', Rule::in(CourseEnquiry::FORMATS)],
            'level' => ['required', Rule::in(CourseEnquiry::LEVELS)],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')],
            'cohort_id' => [
                'nullable',
                'integer',
                Rule::exists('cohorts', 'id')->where(function ($q) {
                    if ($this->filled('course_id')) {
                        $q->where('course_id', $this->integer('course_id'));
                    }
                }),
            ],
            // Honeypot: checked in the controller so bots get a fake success.
            'website' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please tell me your name.',
            'name.min' => 'Could you add your full name?',
            'email.required' => 'Please add your email so I can reply.',
            'email.email' => 'That email doesn’t look quite right — could you double-check it?',
            'contact.regex' => 'Please enter a phone number or a Telegram username like @username — or leave this empty.',
            'language.*' => 'Please choose the language you’d prefer.',
            'format.*' => 'Please pick the format you’re interested in.',
            'level.*' => 'Please choose the option closest to your experience.',
            'message.required' => 'Please add a short message about what you’d like to learn.',
            'message.min' => 'Could you add a little more detail? A sentence or two is perfect.',
            'message.max' => 'Please keep your message under 2000 characters.',
        ];
    }
}
