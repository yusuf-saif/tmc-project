<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EventReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Event $event,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Event Reminder',
            'body' => "Your event \"{$this->event->title}\" starts soon, insha'Allah!",
            'action_url' => route('events.show', $this->event->slug),
        ];
    }
}
