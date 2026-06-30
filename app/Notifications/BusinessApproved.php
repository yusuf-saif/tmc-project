<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BusinessApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $businessName,
        public float $monthlyFee,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Business Listing Approved – The Muhsinat Club')
            ->greeting('Assalamu Alaikum!')
            ->line("Your business listing \"{$this->businessName}\" has been approved.")
            ->line("Your monthly fee is \$ {$this->monthlyFee}.")
            ->line('Please complete your payment to activate the listing.')
            ->action('Complete Payment', url('/souq/payment'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Listing Approved',
            'body' => "Your business listing \"{$this->businessName}\" has been approved.",
            'action_url' => '/souq/payment',
        ];
    }
}
