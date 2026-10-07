<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Course */
class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'hours' => $this->hours,
            'languages' => $this->languages ?? [],
            'level' => $this->level,
            'min_students' => $this->min_students,
            'is_published' => $this->is_published,
            'modules' => CourseModuleResource::collection($this->whenLoaded('modules')),
            'cohorts' => CohortResource::collection($this->whenLoaded('cohorts')),
            'enquiries_count' => $this->whenCounted('enquiries'),
            'modules_count' => $this->whenCounted('modules'),
            'modules_hours_total' => $this->when(
                isset($this->modules_hours_total),
                fn () => (int) $this->modules_hours_total,
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
