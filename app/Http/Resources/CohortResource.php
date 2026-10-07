<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Cohort */
class CohortResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $enrolled = $this->relationLoaded('enquiries')
            ? $this->enquiries->where('status', 'enrolled')->count()
            : $this->enrolledCount();

        $seatsLeft = max(0, (int) $this->seats - $enrolled);

        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'title' => $this->title,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'schedule_text' => $this->schedule_text,
            'format' => $this->format,
            'seats' => $this->seats,
            'seats_left' => $seatsLeft,
            'enrolled_count' => $enrolled,
            'price' => $this->price,
            'currency' => $this->currency,
            'status' => $this->status,
        ];
    }
}
