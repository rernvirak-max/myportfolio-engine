<?php

namespace Tests\Feature;

use App\Models\CourseEnquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'admin@example.com',
            'password' => 'password123',
        ], $overrides));
    }

    private function enquiry(array $overrides = []): CourseEnquiry
    {
        return CourseEnquiry::query()->create(array_merge([
            'name' => 'Sok Dara',
            'email' => 'dara@example.com',
            'contact' => '@sokdara',
            'language' => 'km',
            'format' => 'online',
            'level' => 'Complete beginner',
            'message' => 'Hello',
            'status' => 'new',
        ], $overrides));
    }

    private function actingAsAdmin(?User $user = null): User
    {
        $user ??= $this->admin();
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_login_returns_token(): void
    {
        $this->admin();

        $this->postJson('/api/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
    }

    public function test_login_rejects_bad_credentials(): void
    {
        $this->admin();

        $this->postJson('/api/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_protected_routes_require_auth(): void
    {
        $this->getJson('/api/admin/me')->assertUnauthorized();
        $this->getJson('/api/admin/stats')->assertUnauthorized();
        $this->getJson('/api/admin/course-enquiries')->assertUnauthorized();
        $this->getJson('/api/admin/overview')->assertUnauthorized();
    }

    public function test_me_and_logout(): void
    {
        $user = $this->admin();
        $token = $user->createToken('admin')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/admin/me')
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);

        $this->withToken($token)
            ->postJson('/api/admin/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_stats_and_list_filter_search_and_pagination(): void
    {
        $this->actingAsAdmin();
        $this->enquiry(['name' => 'Alice', 'email' => 'alice@example.com', 'status' => 'new']);
        $this->enquiry(['name' => 'Bob', 'email' => 'bob@example.com', 'status' => 'contacted', 'contact' => 'telegram-bob']);
        $this->enquiry(['name' => 'Cara', 'email' => 'cara@example.com', 'status' => 'enrolled']);

        $this->getJson('/api/admin/stats')
            ->assertOk()
            ->assertJson([
                'total' => 3,
                'by_status' => [
                    'new' => 1,
                    'contacted' => 1,
                    'enrolled' => 1,
                    'declined' => 0,
                ],
            ]);

        $this->getJson('/api/admin/course-enquiries?status=contacted')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Bob')
            ->assertJsonStructure(['current_page', 'last_page', 'total', 'from', 'to']);

        $this->getJson('/api/admin/course-enquiries?search=alice')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'alice@example.com');
    }

    public function test_show_update_and_delete_enquiry(): void
    {
        $this->actingAsAdmin();
        $enquiry = $this->enquiry();

        $this->getJson("/api/admin/course-enquiries/{$enquiry->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Sok Dara');

        // Resource may be wrapped or bare depending on show() — assert via resolved body.
        $show = $this->getJson("/api/admin/course-enquiries/{$enquiry->id}");
        $this->assertTrue(
            data_get($show->json(), 'name') === 'Sok Dara'
            || data_get($show->json(), 'data.name') === 'Sok Dara'
        );

        $this->patchJson("/api/admin/course-enquiries/{$enquiry->id}", [
            'status' => 'contacted',
            'admin_note' => 'Called Monday',
        ])->assertOk()
            ->assertJsonPath('data.status', 'contacted')
            ->assertJsonPath('data.admin_note', 'Called Monday');

        $this->assertDatabaseHas('course_enquiries', [
            'id' => $enquiry->id,
            'status' => 'contacted',
            'admin_note' => 'Called Monday',
        ]);

        $this->deleteJson("/api/admin/course-enquiries/{$enquiry->id}")
            ->assertOk();

        $this->assertDatabaseMissing('course_enquiries', ['id' => $enquiry->id]);
    }

    public function test_update_validates_status(): void
    {
        $this->actingAsAdmin();
        $enquiry = $this->enquiry();

        $this->patchJson("/api/admin/course-enquiries/{$enquiry->id}", [
            'status' => 'nope',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_csv_export(): void
    {
        $this->actingAsAdmin();
        $this->enquiry(['name' => 'Export Me', 'email' => 'export@example.com']);

        $response = $this->get('/api/admin/course-enquiries/export');
        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('Export Me', $response->streamedContent());
        $this->assertStringContainsString('export@example.com', $response->streamedContent());
    }

    public function test_overview_payload(): void
    {
        $this->actingAsAdmin();

        $recent = $this->enquiry(['status' => 'new']);
        $recent->forceFill(['created_at' => now()->subDays(2)])->save();

        $mid = $this->enquiry(['status' => 'enrolled', 'email' => 'e2@example.com']);
        $mid->forceFill(['created_at' => now()->subDays(10)])->save();

        $old = $this->enquiry(['status' => 'declined', 'email' => 'e3@example.com']);
        $old->forceFill(['created_at' => now()->subDays(40)])->save();

        $this->getJson('/api/admin/overview')
            ->assertOk()
            ->assertJsonStructure([
                'by_status' => ['new', 'contacted', 'enrolled', 'declined'],
                'total',
                'new_last_7_days',
                'new_last_30_days',
                'conversion_rate',
                'daily_counts',
                'latest',
            ])
            ->assertJsonPath('total', 3)
            ->assertJsonPath('new_last_7_days', 1)
            ->assertJsonPath('new_last_30_days', 2)
            ->assertJsonPath('conversion_rate', round(1 / 3, 4));

        $this->assertCount(30, $this->getJson('/api/admin/overview')->json('daily_counts'));
        $this->assertIsArray($this->getJson('/api/admin/overview')->json('latest'));
    }
}
