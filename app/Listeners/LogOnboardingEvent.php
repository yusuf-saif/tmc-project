<?php

namespace App\Listeners;

use App\Events\MemberOnboardingCompleted;
use App\Services\AuditLogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogOnboardingEvent implements ShouldQueue
{
    public string $queue = 'membership';

    public int $timeout = 30;

    public function handle(MemberOnboardingCompleted $event): void
    {
        try {
            AuditLogService::log(
                action: 'onboarding_completed',
                model: $event->user->memberProfile,
                old: ['status' => 'pending_onboarding'],
                new: ['status' => 'active', 'membership_id' => $event->membershipId],
                actor: $event->user,
                targetUserId: $event->user->id,
            );
        } catch (\Throwable $e) {
            Log::error('Failed to log onboarding event', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
