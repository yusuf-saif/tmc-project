<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionActivated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Subscription $subscription,
        public string $hijriEndDate,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Subscription Activated – The Muhsinat Club')
            ->greeting('Assalamu Alaikum!')
            ->line("Your {$this->subscription->plan?->name} subscription is now active.")
            ->line("Your subscription is valid until {$this->hijriEndDate} (Hijri).")
            ->line('Thank you for being part of The Muhsinat Club.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Subscription Activated',
            'body' => "Your {$this->subscription->plan?->name} subscription is now active.",
            'action_url' => '/',
        ];
    }
}
