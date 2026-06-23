<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class MembershipActivated
{
    use Dispatchable;

    public function __construct(
        public $user,
        public string $membershipId,
        public User $actor,
    ) {}
}
