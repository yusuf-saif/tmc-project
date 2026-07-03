<?php

namespace App\Policies;

use App\Models\SouqListing;
use App\Models\User;

class SouqListingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SouqListing $listing): bool
    {
        return $listing->status === 'active' || $user->id === $listing->user_id
            || $user->hasAnyRole(['super_admin', 'admin', 'moderator']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['member', 'moderator', 'content_editor']);
    }

    public function update(User $user, SouqListing $listing): bool
    {
        return $user->id === $listing->user_id
            || $user->hasAnyRole(['super_admin', 'admin', 'moderator']);
    }

    public function delete(User $user, SouqListing $listing): bool
    {
        return $user->id === $listing->user_id
            || $user->hasAnyRole(['super_admin', 'admin']);
    }
}
