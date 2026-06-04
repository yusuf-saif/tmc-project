<?php

namespace App\Livewire\Events;

use App\Models\Event;
use App\Services\RsvpService;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EventsList extends Component
{
    public string $tab = 'upcoming';

    public function getEventsProperty(): Collection
    {
        return match ($this->tab) {
            'past' => Event::query()
                ->published()
                ->past()
                ->withCount(['rsvps as active_rsvps_count' => fn ($query) => $query->active()])
                ->orderByDesc('event_date')
                ->get(),
            'my_rsvps' => Event::query()
                ->published()
                ->whereHas('rsvps', fn ($query) => $query
                    ->active()
                    ->where('user_id', Auth::id()))
                ->withCount(['rsvps as active_rsvps_count' => fn ($query) => $query->active()])
                ->orderByRaw('case when event_date >= ? then 0 else 1 end asc', [now()])
                ->orderBy('event_date')
                ->get(),
            default => Event::query()
                ->published()
                ->upcoming()
                ->withCount(['rsvps as active_rsvps_count' => fn ($query) => $query->active()])
                ->orderBy('event_date')
                ->get(),
        };
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, ['upcoming', 'past', 'my_rsvps'], true)) {
            $this->tab = $tab;
        }
    }

    public function rsvp(int $eventId, RsvpService $rsvpService): void
    {
        try {
            $rsvpService->rsvp(Auth::user(), Event::query()->findOrFail($eventId));

            session()->flash('success', 'RSVP confirmed. We saved your place, insha\'Allah.');
        } catch (DomainException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function cancelRsvp(int $eventId, RsvpService $rsvpService): void
    {
        $rsvpService->cancel(Auth::user(), Event::query()->findOrFail($eventId));

        session()->flash('success', 'Your RSVP has been cancelled.');
    }

    public function isRsvpd(int $eventId): bool
    {
        return Auth::user()->eventRsvps()
            ->where('event_id', $eventId)
            ->active()
            ->exists();
    }

    public function emptyStateMessage(): string
    {
        return match ($this->tab) {
            'past' => 'No past events yet',
            'my_rsvps' => 'You haven\'t RSVPd to any events yet',
            default => 'No upcoming events - check back soon, insha\'Allah',
        };
    }

    public function render()
    {
        return view('livewire.events.events-list')
            ->layout('layouts.app', ['title' => 'Events']);
    }
}
