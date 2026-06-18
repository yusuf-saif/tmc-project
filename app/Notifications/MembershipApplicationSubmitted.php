<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MembershipApplicationSubmitted extends Notification
{
    use Queueable;

    public function __construct(
        public User $applicant,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New Membership Application',
            'body' => "{$this->applicant->name} has submitted a membership application for review.",
            'action_url' => '/admin/membership-applications',
            'applicant_id' => $this->applicant->id,
        ];
    }
}
