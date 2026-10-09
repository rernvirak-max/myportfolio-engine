<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $fullStack = Course::query()->updateOrCreate(
            ['slug' => 'full-stack-teaching-course'],
            [
                'title' => 'Full-Stack Web Development',
                'summary' => '60-hour Laravel + Vue curriculum with a Class Manager capstone, delivered bilingual in English and Khmer.',
                'hours' => 60,
                'languages' => ['English', 'Khmer'],
                'level' => 'Beginner to intermediate',
                'min_students' => 4,
                'is_published' => true,
            ],
        );

        foreach ([
            ['title' => 'Web foundations', 'hours' => 8, 'description' => 'HTML, CSS, JavaScript refreshers and tooling.'],
            ['title' => 'PHP & Laravel core', 'hours' => 16, 'description' => 'Routing, Eloquent, validation, auth, and APIs.'],
            ['title' => 'Vue frontend', 'hours' => 16, 'description' => 'Components, routing, forms, and talking to Laravel APIs.'],
            ['title' => 'Class Manager capstone', 'hours' => 20, 'description' => 'Build an end-to-end Class Manager that ties Laravel and Vue together.'],
        ] as $i => $module) {
            $fullStack->modules()->updateOrCreate(
                ['order' => $i + 1],
                $module,
            );
        }

        // Fake demo prices only — not real tuition.
        $fullStack->cohorts()->updateOrCreate(
            ['title' => 'Evening intake'],
            [
                'start_date' => now()->addWeeks(3)->toDateString(),
                'end_date' => now()->addWeeks(3)->addWeeks(10)->toDateString(),
                'schedule_text' => 'Mon / Wed / Fri · 18:00–20:00 (to confirm)',
                'format' => 'hybrid',
                'seats' => 12,
                'min_students' => null,
                'price' => 99.00,
                'currency' => 'USD',
                'installment_count' => 4,
                'installment_amount' => 25.00,
                'deposit_amount' => 20.00,
                'early_bird_price' => 79.00,
                'early_bird_until' => now()->addWeeks(2)->toDateString(),
                'early_bird_seats' => 5,
                'referral_discount' => 10.00,
                'status' => 'open',
            ],
        );

        $fullStack->cohorts()->updateOrCreate(
            ['title' => 'Weekend online'],
            [
                'start_date' => now()->addWeeks(6)->toDateString(),
                'end_date' => now()->addWeeks(6)->addWeeks(10)->toDateString(),
                'schedule_text' => 'Sat / Sun · 09:00–12:00 (to confirm)',
                'format' => 'online',
                'seats' => 10,
                'min_students' => 4,
                'price' => 89.00,
                'currency' => 'USD',
                'installment_count' => 3,
                'installment_amount' => 30.00,
                'deposit_amount' => 15.00,
                'early_bird_price' => null,
                'early_bird_until' => null,
                'early_bird_seats' => null,
                'referral_discount' => 5.00,
                'status' => 'open',
            ],
        );

        $devops = Course::query()->updateOrCreate(
            ['slug' => 'devops-course'],
            [
                'title' => 'DevOps Course',
                'summary' => 'Deploy and run real apps the way I do in production — Docker, CI/CD, Coolify/Nixpacks, Linux servers, and AWS. Taught bilingual in English and Khmer.',
                'hours' => 40,
                'languages' => ['English', 'Khmer'],
                'level' => 'Intermediate',
                'min_students' => 4,
                'is_published' => true,
            ],
        );

        foreach ([
            ['title' => 'Linux servers & fundamentals', 'hours' => 8, 'description' => 'SSH, users, networking basics, and keeping a box healthy.'],
            ['title' => 'Docker & containers', 'hours' => 10, 'description' => 'Images, Compose, volumes, and packaging Laravel/Vue apps.'],
            ['title' => 'CI/CD pipelines', 'hours' => 8, 'description' => 'Automated build, test, and deploy flows for real projects.'],
            ['title' => 'Coolify, Nixpacks & deploys', 'hours' => 8, 'description' => 'Ship to a VPS the way the portfolio and side projects go live.'],
            ['title' => 'AWS & production ops', 'hours' => 6, 'description' => 'Core AWS pieces, logs, backups, and what breaks in production.'],
        ] as $i => $module) {
            $devops->modules()->updateOrCreate(
                ['order' => $i + 1],
                $module,
            );
        }

        $devops->cohorts()->updateOrCreate(
            ['title' => 'Evening class'],
            [
                'start_date' => now()->addWeeks(4)->toDateString(),
                'end_date' => now()->addWeeks(4)->addWeeks(8)->toDateString(),
                'schedule_text' => 'Tue / Thu · 18:00–20:30 (to confirm)',
                'format' => 'online',
                'seats' => 12,
                'min_students' => null,
                'price' => 75.00,
                'currency' => 'USD',
                'installment_count' => 3,
                'installment_amount' => 25.00,
                'deposit_amount' => 15.00,
                'early_bird_price' => 60.00,
                'early_bird_until' => now()->addWeeks(3)->toDateString(),
                'early_bird_seats' => 4,
                'referral_discount' => 8.00,
                'status' => 'open',
            ],
        );

        // KHR demo class — whole-riel fake amounts only.
        $devops->cohorts()->updateOrCreate(
            ['title' => 'Phnom Penh in-person'],
            [
                'start_date' => now()->addWeeks(5)->toDateString(),
                'end_date' => now()->addWeeks(5)->addWeeks(8)->toDateString(),
                'schedule_text' => 'Sat · 13:00–17:00 (to confirm)',
                'format' => 'in_person',
                'seats' => 8,
                'min_students' => 4,
                'price' => 299000,
                'currency' => 'KHR',
                'installment_count' => 2,
                'installment_amount' => 150000,
                'deposit_amount' => 50000,
                'early_bird_price' => 249000,
                'early_bird_until' => now()->addWeeks(2)->toDateString(),
                'early_bird_seats' => 3,
                'referral_discount' => 20000,
                'status' => 'open',
            ],
        );
    }
}
