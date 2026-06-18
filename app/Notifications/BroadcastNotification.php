<?php

namespace App\Notifications;

use App\Models\Broadcast;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BroadcastNotification extends Notification
{
    use Queueable;

    public function __construct(public Broadcast $broadcast) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->broadcast->title)
            ->line($this->broadcast->body)
            ->action('View in App', url('/home'))
            ->line('— The Muhsinat Club');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'broadcast_id' => $this->broadcast->id,
            'title' => $this->broadcast->title,
            'body' => $this->broadcast->body,
            'type' => 'broadcast',
        ];
    }
}
