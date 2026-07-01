<?php

namespace App\Policies;

use App\Models\JournalEntry;
use App\Models\User;

class JournalEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['member', 'moderator', 'content_editor']);
    }

    public function view(User $user, JournalEntry $entry): bool
    {
        return $user->id === $entry->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['member', 'moderator', 'content_editor']);
    }

    public function update(User $user, JournalEntry $entry): bool
    {
        return $user->id === $entry->user_id;
    }

    public function delete(User $user, JournalEntry $entry): bool
    {
        return $user->id === $entry->user_id;
    }
}
