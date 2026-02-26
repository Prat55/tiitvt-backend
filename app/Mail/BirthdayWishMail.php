<?php

namespace App\Mail;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BirthdayWishMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Student $student) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Happy Birthday from TIITVT',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.birthday-wishes',
            with: [
                'student' => $this->student,
            ],
        );
    }
}
