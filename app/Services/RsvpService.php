<?php

namespace App\Services;

use App\Jobs\SendEventReminderNotification;
use App\Models\Event;
use App\Models\EventRsvp;
use App\Models\User;
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

        SendEventReminderNotification::dispatch($user, $event)
            ->delay($event->event_date->copy()->subDay()->max(now()));
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
}
