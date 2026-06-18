<?php

use App\Models\Announcement;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Announcement::query()
        ->where('status', 'scheduled')
        ->where('publish_at', '<=', now())
        ->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
})->everyMinute()->name('publish-scheduled-announcements');
