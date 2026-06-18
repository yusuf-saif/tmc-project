<?php

namespace App\Jobs;

use App\Models\Broadcast;
use App\Notifications\BroadcastNotification;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBroadcastNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public Broadcast $broadcast) {}

    public function handle(NotificationService $notificationService): void
    {
        $broadcast->update(['status' => 'sending']);

        if ($broadcast->expires_at && $broadcast->expires_at->isPast()) {
            $broadcast->update(['status' => 'failed']);
            Log::info("Broadcast #{$broadcast->id} expired before delivery.");

            return;
        }

        $users = $notificationService->resolveAudience(
            $broadcast->target_audience,
            $broadcast->audience_value
        );

        $count = 0;

        foreach ($users as $user) {
            try {
                // FCM / Web Push integration point
                // If user has fcm_token or push_subscription, send here
                // For now, store as Laravel notification for in-app retrieval
                $user->notify(new BroadcastNotification($broadcast));
                $count++;
            } catch (\Exception $e) {
                Log::warning("Failed to send broadcast to user #{$user->id}: {$e->getMessage()}");
            }
        }

        $broadcast->update([
            'status' => 'sent',
            'delivery_count' => $count,
        ]);

        AuditLogService::log('broadcast_sent', $broadcast, [], [
            'title' => $broadcast->title,
            'audience' => $broadcast->target_audience,
            'delivery_count' => $count,
        ]);

        Log::info("Broadcast #{$broadcast->id} sent to {$count} users.");
    }

    public function failed(\Throwable $exception): void
    {
        $this->broadcast->update(['status' => 'failed']);
        Log::error("Broadcast #{$this->broadcast->id} failed: {$exception->getMessage()}");
    }
}
