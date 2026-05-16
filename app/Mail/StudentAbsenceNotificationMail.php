<?php

namespace App\Mail;

use App\Models\Academic\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class StudentAbsenceNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Student $student,
        public Collection $absenceRecords,
        public string $representativeName
    ) {}

    public function build(): self
    {
        return $this
            ->subject(__('messages.attendance.absence_email_subject'))
            ->view('emails.attendance.student-absence-notification');
    }
}
