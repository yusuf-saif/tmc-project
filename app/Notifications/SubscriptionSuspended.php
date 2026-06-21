<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionSuspended extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Subscription Suspended – The Muhsinat Club')
            ->greeting('Assalamu Alaikum!')
            ->line('Your subscription has been suspended.')
            ->line("Reason: {$this->reason}")
            ->line('Please contact the administration for further assistance.');
    }
}
