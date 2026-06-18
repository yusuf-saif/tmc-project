<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipRejected extends Notification
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
            ->subject('Membership Application Update')
            ->line('Thank you for your interest in The Muhsinat Club.')
            ->line('After careful review, we regret to inform you that your membership application has been rejected.')
            ->when($this->reason, fn ($message) => $message->line("Reason: {$this->reason}"))
            ->line('If you have any questions, please contact us.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Membership Application Rejected',
            'body' => $this->reason ? "Your application has been rejected. Reason: {$this->reason}" : 'Your application has been rejected.',
            'action_url' => null,
        ];
    }
}
