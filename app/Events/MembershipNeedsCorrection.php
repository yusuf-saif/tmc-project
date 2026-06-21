<?php

namespace App\Events;

use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class MembershipNeedsCorrection
{
    use Dispatchable;

    public function __construct(
        public MemberProfile $profile,
        public User $actor,
        public string $notes,
    ) {}
}
