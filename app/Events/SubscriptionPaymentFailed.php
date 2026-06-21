<?php

namespace App\Events;

use App\Models\Subscription;
use Illuminate\Foundation\Events\Dispatchable;

class SubscriptionPaymentFailed
{
    use Dispatchable;

    public function __construct(
        public Subscription $subscription,
        public string $reason,
    ) {}
}
