<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEventReminderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public User $user, public Event $event) {}

    public function handle(): void
    {
        if (! (bool) Setting::get('notify_event_reminders_enabled')) {
            Log::info("Event reminder suppressed (disabled in settings) for {$this->user->name} - {$this->event->title}");

            return;
        }

        Log::info("Event reminder for {$this->user->name} - {$this->event->title}");
    }
}
