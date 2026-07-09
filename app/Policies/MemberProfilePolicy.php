<?php

namespace App\Policies;

use App\Models\MemberProfile;
use App\Models\User;

class MemberProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MemberProfile $memberProfile): bool
    {
        return $user->id === $memberProfile->user_id
            || $user->hasAnyRole(['super_admin', 'admin', 'moderator']);
    }

    public function update(User $user, MemberProfile $memberProfile): bool
    {
        return $user->id === $memberProfile->user_id
            || $user->hasAnyRole(['super_admin', 'admin', 'moderator']);
    }
}
