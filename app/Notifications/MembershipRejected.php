<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Membership Application Rejected')
            ->line('Your membership submission was reviewed and rejected.')
            ->when($this->reason, fn ($message) => $message->line("Reason: {$this->reason}"))
            ->line('You can log in and resubmit after updating your details.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Membership Application Rejected',
            'body' => $this->reason ? "Your application has been rejected. Reason: {$this->reason}" : 'Your application has been rejected.',
            'action_url' => '/membership/onboarding',
        ];
    }
}
