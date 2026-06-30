<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\EventReminder;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEventReminderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public User $user, public Event $event) {}

    public function handle(PushNotificationService $pushService): void
    {
        if (! (bool) Setting::get('notify_event_reminders_enabled')) {
            return;
        }

        $this->user->notify(new EventReminder($this->event));

        $pushService->send(
            $this->user,
            'Event Reminder',
            "Your event \"{$this->event->title}\" starts soon, insha'Allah!",
            route('events.show', $this->event->slug)
        );
    }
}
