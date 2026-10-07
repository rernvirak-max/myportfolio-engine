<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\CourseModule */
class CourseModuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'order' => $this->order,
            'position' => $this->order,
            'title' => $this->title,
            'hours' => $this->hours,
            'description' => $this->description,
        ];
    }
}
