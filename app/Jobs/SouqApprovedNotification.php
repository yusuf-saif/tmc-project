<?php

namespace App\Jobs;

use App\Models\SouqListing;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SouqApprovedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public User $user, public SouqListing $listing) {}

    public function handle(): void
    {
        Log::info("Souq approved for {$this->user->name}: {$this->listing->business_name}");
    }
}
