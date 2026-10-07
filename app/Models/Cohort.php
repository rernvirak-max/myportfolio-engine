<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cohort extends Model
{
    public const FORMATS = ['online', 'in_person', 'hybrid'];

    public const STATUSES = ['draft', 'open', 'full', 'closed'];

    protected $fillable = [
        'course_id',
        'title',
        'start_date',
        'end_date',
        'schedule_text',
        'format',
        'seats',
        'min_students',
        'price',
        'currency',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'seats' => 'integer',
            'min_students' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(CourseEnquiry::class);
    }

    /** Confirmed seats (status = enrolled). Used for seats_left. */
    public function enrolledCount(): int
    {
        return $this->enquiries()->where('status', 'enrolled')->count();
    }

    /**
     * Requests that count toward opening the class (new + contacted + enrolled).
     * Exposed as enrolled_count in the API for the frontend progress UI.
     */
    public function openingRequestCount(): int
    {
        return $this->enquiries()
            ->whereIn('status', ['new', 'contacted', 'enrolled'])
            ->count();
    }

    public function effectiveMinStudents(): int
    {
        if ($this->min_students !== null) {
            return (int) $this->min_students;
        }

        return (int) ($this->course?->min_students ?? 4);
    }

    public function seatsLeft(): int
    {
        return max(0, (int) $this->seats - $this->enrolledCount());
    }
}
