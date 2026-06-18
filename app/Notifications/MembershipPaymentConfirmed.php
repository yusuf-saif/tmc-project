<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipPaymentConfirmed extends Notification
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
            ->subject('Welcome to The Muhsinat Club')
            ->greeting('Assalamu Alaikum,')
            ->line('Your membership payment has been confirmed.')
            ->line("Your Membership ID: **{$this->membershipId}**")
            ->line('You now have full access to your member dashboard.')
            ->action('Go to Dashboard', url('/home'));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Welcome to The Muhsinat Club',
            'body' => 'Your payment is confirmed. You now have full access to your member dashboard.',
            'action_url' => '/home',
        ];
    }
}
