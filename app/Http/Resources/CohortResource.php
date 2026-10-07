<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Cohort */
class CohortResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $enrolledOnly = $this->relationLoaded('enquiries')
            ? $this->enquiries->where('status', 'enrolled')->count()
            : $this->enrolledCount();

        // Progress toward min_students includes pipeline requests (new + contacted + enrolled).
        $openingCount = $this->relationLoaded('enquiries')
            ? $this->enquiries->whereIn('status', ['new', 'contacted', 'enrolled'])->count()
            : $this->openingRequestCount();

        $seatsLeft = max(0, (int) $this->seats - $enrolledOnly);

        if (! $this->relationLoaded('course')) {
            $this->resource->loadMissing('course');
        }

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
            // Frontend uses this for "X of N needed to open" / class confirmed.
            'enrolled_count' => $openingCount,
            'min_students' => $this->min_students ?? $this->course?->min_students,
            'price' => $this->price,
            'currency' => $this->currency,
            'status' => $this->status,
        ];
    }
}
