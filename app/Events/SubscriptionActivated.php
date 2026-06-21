<?php

namespace App\Events;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class SubscriptionActivated
{
    use Dispatchable;

    public function __construct(
        public Subscription $subscription,
        public User $actor,
    ) {}
}
