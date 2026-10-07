<?php

namespace App\Jobs;

use App\Models\CourseEnquiry;
use App\Services\EnquiryNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Queue-safe wrapper. Dispatched with dispatchSync by default
 * (ENQUIRY_NOTIFY_QUEUE=false); set it true + run a worker to send async.
 */
class NotifyCourseEnquiry implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public CourseEnquiry $enquiry) {}

    public function handle(EnquiryNotifier $notifier): void
    {
        $notifier->notify($this->enquiry);
    }
}
