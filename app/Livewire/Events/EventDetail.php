<?php

namespace App\Livewire\Events;

use App\Models\Event;
use App\Services\RsvpService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EventDetail extends Component
{
    public Event $event;

    public bool $isRsvpd = false;

    public function mount(string $slug, RsvpService $rsvpService): void
    {
        $this->event = Event::query()
            ->withCount(['rsvps as active_rsvps_count' => fn ($query) => $query->active()])
            ->whereIn('status', ['published', 'cancelled'])
            ->where('slug', $slug)
            ->firstOrFail();

        $this->isRsvpd = $rsvpService->isRsvpd(Auth::user(), $this->event);
    }

    public function rsvp(RsvpService $rsvpService): void
    {
        try {
            $rsvpService->rsvp(Auth::user(), $this->event);
            $this->isRsvpd = true;
            $this->refreshEventCount();

            session()->flash('success', 'RSVP confirmed. We saved your place, insha\'Allah.');
        } catch (DomainException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function cancelRsvp(RsvpService $rsvpService): void
    {
        $rsvpService->cancel(Auth::user(), $this->event);
        $this->isRsvpd = false;
        $this->refreshEventCount();

        session()->flash('success', 'Your RSVP has been cancelled.');
    }

    protected function refreshEventCount(): void
    {
        $this->event->loadCount(['rsvps as active_rsvps_count' => fn ($query) => $query->active()]);
    }

    public function render()
    {
        return view('livewire.events.event-detail')
            ->layout('layouts.app', ['title' => $this->event->title]);
    }
}
