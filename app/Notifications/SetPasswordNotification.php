<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $token,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Set Your Password — The Muhsinat Club')
            ->greeting('Assalamu Alaikum,')
            ->line('Use the link below to set a password for your TMC account.')
            ->action('Set Your Password', $resetUrl)
            ->line('This link will expire in 60 minutes.')
            ->line('If you did not request this, please contact our support team.');
    }
}
