<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Event $event): bool
    {
        return in_array($event->status, ['published', 'cancelled'], true);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'moderator']);
    }

    public function update(User $user, Event $event): bool
    {
        return $user->id === $event->created_by
            || $user->hasAnyRole(['super_admin', 'admin', 'moderator']);
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }
}
