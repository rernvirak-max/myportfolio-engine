<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseEnquiry extends Model
{
    public const LANGUAGES = ['en', 'km'];

    public const FORMATS = ['online', 'in_person', 'either'];

    public const STATUSES = ['new', 'contacted', 'enrolled', 'declined'];

    public const LEVELS = [
        'Complete beginner',
        'Some HTML, CSS, or JavaScript',
        'Some PHP or Laravel',
        'Working developer',
    ];

    protected $fillable = [
        'name',
        'email',
        'contact',
        'language',
        'format',
        'level',
        'message',
        'ip',
        'user_agent',
        'status',
        'admin_note',
        'course_id',
        'cohort_id',
    ];

    protected $attributes = ['status' => 'new'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    public function scopeFilter(
        Builder $query,
        ?string $status = null,
        ?string $search = null,
        ?int $courseId = null,
        ?int $cohortId = null,
    ): Builder {
        if ($status && in_array($status, self::STATUSES, true)) {
            $query->where('status', $status);
        }

        if ($courseId) {
            $query->where('course_id', $courseId);
        }

        if ($cohortId) {
            $query->where('cohort_id', $cohortId);
        }

        if ($search !== null && $search !== '') {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('contact', 'like', $term);
            });
        }

        return $query;
    }
}
