<?php

namespace App\Notifications\Academic;

use App\Models\Academic\Enrollment;
use App\Models\Academic\StudentUserLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnrollmentCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Enrollment $enrollment,
        protected string $type = 'student',
        protected ?StudentUserLink $studentUserLink = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->type === 'representative'
            ? __('messages.enrollments.representative_email_subject')
            : __('messages.enrollments.student_email_subject');

        $registerUrl = null;

        if ($this->type === 'representative' && $this->studentUserLink) {
            $registerUrl = rtrim((string) config('app.frontend_url'), '/')
                . '/' . app()->getLocale()
                . '/register/student-link?token='
                . urlencode($this->studentUserLink->token);
        }

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.academic.enrollments.created', [
                'enrollment' => $this->enrollment,
                'recipient' => $notifiable,
                'type' => $this->type,
                'studentUserLink' => $this->studentUserLink,
                'registerUrl' => $registerUrl,
            ]);
    }
}
