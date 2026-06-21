<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionPaymentReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public float $amount,
        public string $planName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment Received – The Muhsinat Club')
            ->greeting('Assalamu Alaikum!')
            ->line("We have received your payment of $ {$this->amount} for {$this->planName}.")
            ->line('Thank you for your continued support.');
    }
}
