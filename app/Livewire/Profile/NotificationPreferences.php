<?php

namespace App\Livewire\Profile;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class NotificationPreferences extends Component
{
    public bool $events = true;

    public bool $announcements = true;

    public bool $coins = true;

    public bool $community = true;

    public function mount(): void
    {
        $prefs = auth()->user()->memberProfile?->notification_preferences ?? [];

        $this->events = $prefs['events'] ?? $prefs['events_halaqahs'] ?? true;
        $this->announcements = $prefs['announcements'] ?? true;
        $this->coins = $prefs['coins'] ?? $prefs['coins_rewards'] ?? true;
        $this->community = $prefs['community'] ?? $prefs['community_updates'] ?? true;
    }

    public function save(): void
    {
        auth()->user()->memberProfile()->update([
            'notification_preferences' => [
                'events' => $this->events,
                'announcements' => $this->announcements,
                'coins' => $this->coins,
                'community' => $this->community,
            ],
        ]);

        session()->flash('success', 'Preferences saved');
    }

    public function render(): View
    {
        return view('livewire.profile.notification-prefs')
            ->layout('layouts.app', ['title' => 'Notifications']);
    }
}
