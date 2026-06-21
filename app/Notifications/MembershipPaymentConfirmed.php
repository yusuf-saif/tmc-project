<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipPaymentConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $membershipId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to The Muhsinat Club — Payment Confirmed')
            ->greeting('Assalamu Alaikum,')
            ->line('Your membership payment has been confirmed.')
            ->line("Your Membership ID: **{$this->membershipId}**")
            ->line('Your account is now active. You have full access to your member dashboard.')
            ->action('Go to Dashboard', url('/home'));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Welcome to The Muhsinat Club',
            'body' => 'Your payment is confirmed. Your account is now active with full access.',
            'action_url' => '/home',
        ];
    }
}
