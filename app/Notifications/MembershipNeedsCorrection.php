<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipNeedsCorrection extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $notes,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Membership Application Needs Correction')
            ->line('Your membership application needs some corrections.')
            ->line("Notes: {$this->notes}")
            ->action('Review Application', url('/membership/onboarding'))
            ->line('Please log in and update your application.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Application Needs Correction',
            'body' => "Your application needs correction: {$this->notes}",
            'action_url' => '/membership/onboarding',
        ];
    }
}
