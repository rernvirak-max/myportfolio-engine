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
            'min_students' => 4,
        ]);
        $cohort = $course->cohorts()->create([
            'title' => 'A',
            'format' => 'online',
            'seats' => 5,
            'status' => 'open',
            'min_students' => null,
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

    public function test_public_course_exposes_min_students_and_opening_counts(): void
    {
        $course = Course::query()->create([
            'title' => 'DevOps Course',
            'slug' => 'devops-course',
            'is_published' => true,
            'min_students' => 4,
            'hours' => 40,
        ]);
        $cohort = $course->cohorts()->create([
            'title' => 'Evening class',
            'format' => 'online',
            'seats' => 12,
            'status' => 'open',
            'min_students' => null,
        ]);
        $course->cohorts()->create([
            'title' => 'Hidden draft',
            'format' => 'online',
            'seats' => 10,
            'status' => 'draft',
        ]);

        CourseEnquiry::query()->create([
            'name' => 'A',
            'email' => 'a@example.com',
            'language' => 'en',
            'format' => 'online',
            'level' => 'Complete beginner',
            'message' => 'Hello there, I want a seat.',
            'status' => 'new',
            'course_id' => $course->id,
            'cohort_id' => $cohort->id,
        ]);
        CourseEnquiry::query()->create([
            'name' => 'B',
            'email' => 'b@example.com',
            'language' => 'en',
            'format' => 'online',
            'level' => 'Complete beginner',
            'message' => 'Hello there, I want a seat too.',
            'status' => 'enrolled',
            'course_id' => $course->id,
            'cohort_id' => $cohort->id,
        ]);

        $this->getJson('/api/courses/devops-course')
            ->assertOk()
            ->assertJsonPath('data.min_students', 4)
            ->assertJsonPath('data.cohorts.0.min_students', 4)
            ->assertJsonPath('data.cohorts.0.enrolled_count', 2)
            ->assertJsonPath('data.cohorts.0.seats_left', 11)
            ->assertJsonCount(1, 'data.cohorts');
    }

    public function test_admin_can_assign_and_unassign_enquiry_cohort(): void
    {
        $this->actingAdmin();

        $course = Course::query()->create([
            'title' => 'Full-stack',
            'slug' => 'full-stack-assign',
            'is_published' => true,
            'min_students' => 4,
        ]);
        $cohort = $course->cohorts()->create([
            'title' => 'Evening class',
            'format' => 'online',
            'seats' => 12,
            'status' => 'open',
        ]);

        $enquiry = CourseEnquiry::query()->create([
            'name' => 'Sok Dara',
            'email' => 'dara@example.com',
            'language' => 'en',
            'format' => 'online',
            'level' => 'Complete beginner',
            'message' => 'Please assign me to a class.',
            'status' => 'enrolled',
            'course_id' => $course->id,
            'cohort_id' => null,
        ]);

        $this->patchJson("/api/admin/course-enquiries/{$enquiry->id}", [
            'cohort_id' => $cohort->id,
        ])->assertOk()
            ->assertJsonPath('data.cohort_id', $cohort->id)
            ->assertJsonPath('data.cohort.id', $cohort->id)
            ->assertJsonPath('data.cohort.title', 'Evening class');

        $this->assertDatabaseHas('course_enquiries', [
            'id' => $enquiry->id,
            'cohort_id' => $cohort->id,
        ]);

        $this->getJson("/api/admin/courses/{$course->id}")
            ->assertOk()
            ->assertJsonPath('data.cohorts.0.enrolled_count', 1)
            ->assertJsonPath('data.cohorts.0.seats_left', 11);

        $this->patchJson("/api/admin/course-enquiries/{$enquiry->id}", [
            'cohort_id' => null,
        ])->assertOk()
            ->assertJsonPath('data.cohort_id', null);

        $this->assertDatabaseHas('course_enquiries', [
            'id' => $enquiry->id,
            'cohort_id' => null,
        ]);

        $this->getJson("/api/admin/courses/{$course->id}")
            ->assertOk()
            ->assertJsonPath('data.cohorts.0.enrolled_count', 0)
            ->assertJsonPath('data.cohorts.0.seats_left', 12);
    }

    public function test_admin_rejects_cohort_from_another_course(): void
    {
        $this->actingAdmin();

        $courseA = Course::query()->create([
            'title' => 'Course A',
            'slug' => 'course-a',
            'is_published' => true,
        ]);
        $courseB = Course::query()->create([
            'title' => 'Course B',
            'slug' => 'course-b',
            'is_published' => true,
        ]);
        $cohortB = $courseB->cohorts()->create([
            'title' => 'B class',
            'format' => 'online',
            'seats' => 10,
            'status' => 'open',
        ]);

        $enquiry = CourseEnquiry::query()->create([
            'name' => 'Sok Dara',
            'email' => 'dara2@example.com',
            'language' => 'en',
            'format' => 'online',
            'level' => 'Complete beginner',
            'message' => 'Wrong course assignment attempt.',
            'status' => 'new',
            'course_id' => $courseA->id,
        ]);

        $this->patchJson("/api/admin/course-enquiries/{$enquiry->id}", [
            'cohort_id' => $cohortB->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['cohort_id']);
    }

    public function test_admin_can_partially_update_cohort_status(): void
    {
        $this->actingAdmin();

        $course = Course::query()->create([
            'title' => 'DevOps',
            'slug' => 'devops-partial',
            'is_published' => true,
        ]);
        $cohort = $course->cohorts()->create([
            'title' => 'Evening class',
            'format' => 'online',
            'seats' => 12,
            'status' => 'open',
            'schedule_text' => 'Tue / Thu',
            'currency' => 'USD',
        ]);

        $this->patchJson("/api/admin/courses/{$course->id}/cohorts/{$cohort->id}", [
            'status' => 'full',
        ])->assertOk()
            ->assertJsonPath('data.status', 'full')
            ->assertJsonPath('data.title', 'Evening class')
            ->assertJsonPath('data.seats', 12)
            ->assertJsonPath('data.schedule_text', 'Tue / Thu');

        $this->assertDatabaseHas('cohorts', [
            'id' => $cohort->id,
            'status' => 'full',
            'title' => 'Evening class',
        ]);
    }

    public function test_admin_courses_index_includes_modules_and_totals(): void
    {
        $this->actingAdmin();

        $course = Course::query()->create([
            'title' => 'Full-Stack',
            'slug' => 'full-stack-index',
            'is_published' => true,
            'hours' => 60,
        ]);
        $course->modules()->createMany([
            ['order' => 1, 'title' => 'Foundations', 'hours' => 8, 'description' => 'Basics'],
            ['order' => 2, 'title' => 'Laravel', 'hours' => 16, 'description' => 'Backend'],
        ]);

        $this->getJson('/api/admin/courses')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'full-stack-index')
            ->assertJsonPath('data.0.modules_count', 2)
            ->assertJsonPath('data.0.modules_hours_total', 24)
            ->assertJsonCount(2, 'data.0.modules')
            ->assertJsonPath('data.0.modules.0.title', 'Foundations')
            ->assertJsonPath('data.0.modules.0.hours', 8)
            ->assertJsonPath('data.0.modules.0.position', 1)
            ->assertJsonPath('data.0.modules.0.order', 1);
    }
}
