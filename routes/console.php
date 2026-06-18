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
