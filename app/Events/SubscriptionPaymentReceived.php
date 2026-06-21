<?php

namespace App\Events;

use App\Models\Subscription;
use Illuminate\Foundation\Events\Dispatchable;

class SubscriptionPaymentReceived
{
    use Dispatchable;

    public function __construct(
        public Subscription $subscription,
        public float $amount,
        public string $paymentMethod = 'manual',
    ) {}
}
