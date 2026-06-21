<?php

use App\Jobs\SendBroadcastNotificationJob;
use App\Jobs\SendNewsletterEmailJob;
use App\Models\Announcement;
use App\Models\Broadcast;
use App\Models\InAppAnnouncement;
use App\Models\Newsletter;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Publish scheduled content announcements ──────────────────────
Schedule::call(function () {
    Announcement::query()
        ->where('status', 'scheduled')
        ->where('publish_at', '<=', now())
        ->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
})->everyMinute()->name('publish-scheduled-announcements');

// ─── Expire in-app announcements ──────────────────────────────────
Schedule::call(function () {
    InAppAnnouncement::query()
        ->where('status', 'active')
        ->where('expires_at', '<=', now())
        ->update(['status' => 'inactive']);
})->everyMinute()->name('expire-in-app-announcements');

// ─── Dispatch queued broadcasts ───────────────────────────────────
Schedule::call(function () {
    Broadcast::readyToSend()->each(function (Broadcast $broadcast) {
        SendBroadcastNotificationJob::dispatch($broadcast);
    });
})->everyMinute()->name('dispatch-queued-broadcasts');

// ─── Dispatch queued newsletters ──────────────────────────────────
Schedule::call(function () {
    Newsletter::readyToSend()->each(function (Newsletter $newsletter) {
        SendNewsletterEmailJob::dispatch($newsletter);
    });
})->everyMinute()->name('dispatch-queued-newsletters');

// ─── Expire subscriptions past their end_date ─────────────────────
Schedule::call(function () {
    App\Models\Subscription::query()
        ->where('status', 'active')
        ->whereNotNull('end_date')
        ->where('end_date', '<=', now())
        ->each(function (App\Models\Subscription $subscription) {
            App\Events\SubscriptionExpired::dispatch($subscription);
            $subscription->update(['status' => 'expired']);
        });
})->dailyAt('00:01')->name('expire-subscriptions');

// ─── Remind about subscriptions expiring in 7, 3, and 1 days ─────
Schedule::call(function () {
    $intervals = [7, 3, 1];
    foreach ($intervals as $days) {
        App\Models\Subscription::expiringBetween($days, $days)
            ->each(function (App\Models\Subscription $subscription) use ($days) {
                App\Events\SubscriptionExpiringSoon::dispatch($subscription, $days);
            });
    }
})->dailyAt('08:00')->name('subscription-expiry-reminders');

// ─── Expire business listing billing ──────────────────────────────
Schedule::call(function () {
    App\Models\SouqListing::query()
        ->where('billing_status', 'active')
        ->whereNotNull('billing_end_date')
        ->where('billing_end_date', '<=', now())
        ->each(function (App\Models\SouqListing $listing) {
            app(App\Services\BusinessStateService::class)->autoExpireBilling($listing);
        });
})->dailyAt('00:05')->name('expire-business-billing');
