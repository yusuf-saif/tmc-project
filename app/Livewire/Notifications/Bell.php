<?php

namespace App\Livewire\Notifications;

use Livewire\Component;

class Bell extends Component
{
    public function getUnreadCountProperty(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    public function getRecentProperty()
    {
        return auth()->user()->notifications()
            ->latest()->limit(10)->get();
    }

    public function markAsRead(string $id): void
    {
        auth()->user()->notifications()
            ->where('id', $id)->first()?->markAsRead();
    }

    public function render()
    {
        return view('livewire.notifications.bell');
    }
}
