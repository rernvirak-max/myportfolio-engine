<?php

namespace Tests\Feature;

use App\Models\Cohort;
use App\Models\Course;
use App\Models\CourseEnquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CohortPaymentOptionsTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdmin(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    private function makeCourse(): Course
    {
        return Course::query()->create([
            'title' => 'Payment demo course',
            'slug' => 'payment-demo-course',
            'is_published' => true,
            'min_students' => 4,
        ]);
    }

    /** @return array<string, mixed> */
    private function fullPaymentPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Demo class',
            'format' => 'online',
            'seats' => 12,
            'status' => 'open',
            'price' => 100.00,
            'currency' => 'USD',
            'installment_count' => 4,
            'installment_amount' => 25.00,
            'deposit_amount' => 20.00,
            'early_bird_price' => 80.00,
            'early_bird_until' => now()->addWeeks(2)->toDateString(),
            'early_bird_seats' => 5,
            'referral_discount' => 10.00,
        ], $overrides);
    }

    public function test_admin_can_create_cohort_with_every_payment_option(): void
    {
        $this->actingAdmin();
        $course = $this->makeCourse();
        $until = now()->addWeeks(2)->toDateString();

        $this->postJson("/api/admin/courses/{$course->id}/cohorts", $this->fullPaymentPayload([
            'early_bird_until' => $until,
        ]))->assertCreated()
            ->assertJsonPath('data.price', '100.00')
            ->assertJsonPath('data.currency', 'USD')
            ->assertJsonPath('data.installment_count', 4)
            ->assertJsonPath('data.installment_amount', '25.00')
            ->assertJsonPath('data.deposit_amount', '20.00')
            ->assertJsonPath('data.early_bird_price', '80.00')
            ->assertJsonPath('data.early_bird_until', $until)
            ->assertJsonPath('data.early_bird_seats', 5)
            ->assertJsonPath('data.referral_discount', '10.00')
            ->assertJsonPath('data.early_bird_active', true)
            ->assertJsonPath('data.effective_price', '80.00')
            ->assertJsonPath('data.early_bird_seats_left', 5);
    }

    public function test_partial_status_patch_keeps_payment_options(): void
    {
        $this->actingAdmin();
        $course = $this->makeCourse();
        $until = now()->addWeeks(2)->toDateString();

        $create = $this->postJson("/api/admin/courses/{$course->id}/cohorts", $this->fullPaymentPayload([
            'early_bird_until' => $until,
        ]))->assertCreated();

        $cohortId = $create->json('data.id');

        $this->patchJson("/api/admin/courses/{$course->id}/cohorts/{$cohortId}", [
            'status' => 'full',
        ])->assertOk()
            ->assertJsonPath('data.status', 'full')
            ->assertJsonPath('data.installment_count', 4)
            ->assertJsonPath('data.installment_amount', '25.00')
            ->assertJsonPath('data.deposit_amount', '20.00')
            ->assertJsonPath('data.early_bird_price', '80.00')
            ->assertJsonPath('data.early_bird_until', $until)
            ->assertJsonPath('data.early_bird_seats', 5)
            ->assertJsonPath('data.referral_discount', '10.00');

        $this->assertDatabaseHas('cohorts', [
            'id' => $cohortId,
            'status' => 'full',
            'installment_count' => 4,
            'early_bird_seats' => 5,
        ]);
    }

    public function test_admin_can_clear_payment_options(): void
    {
        $this->actingAdmin();
        $course = $this->makeCourse();

        $cohortId = $this->postJson("/api/admin/courses/{$course->id}/cohorts", $this->fullPaymentPayload())
            ->assertCreated()
            ->json('data.id');

        $this->patchJson("/api/admin/courses/{$course->id}/cohorts/{$cohortId}", [
            'installment_count' => null,
            'installment_amount' => null,
            'deposit_amount' => null,
            'early_bird_price' => null,
            'early_bird_until' => null,
            'early_bird_seats' => null,
            'referral_discount' => null,
        ])->assertOk()
            ->assertJsonPath('data.installment_count', null)
            ->assertJsonPath('data.installment_amount', null)
            ->assertJsonPath('data.deposit_amount', null)
            ->assertJsonPath('data.early_bird_price', null)
            ->assertJsonPath('data.early_bird_until', null)
            ->assertJsonPath('data.early_bird_seats', null)
            ->assertJsonPath('data.referral_discount', null)
            ->assertJsonPath('data.early_bird_active', false)
            ->assertJsonPath('data.effective_price', '100.00');
    }

    public function test_payment_option_validation_errors(): void
    {
        $this->actingAdmin();
        $course = $this->makeCourse();
        $base = [
            'title' => 'Validation class',
            'format' => 'online',
            'seats' => 10,
            'status' => 'open',
            'price' => 100,
            'currency' => 'USD',
        ];

        $this->postJson("/api/admin/courses/{$course->id}/cohorts", $base + [
            'installment_count' => 1,
            'installment_amount' => 50,
        ])->assertStatus(422)->assertJsonValidationErrors(['installment_count']);

        $this->postJson("/api/admin/courses/{$course->id}/cohorts", $base + [
            'installment_count' => 13,
            'installment_amount' => 10,
        ])->assertStatus(422)->assertJsonValidationErrors(['installment_count']);

        $this->postJson("/api/admin/courses/{$course->id}/cohorts", $base + [
            'installment_count' => 3,
        ])->assertStatus(422)->assertJsonValidationErrors(['installment_amount']);

        $this->postJson("/api/admin/courses/{$course->id}/cohorts", $base + [
            'deposit_amount' => -1,
        ])->assertStatus(422)->assertJsonValidationErrors(['deposit_amount']);

        $this->postJson("/api/admin/courses/{$course->id}/cohorts", $base + [
            'early_bird_price' => 100,
        ])->assertStatus(422)->assertJsonValidationErrors(['early_bird_price']);

        $this->postJson("/api/admin/courses/{$course->id}/cohorts", [
            'title' => 'No price',
            'format' => 'online',
            'seats' => 10,
            'status' => 'open',
            'currency' => 'USD',
            'early_bird_price' => 50,
        ])->assertStatus(422)->assertJsonValidationErrors(['early_bird_price']);

        $this->postJson("/api/admin/courses/{$course->id}/cohorts", $base + [
            'early_bird_price' => 80,
            'early_bird_until' => 'not-a-date',
        ])->assertStatus(422)->assertJsonValidationErrors(['early_bird_until']);

        $this->postJson("/api/admin/courses/{$course->id}/cohorts", $base + [
            'early_bird_price' => 80,
            'early_bird_seats' => 0,
        ])->assertStatus(422)->assertJsonValidationErrors(['early_bird_seats']);

        $this->postJson("/api/admin/courses/{$course->id}/cohorts", $base + [
            'early_bird_price' => 80,
            'early_bird_seats' => 11,
        ])->assertStatus(422)->assertJsonValidationErrors(['early_bird_seats']);

        $this->postJson("/api/admin/courses/{$course->id}/cohorts", $base + [
            'deposit_amount' => 100,
        ])->assertStatus(422)->assertJsonValidationErrors(['deposit_amount']);

        $this->postJson("/api/admin/courses/{$course->id}/cohorts", [
            'title' => 'KHR class',
            'format' => 'in_person',
            'seats' => 8,
            'status' => 'open',
            'price' => 100000,
            'currency' => 'KHR',
            'deposit_amount' => 50.5,
        ])->assertStatus(422)->assertJsonValidationErrors(['deposit_amount']);
    }

    public function test_patch_early_bird_compares_against_stored_price(): void
    {
        $this->actingAdmin();
        $course = $this->makeCourse();

        $cohort = $course->cohorts()->create([
            'title' => 'Stored price class',
            'format' => 'online',
            'seats' => 10,
            'status' => 'open',
            'price' => 100,
            'currency' => 'USD',
        ]);

        $this->patchJson("/api/admin/courses/{$course->id}/cohorts/{$cohort->id}", [
            'early_bird_price' => 120,
        ])->assertStatus(422)->assertJsonValidationErrors(['early_bird_price']);
    }

    public function test_early_bird_active_and_effective_price_lifecycle(): void
    {
        $this->actingAdmin();
        $course = $this->makeCourse();

        $cohort = $course->cohorts()->create([
            'title' => 'Early bird class',
            'format' => 'online',
            'seats' => 10,
            'status' => 'open',
            'price' => 100,
            'currency' => 'USD',
            'early_bird_price' => 80,
            'early_bird_until' => '2026-10-20',
            'early_bird_seats' => 2,
        ]);

        $this->travelTo('2026-10-15');

        $this->getJson("/api/admin/courses/{$course->id}")
            ->assertOk()
            ->assertJsonPath('data.cohorts.0.early_bird_active', true)
            ->assertJsonPath('data.cohorts.0.effective_price', '80.00')
            ->assertJsonPath('data.cohorts.0.early_bird_seats_left', 2);

        $this->travelTo('2026-10-21');

        $this->getJson("/api/admin/courses/{$course->id}")
            ->assertOk()
            ->assertJsonPath('data.cohorts.0.early_bird_active', false)
            ->assertJsonPath('data.cohorts.0.effective_price', '100.00');

        $this->travelTo('2026-10-15');
        $cohort->update(['early_bird_until' => null]);

        CourseEnquiry::query()->create([
            'name' => 'A',
            'email' => 'a@example.com',
            'language' => 'en',
            'format' => 'online',
            'level' => 'Complete beginner',
            'message' => 'enrolled one',
            'status' => 'enrolled',
            'course_id' => $course->id,
            'cohort_id' => $cohort->id,
        ]);
        CourseEnquiry::query()->create([
            'name' => 'B',
            'email' => 'b@example.com',
            'language' => 'en',
            'format' => 'online',
            'level' => 'Complete beginner',
            'message' => 'enrolled two',
            'status' => 'enrolled',
            'course_id' => $course->id,
            'cohort_id' => $cohort->id,
        ]);
        CourseEnquiry::query()->create([
            'name' => 'C',
            'email' => 'c@example.com',
            'language' => 'en',
            'format' => 'online',
            'level' => 'Complete beginner',
            'message' => 'pipeline only',
            'status' => 'new',
            'course_id' => $course->id,
            'cohort_id' => $cohort->id,
        ]);
        CourseEnquiry::query()->create([
            'name' => 'D',
            'email' => 'd@example.com',
            'language' => 'en',
            'format' => 'online',
            'level' => 'Complete beginner',
            'message' => 'contacted only',
            'status' => 'contacted',
            'course_id' => $course->id,
            'cohort_id' => $cohort->id,
        ]);

        $this->getJson("/api/admin/courses/{$course->id}")
            ->assertOk()
            ->assertJsonPath('data.cohorts.0.early_bird_active', false)
            ->assertJsonPath('data.cohorts.0.early_bird_seats_left', 0)
            ->assertJsonPath('data.cohorts.0.effective_price', '100.00')
            ->assertJsonPath('data.cohorts.0.enrolled_count', 4)
            ->assertJsonPath('data.cohorts.0.seats_left', 8);
    }

    public function test_public_endpoints_include_payment_option_keys(): void
    {
        $course = $this->makeCourse();
        $until = now()->addWeek()->toDateString();

        $course->cohorts()->create([
            'title' => 'Public class',
            'format' => 'online',
            'seats' => 12,
            'status' => 'open',
            'price' => 99,
            'currency' => 'USD',
            'installment_count' => 3,
            'installment_amount' => 33,
            'deposit_amount' => 15,
            'early_bird_price' => 79,
            'early_bird_until' => $until,
            'early_bird_seats' => 4,
            'referral_discount' => 9,
            'min_students' => 4,
        ]);

        foreach (['/api/courses', '/api/courses/payment-demo-course'] as $uri) {
            $response = $this->getJson($uri)->assertOk();

            $cohortPath = str_contains($uri, 'payment-demo')
                ? 'data.cohorts.0'
                : 'data.0.cohorts.0';

            $response
                ->assertJsonPath("{$cohortPath}.price", '99.00')
                ->assertJsonPath("{$cohortPath}.currency", 'USD')
                ->assertJsonPath("{$cohortPath}.status", 'open')
                ->assertJsonPath("{$cohortPath}.seats_left", 12)
                ->assertJsonPath("{$cohortPath}.enrolled_count", 0)
                ->assertJsonPath("{$cohortPath}.min_students", 4)
                ->assertJsonPath("{$cohortPath}.installment_count", 3)
                ->assertJsonPath("{$cohortPath}.installment_amount", '33.00')
                ->assertJsonPath("{$cohortPath}.deposit_amount", '15.00')
                ->assertJsonPath("{$cohortPath}.early_bird_price", '79.00')
                ->assertJsonPath("{$cohortPath}.early_bird_until", $until)
                ->assertJsonPath("{$cohortPath}.early_bird_seats", 4)
                ->assertJsonPath("{$cohortPath}.referral_discount", '9.00')
                ->assertJsonPath("{$cohortPath}.early_bird_active", true)
                ->assertJsonPath("{$cohortPath}.effective_price", '79.00');
        }
    }

    public function test_enquiry_contract_unchanged_with_referred_by_prefix(): void
    {
        $message = "[Referred by: Demo Friend] I'd like to join";

        $this->postJson('/api/course-enquiries', [
            'name' => 'Sok Dara',
            'email' => 'dara@example.com',
            'language' => 'en',
            'format' => 'online',
            'level' => 'Complete beginner',
            'message' => $message,
            'website' => '',
        ])->assertCreated();

        $this->assertDatabaseHas('course_enquiries', [
            'email' => 'dara@example.com',
            'message' => $message,
        ]);
    }
}
