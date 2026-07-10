<?php

namespace App\Jobs;

use App\Models\Broadcast;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use App\Services\PushNotificationService;
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
        $this->broadcast->update(['status' => 'sending']);

        if ($this->broadcast->expires_at && $this->broadcast->expires_at->isPast()) {
            $this->broadcast->update(['status' => 'failed']);
            Log::info("Broadcast #{$this->broadcast->id} expired before delivery.");

            return;
        }

        $users = $notificationService->resolveAudience(
            $this->broadcast->target_audience,
            $this->broadcast->audience_value
        );

        $push = app(PushNotificationService::class);
        $count = 0;

        foreach ($users as $user) {
            try {
                $push->send($user, $this->broadcast->title, $this->broadcast->body, route('home'));
                $count++;
            } catch (\Exception $e) {
                Log::warning("Failed to send broadcast to user #{$user->id}: {$e->getMessage()}");
            }
        }

        $this->broadcast->update([
            'status' => 'sent',
            'delivery_count' => $count,
        ]);

        AuditLogService::log('broadcast_sent', $this->broadcast, [], [
            'title' => $this->broadcast->title,
            'audience' => $this->broadcast->target_audience,
            'delivery_count' => $count,
        ]);

        Log::info("Broadcast #{$this->broadcast->id} sent to {$count} users.");
    }

    public function failed(\Throwable $exception): void
    {
        $this->broadcast->update(['status' => 'failed']);
        Log::error("Broadcast #{$this->broadcast->id} failed: {$exception->getMessage()}");
    }
}
