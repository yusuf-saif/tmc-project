<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipApproved extends Notification implements ShouldQueue
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
            ->subject('Membership Approved — Payment Required')
            ->greeting("Assalamu Alaikum {$this->user->name},")
            ->line('Your membership application has been approved.')
            ->line("Membership Type: {$this->membershipType}")
            ->line("Membership ID: {$this->membershipId}")
            ->line('To complete your registration, please submit your membership payment.')
            ->line('Your account will be activated once payment is confirmed.')
            ->action('Complete Payment', url('/membership/payment'));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Membership Approved — Payment Required',
            'body' => "Your membership has been approved (ID: {$this->membershipId}). Please complete your payment to activate your account.",
            'action_url' => '/membership/payment',
        ];
    }
}
