<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OnboardingInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $token,
        public string $membershipId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiryHours = Setting::get('onboarding_token_expiry_hours', 72);

        $url = route('onboarding.form', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
            'member_id' => $this->membershipId,
        ]);

        return (new MailMessage)
            ->subject("You're invited to The Muhsinat Club — complete your account")
            ->greeting('Assalamu Alaykum '.$notifiable->name.',')
            ->line('You have been added to The Muhsinat Club.')
            ->line('Your Membership ID is: '.$this->membershipId)
            ->line('Click the button below to set up your account:')
            ->action('Complete My Account', $url)
            ->line('This link will expire in '.$expiryHours.' hours.')
            ->line('JazakAllahu Khairan')
            ->salutation('The Muhsinat Club Team');
    }
}
