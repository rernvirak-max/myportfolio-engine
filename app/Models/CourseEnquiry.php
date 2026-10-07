<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseEnquiry extends Model
{
    public const LANGUAGES = ['en', 'km'];
    public const FORMATS = ['online', 'in_person', 'either'];
    public const LEVELS = [
        'Complete beginner',
        'Some HTML, CSS, or JavaScript',
        'Some PHP or Laravel',
        'Working developer',
    ];

    protected $fillable = [
        'name', 'email', 'contact', 'language', 'format', 'level', 'message', 'ip', 'user_agent', 'status',
    ];

    protected $attributes = ['status' => 'new'];
}
