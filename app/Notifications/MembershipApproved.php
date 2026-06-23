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
        $legacyCardUrl = route('profile.legacy-card');
        $paymentUrl = route('membership.payment');

        return (new MailMessage)
            ->subject('Membership Approved — Welcome to TMC!')
            ->markdown('emails.membership.approved', [
                'user' => $this->user,
                'membershipId' => $this->membershipId,
                'membershipType' => $this->membershipType,
                'legacyCardUrl' => $legacyCardUrl,
                'paymentUrl' => $paymentUrl,
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Membership Approved',
            'body' => "Your membership (ID: {$this->membershipId}) has been approved. Please complete your payment to activate your account.",
            'action_url' => '/membership/payment',
        ];
    }
}
