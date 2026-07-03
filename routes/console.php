<?php

use App\Events\SubscriptionExpired;
use App\Events\SubscriptionExpiringSoon;
use App\Jobs\SendBroadcastNotificationJob;
use App\Jobs\SendNewsletterEmailJob;
use App\Models\Broadcast;
use App\Models\InAppAnnouncement;
use App\Models\Newsletter;
use App\Models\SouqListing;
use App\Models\Subscription;
use App\Services\BusinessStateService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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
        $newsletter->update(['status' => 'sending']);
        SendNewsletterEmailJob::dispatch($newsletter);
    });
})->everyMinute()->name('dispatch-queued-newsletters');

// ─── Expire subscriptions past their end_date ─────────────────────
Schedule::call(function () {
    Subscription::query()
        ->where('status', 'active')
        ->whereNotNull('end_date')
        ->where('end_date', '<=', now())
        ->each(function (Subscription $subscription) {
            SubscriptionExpired::dispatch($subscription);
            $subscription->update(['status' => 'expired']);
        });
})->dailyAt('00:01')->name('expire-subscriptions');

// ─── Remind about subscriptions expiring in 7, 3, and 1 days ─────
Schedule::call(function () {
    $intervals = [7, 3, 1];
    foreach ($intervals as $days) {
        Subscription::expiringBetween($days, $days)
            ->each(function (Subscription $subscription) use ($days) {
                SubscriptionExpiringSoon::dispatch($subscription, $days);
            });
    }
})->dailyAt('08:00')->name('subscription-expiry-reminders');

// ─── Expire business listing billing ──────────────────────────────
Schedule::call(function () {
    SouqListing::query()
        ->where('billing_status', 'active')
        ->whereNotNull('billing_end_date')
        ->where('billing_end_date', '<=', now())
        ->each(function (SouqListing $listing) {
            app(BusinessStateService::class)->autoExpireBilling($listing);
        });
})->dailyAt('00:05')->name('expire-business-billing');

// ─── Check membership grace periods ───────────────────────────────
Schedule::command('membership:check-grace-periods')
    ->dailyAt('00:10')
    ->name('check-membership-grace-periods');

// ─── Send membership renewal reminders ────────────────────────────
Schedule::command('membership:send-renewal-reminders')
    ->dailyAt('08:00')
    ->name('send-membership-renewal-reminders');

// ─── Database & file backups ──────────────────────────────────────
Schedule::command('backup:clean')
    ->dailyAt('01:30')
    ->name('backup-cleanup');

Schedule::command('backup:run')
    ->dailyAt('02:00')
    ->name('database-backup');
