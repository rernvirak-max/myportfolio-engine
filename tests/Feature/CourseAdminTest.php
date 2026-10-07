<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CourseAdminTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdmin(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_admin_can_crud_course_with_modules_and_cohorts(): void
    {
        $this->actingAdmin();

        $create = $this->postJson('/api/admin/courses', [
            'title' => 'Full-stack teaching course',
            'summary' => 'Laravel + Vue',
            'hours' => 60,
            'languages' => ['English', 'Khmer'],
            'is_published' => true,
        ])->assertCreated()
            ->assertJsonPath('data.slug', 'full-stack-teaching-course');

        $id = $create->json('data.id');

        $this->postJson("/api/admin/courses/{$id}/modules", [
            'title' => 'Laravel core',
            'hours' => 16,
            'description' => 'Routing and Eloquent',
        ])->assertCreated();

        $this->postJson("/api/admin/courses/{$id}/cohorts", [
            'title' => 'Spring intake',
            'format' => 'online',
            'seats' => 10,
            'status' => 'open',
            'start_date' => now()->addMonth()->toDateString(),
        ])->assertCreated()
            ->assertJsonPath('data.seats_left', 10);

        $this->getJson("/api/admin/courses/{$id}")
            ->assertOk()
            ->assertJsonCount(1, 'data.modules')
            ->assertJsonCount(1, 'data.cohorts');

        $this->getJson('/api/courses/full-stack-teaching-course')
            ->assertOk()
            ->assertJsonPath('data.title', 'Full-stack teaching course');
    }

    public function test_public_courses_hide_unpublished(): void
    {
        Course::query()->create([
            'title' => 'Draft',
            'slug' => 'draft',
            'is_published' => false,
        ]);
        Course::query()->create([
            'title' => 'Live',
            'slug' => 'live',
            'is_published' => true,
        ]);

        $this->getJson('/api/courses')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'live');
    }

    public function test_enquiry_can_link_course_and_cohort(): void
    {
        $course = Course::query()->create([
            'title' => 'Full-stack',
            'slug' => 'full-stack',
            'is_published' => true,
        ]);
        $cohort = $course->cohorts()->create([
            'title' => 'A',
            'format' => 'online',
            'seats' => 5,
            'status' => 'open',
        ]);

        $this->postJson('/api/course-enquiries', [
            'name' => 'Sok Dara',
            'email' => 'dara@example.com',
            'language' => 'en',
            'format' => 'online',
            'level' => 'Complete beginner',
            'message' => 'I would like a seat in the next class please.',
            'website' => '',
            'course_id' => $course->id,
            'cohort_id' => $cohort->id,
        ])->assertCreated();

        $this->assertDatabaseHas('course_enquiries', [
            'email' => 'dara@example.com',
            'course_id' => $course->id,
            'cohort_id' => $cohort->id,
        ]);

        $this->actingAdmin();
        $this->getJson("/api/admin/course-enquiries?course_id={$course->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
