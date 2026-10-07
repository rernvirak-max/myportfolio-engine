<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'summary',
        'hours',
        'languages',
        'level',
        'min_students',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'languages' => 'array',
            'is_published' => 'boolean',
            'hours' => 'integer',
            'min_students' => 'integer',
        ];
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('order');
    }

    public function cohorts(): HasMany
    {
        return $this->hasMany(Cohort::class)->latest('start_date');
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(CourseEnquiry::class);
    }
}
