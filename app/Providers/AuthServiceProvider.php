<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\JournalEntry;
use App\Models\SouqListing;
use App\Policies\EventPolicy;
use App\Policies\JournalEntryPolicy;
use App\Policies\SouqListingPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Event::class => EventPolicy::class,
        JournalEntry::class => JournalEntryPolicy::class,
        SouqListing::class => SouqListingPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
