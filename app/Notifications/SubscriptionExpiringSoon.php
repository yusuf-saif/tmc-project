<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiringSoon extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Subscription $subscription,
        public int $daysRemaining,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Subscription Expiring in {$this->daysRemaining} Days – The Muhsinat Club")
            ->greeting('Assalamu Alaikum!')
            ->line("Your {$this->subscription->planName()} subscription will expire in {$this->daysRemaining} day(s).")
            ->line('Please renew your subscription to avoid any interruption of services.')
            ->action('Renew Now', url('/home'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Subscription Expiring Soon',
            'body' => "Your {$this->subscription->planName()} subscription will expire in {$this->daysRemaining} day(s).",
            'action_url' => '/home',
        ];
    }
}
