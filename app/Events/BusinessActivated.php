<?php

namespace App\Events;

use App\Models\SouqListing;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class BusinessActivated
{
    use Dispatchable;

    public function __construct(
        public SouqListing $listing,
        public User $actor,
    ) {}
}
