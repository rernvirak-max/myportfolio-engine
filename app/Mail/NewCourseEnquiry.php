<?php

namespace App\Mail;

use App\Models\CourseEnquiry;
use App\Services\EnquiryNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewCourseEnquiry extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CourseEnquiry $enquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Course enquiry – {$this->enquiry->name}",
            replyTo: [new Address($this->enquiry->email, $this->enquiry->name)],
        );
    }

    public function content(): Content
    {
        return new Content(text: 'mail.course-enquiry', with: ['body' => EnquiryNotifier::summary($this->enquiry)]);
    }
}
