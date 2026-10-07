<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $course = Course::query()->updateOrCreate(
            ['slug' => 'full-stack-teaching-course'],
            [
                'title' => 'Full-stack teaching course',
                'summary' => '60-hour Laravel + Vue curriculum with a Class Manager capstone, delivered bilingual in English and Khmer.',
                'hours' => 60,
                'languages' => ['English', 'Khmer'],
                'level' => 'Beginner to intermediate',
                'is_published' => true,
            ],
        );

        if ($course->modules()->count() === 0) {
            $modules = [
                ['order' => 1, 'title' => 'Web foundations', 'hours' => 8, 'description' => 'HTML, CSS, JavaScript refreshers and tooling.'],
                ['order' => 2, 'title' => 'PHP & Laravel core', 'hours' => 16, 'description' => 'Routing, Eloquent, validation, auth, and APIs.'],
                ['order' => 3, 'title' => 'Vue frontend', 'hours' => 16, 'description' => 'Components, routing, forms, and talking to Laravel APIs.'],
                ['order' => 4, 'title' => 'Class Manager capstone', 'hours' => 20, 'description' => 'Build an end-to-end Class Manager that ties Laravel and Vue together.'],
            ];
            foreach ($modules as $module) {
                $course->modules()->create($module);
            }
        }

        if ($course->cohorts()->count() === 0) {
            $course->cohorts()->create([
                'title' => 'Next open intake',
                'start_date' => now()->addWeeks(3)->toDateString(),
                'end_date' => now()->addWeeks(3)->addMonths(2)->toDateString(),
                'schedule_text' => 'Evenings · 3× per week (to confirm)',
                'format' => 'hybrid',
                'seats' => 12,
                'price' => null,
                'currency' => 'USD',
                'status' => 'open',
            ]);
        }
    }
}
