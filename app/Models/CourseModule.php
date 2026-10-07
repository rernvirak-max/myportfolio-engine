<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseModule extends Model
{
    protected $fillable = [
        'course_id',
        'order',
        'title',
        'hours',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'hours' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
