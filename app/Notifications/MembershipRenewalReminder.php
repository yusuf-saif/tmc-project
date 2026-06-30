<?php

namespace App\Notifications;

use App\Models\MemberProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipRenewalReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public MemberProfile $profile,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Renew Your Membership – The Muhsinat Club')
            ->greeting('Assalamu Alaikum!')
            ->line('Your membership period is ending soon.')
            ->line('Please renew your membership to continue enjoying full access.')
            ->action('Renew Now', url('/membership/payment'));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Renew Your Membership',
            'body' => 'Your membership period is ending soon. Renew now to keep your access active.',
            'action_url' => '/membership/payment',
        ];
    }
}
