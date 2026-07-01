<?php

namespace App\Services;

use App\Jobs\SendEventReminderNotification;
use App\Models\Event;
use App\Models\EventRsvp;
use App\Models\JannahCoinsLedger;
use App\Models\User;
use App\Models\Setting;
use DomainException;

class RsvpService
{
    public function rsvp(User $user, Event $event): void
    {
        if ($event->status !== 'published') {
            throw new DomainException('Only published events can accept RSVPs.');
        }

        $rsvp = EventRsvp::query()->firstOrNew([
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);

        if ($rsvp->exists && $rsvp->cancelled_at === null) {
            return;
        }

        $rsvp->fill([
            'rsvp_at' => now(),
            'cancelled_at' => null,
        ])->save();

        if ($event->coin_reward > 0) {
            $this->awardEventCoins($user, $event);
        }

        $reminderHours = (int) Setting::get('event_reminder_hours_before');

        SendEventReminderNotification::dispatch($user, $event)
            ->delay($event->event_date->copy()->subHours($reminderHours)->max(now()));
    }

    public function cancel(User $user, Event $event): void
    {
        $rsvp = EventRsvp::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->active()
            ->first();

        if (! $rsvp) {
            return;
        }

        $rsvp->cancelled_at = now();
        $rsvp->save();
    }

    public function isRsvpd(User $user, Event $event): bool
    {
        return EventRsvp::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->active()
            ->exists();
    }

    protected function awardEventCoins(User $user, Event $event): void
    {
        $alreadyAwarded = JannahCoinsLedger::query()
            ->where('user_id', $user->id)
            ->where('reference_id', $event->id)
            ->where('reason', 'event_attendance')
            ->exists();

        if ($alreadyAwarded) {
            return;
        }

        CoinsService::award($user, $event->coin_reward, 'event_attendance', $event->id);
    }
}
