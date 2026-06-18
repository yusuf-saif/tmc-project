<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipApproved extends Notification
{
    use Queueable;

    public function __construct(
        public User $user,
        public string $membershipId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Membership Application Approved')
            ->greeting("Assalamu Alaikum {$this->user->name},")
            ->line('Your membership application has been approved.')
            ->line("Your Membership ID: **{$this->membershipId}**")
            ->line('Please complete your membership fee payment to activate your account.')
            ->action('Complete Payment', url('/membership/payment'));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Membership Approved',
            'body' => "Your membership application has been approved. Your ID: {$this->membershipId}. Please complete your payment.",
            'action_url' => '/membership/payment',
        ];
    }
}
