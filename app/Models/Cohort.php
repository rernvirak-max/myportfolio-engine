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

    public function enrolledCount(): int
    {
        return $this->enquiries()->where('status', 'enrolled')->count();
    }

    public function seatsLeft(): int
    {
        return max(0, (int) $this->seats - $this->enrolledCount());
    }
}
