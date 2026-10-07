<?php

namespace Tests\Feature;

use App\Mail\NewCourseEnquiry;
use App\Models\CourseEnquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class CourseEnquiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('course-enquiries');
        config(['services.telegram.bot_token' => null, 'services.telegram.chat_id' => null, 'services.enquiry.notify_email' => null]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Sok Dara',
            'email' => 'dara@example.com',
            'contact' => '@sokdara',
            'language' => 'km',
            'format' => 'online',
            'level' => 'Complete beginner',
            'message' => 'I would like to learn Laravel and Vue from scratch.',
            'website' => '',
        ], $overrides);
    }

    public function test_health(): void
    {
        $this->getJson('/api/health')->assertOk()->assertJson(['status' => 'ok']);
    }

    public function test_valid_enquiry_is_stored(): void
    {
        $this->postJson('/api/course-enquiries', $this->payload())
            ->assertCreated()
            ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('course_enquiries', [
            'email' => 'dara@example.com', 'language' => 'km', 'format' => 'online', 'status' => 'new', 'ip' => '127.0.0.1',
        ]);
    }

    public function test_validation_errors_return_422(): void
    {
        $this->postJson('/api/course-enquiries', $this->payload([
            'name' => '', 'email' => 'nope', 'language' => 'fr', 'format' => 'x', 'level' => 'x', 'message' => str_repeat('a', 2001), 'contact' => '!!',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'language', 'format', 'level', 'message', 'contact']);

        $this->assertDatabaseCount('course_enquiries', 0);
    }

    public function test_honeypot_is_silently_accepted_without_saving(): void
    {
        Http::fake();
        $this->postJson('/api/course-enquiries', $this->payload(['website' => 'http://spam.example']))
            ->assertCreated();

        $this->assertDatabaseCount('course_enquiries', 0);
        Http::assertNothingSent();
    }

    public function test_rate_limit_returns_429(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/course-enquiries', $this->payload())->assertCreated();
        }
        $this->postJson('/api/course-enquiries', $this->payload())
            ->assertStatus(429)->assertJsonStructure(['message']);
        $this->assertDatabaseCount('course_enquiries', 5);
    }

    public function test_notifications_are_sent(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        Mail::fake();
        config(['services.telegram.bot_token' => 'TOKEN', 'services.telegram.chat_id' => '123', 'services.enquiry.notify_email' => 'max@example.com']);

        $this->postJson('/api/course-enquiries', $this->payload())->assertCreated();

        Http::assertSent(fn ($r) => str_contains($r->url(), '/botTOKEN/sendMessage') && $r['chat_id'] === '123');
        Mail::assertSent(NewCourseEnquiry::class, fn ($m) => $m->hasTo('max@example.com'));
    }

    public function test_notification_failures_do_not_break_request(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 500)]);
        config(['services.telegram.bot_token' => 'TOKEN', 'services.telegram.chat_id' => '123',
            'services.enquiry.notify_email' => 'max@example.com', 'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1]);

        $this->postJson('/api/course-enquiries', $this->payload())->assertCreated();
        $this->assertDatabaseCount('course_enquiries', 1);

        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('down'));
        $this->postJson('/api/course-enquiries', $this->payload())->assertCreated();
        $this->assertDatabaseCount('course_enquiries', 2);
    }

    public function test_cors_allows_configured_origin(): void
    {
        $this->call('OPTIONS', '/api/course-enquiries', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost:5173',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ])->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
    }

    public function test_server_error_is_generic(): void
    {
        config(['app.debug' => false]);
        CourseEnquiry::creating(fn () => throw new \RuntimeException('secret db detail'));

        $res = $this->postJson('/api/course-enquiries', $this->payload())->assertStatus(500);
        $this->assertStringNotContainsString('secret', $res->getContent());
    }
}
