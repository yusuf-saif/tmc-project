<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpired extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $planName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Subscription Expired – The Muhsinat Club')
            ->greeting('Assalamu Alaikum!')
            ->line("Your {$this->planName} subscription has expired.")
            ->line('Please renew your subscription to continue enjoying our services.')
            ->action('Renew Now', url('/home'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Subscription Expired',
            'body' => "Your {$this->planName} subscription has expired.",
            'action_url' => '/home',
        ];
    }
}
