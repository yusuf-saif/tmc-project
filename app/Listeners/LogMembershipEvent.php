<?php

namespace App\Listeners;

use App\Events\MembershipApproved;
use App\Events\MembershipNeedsCorrection;
use App\Events\MembershipRejected;
use App\Events\MembershipSubmitted;
use App\Services\AuditLogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogMembershipEvent implements ShouldQueue
{
    public string $queue = 'membership';

    public int $timeout = 30;

    public function handle(
        MembershipSubmitted|MembershipApproved|MembershipRejected|MembershipNeedsCorrection $event,
    ): void {
        try {
            match (true) {
                $event instanceof MembershipSubmitted => $this->logSubmitted($event),
                $event instanceof MembershipApproved => $this->logApproved($event),
                $event instanceof MembershipRejected => $this->logRejected($event),
                $event instanceof MembershipNeedsCorrection => $this->logNeedsCorrection($event),
            };
        } catch (\Throwable $e) {
            Log::error('Failed to log membership event', [
                'event_class' => $event::class,
                'profile_id' => $event->profile->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function logSubmitted(MembershipSubmitted $event): void
    {
        AuditLogService::log(
            action: 'membership_submitted',
            model: $event->profile,
            old: ['onboarding_status' => 'in_progress'],
            new: ['onboarding_status' => 'pending_review', 'submitted_at' => now()],
            actor: $event->actor,
            targetUserId: $event->actor->id,
        );
    }

    protected function logApproved(MembershipApproved $event): void
    {
        AuditLogService::log(
            action: 'membership_id_generated',
            model: $event->profile,
            old: [],
            new: [
                'membership_id' => $event->membershipId,
                'membership_type' => $event->membershipType,
                'hijri_year' => $event->profile->hijri_year,
            ],
            actor: $event->actor,
            targetUserId: $event->profile->user_id,
        );
    }

    protected function logRejected(MembershipRejected $event): void
    {
        AuditLogService::log(
            action: 'membership_rejected',
            model: $event->profile,
            old: ['onboarding_status' => 'pending_review'],
            new: ['onboarding_status' => 'rejected', 'rejection_reason' => $event->reason],
            actor: $event->actor,
            targetUserId: $event->profile->user_id,
        );
    }

    protected function logNeedsCorrection(MembershipNeedsCorrection $event): void
    {
        AuditLogService::log(
            action: 'membership_needs_correction',
            model: $event->profile,
            old: ['onboarding_status' => 'pending_review'],
            new: ['onboarding_status' => 'needs_correction', 'needs_correction_notes' => $event->notes],
            actor: $event->actor,
            targetUserId: $event->profile->user_id,
        );
    }
}
