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
        public string $membershipType = 'M',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Membership Approved')
            ->greeting("Assalamu Alaikum {$this->user->name},")
            ->line('Your membership application has been approved.')
            ->line("Membership Type: {$this->membershipType}")
            ->line("Membership ID: {$this->membershipId}")
            ->line('You can now log in and access the full member area.')
            ->action('Log In', url('/login'));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Membership Approved',
            'body' => "Your membership has been approved. ID: {$this->membershipId}.",
            'action_url' => '/home',
        ];
    }
}
