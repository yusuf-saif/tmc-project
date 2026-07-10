<?php

namespace App\Jobs;

use App\Mail\NewsletterMail;
use App\Models\Newsletter;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNewsletterEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public int $batchSize = 50;

    public function __construct(public Newsletter $newsletter) {}

    public function handle(NotificationService $notificationService): void
    {
        $users = $notificationService->resolveAudience(
            $this->newsletter->target_audience,
            $this->newsletter->audience_value
        );

        $count = 0;

        $users->chunk($this->batchSize)
            ->each(function ($chunk, $index) use (&$count) {
                $chunk->each(function ($user) use (&$count, $index) {
                    try {
                        Mail::to($user->email)
                            ->queue(new NewsletterMail($this->newsletter, $user))
                            ->delay(now()->addSeconds($index * 10));
                        $count++;
                    } catch (\Exception $e) {
                        Log::warning("Failed to queue newsletter email to {$user->email}: {$e->getMessage()}");
                    }
                });
            });

        if ($count > 0) {
            $this->newsletter->update([
                'status' => 'sent',
                'sent_count' => $count,
            ]);

            AuditLogService::log('newsletter_sent', $this->newsletter, [], [
                'subject' => $this->newsletter->subject,
                'audience' => $this->newsletter->target_audience,
                'sent_count' => $count,
            ]);

            Log::info("Newsletter #{$this->newsletter->id} queued for {$count} recipients.");
        } else {
            $this->newsletter->update([
                'status' => 'failed',
                'sent_count' => 0,
            ]);

            Log::warning("Newsletter #{$this->newsletter->id} had 0 eligible recipients — marked as failed.");
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->newsletter->update(['status' => 'failed']);
        Log::error("Newsletter #{$this->newsletter->id} failed: {$exception->getMessage()}");
    }
}
