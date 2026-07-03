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
            ->subject('Welcome to The Muhsinat Club — Set Your Password')
            ->greeting('Assalamu Alaikum,')
            ->line('Your membership account has been created.')
            ->line('Please set your password using the link below to access your dashboard.')
            ->action('Set Your Password', $resetUrl)
            ->line('This password set link will expire in 60 minutes.')
            ->line('If you did not expect this email, please contact our support team.');
    }
}
