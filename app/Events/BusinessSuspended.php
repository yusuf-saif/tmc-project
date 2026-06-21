<?php

namespace App\Events;

use App\Models\SouqListing;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class BusinessSuspended
{
    use Dispatchable;

    public function __construct(
        public SouqListing $listing,
        public User $actor,
        public string $reason,
    ) {}
}
