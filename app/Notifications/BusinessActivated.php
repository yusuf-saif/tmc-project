<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BusinessActivated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $businessName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Business Listing Active – The Muhsinat Club')
            ->greeting('Assalamu Alaikum!')
            ->line("Your business listing \"{$this->businessName}\" is now active and visible to the community.")
            ->line('May Allah bless your business.');
    }
}
