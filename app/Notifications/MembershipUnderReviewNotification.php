<?php

namespace App\Notifications;

use App\Models\MemberProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipUnderReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public MemberProfile $profile) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your account is under review')
            ->greeting("Assalamu Alaikum {$notifiable->name},")
            ->line('Thank you for submitting your membership onboarding form.')
            ->line('Your account is now under review by our admin team.')
            ->line('We will notify you once a decision has been made.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Account under review',
            'body' => 'Your membership submission has been received and is under review.',
            'action_url' => route('membership.pending'),
        ];
    }
}
