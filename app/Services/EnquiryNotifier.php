<?php

namespace App\Services;

use App\Mail\NewCourseEnquiry;
use App\Models\CourseEnquiry;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends Telegram + email alerts for a new enquiry. Every channel is
 * best-effort: failures are logged and never thrown.
 */
class EnquiryNotifier
{
    public function notify(CourseEnquiry $enquiry): void
    {
        $this->telegram($enquiry);
        $this->email($enquiry);
    }

    public function telegram(CourseEnquiry $enquiry): void
    {
        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (! $token || ! $chatId) {
            Log::warning('Course enquiry Telegram notification skipped: TELEGRAM_BOT_TOKEN / TELEGRAM_CHAT_ID not set.');

            return;
        }

        try {
            $response = Http::timeout(10)->asForm()->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => self::summary($enquiry),
                'disable_web_page_preview' => 'true',
            ]);

            if ($response->failed()) {
                Log::error('Course enquiry Telegram notification failed.', [
                    'enquiry_id' => $enquiry->id,
                    'status' => $response->status(),
                ]);
            }
        } catch (Throwable $e) {
            Log::error('Course enquiry Telegram notification error.', ['enquiry_id' => $enquiry->id, 'error' => $e->getMessage()]);
        }
    }

    public function email(CourseEnquiry $enquiry): void
    {
        $to = config('services.enquiry.notify_email');

        if (! $to) {
            Log::warning('Course enquiry email notification skipped: ENQUIRY_NOTIFY_EMAIL not set.');

            return;
        }

        try {
            Mail::to($to)->send(new NewCourseEnquiry($enquiry));
        } catch (Throwable $e) {
            Log::error('Course enquiry email notification error.', ['enquiry_id' => $enquiry->id, 'error' => $e->getMessage()]);
        }
    }

    public static function summary(CourseEnquiry $e): string
    {
        $formats = ['online' => 'Online', 'in_person' => 'In person', 'either' => 'Either'];

        return implode("\n", [
            "📚 New course enquiry #{$e->id}",
            '',
            "Name: {$e->name}",
            "Email: {$e->email}",
            'Phone/Telegram: '.($e->contact ?: '—'),
            'Language: '.($e->language === 'km' ? 'Khmer' : 'English'),
            'Format: '.($formats[$e->format] ?? $e->format),
            "Level: {$e->level}",
            '',
            $e->message,
        ]);
    }
}
