<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionPaymentFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment Failed – The Muhsinat Club')
            ->greeting('Assalamu Alaikum!')
            ->line('We were unable to process your subscription payment.')
            ->line("Reason: {$this->reason}")
            ->line('Please update your payment information to continue your subscription.')
            ->action('Update Payment', url('/subscription/payment'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Payment Failed',
            'body' => "Payment failed. Reason: {$this->reason}",
            'action_url' => '/subscription/payment',
        ];
    }
}
