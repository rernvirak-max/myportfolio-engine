<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\CourseEnquiry */
class CourseEnquiryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'contact' => $this->contact,
            'language' => $this->language,
            'format' => $this->format,
            'level' => $this->level,
            'message' => $this->message,
            'status' => $this->status,
            'admin_note' => $this->admin_note,
            'course_id' => $this->course_id,
            'cohort_id' => $this->cohort_id,
            'cohort' => $this->when(
                $this->relationLoaded('cohort') && $this->cohort,
                fn () => [
                    'id' => $this->cohort->id,
                    'title' => $this->cohort->title,
                ],
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
