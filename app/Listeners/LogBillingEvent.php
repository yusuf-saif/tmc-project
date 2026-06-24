<?php

namespace App\Listeners;

use App\Events\BusinessActivated;
use App\Events\BusinessApproved;
use App\Events\BusinessSuspended;
use App\Events\MembershipActivated;
use App\Events\SubscriptionActivated;
use App\Events\SubscriptionExpired;
use App\Events\SubscriptionExpiringSoon;
use App\Events\SubscriptionPaymentFailed;
use App\Events\SubscriptionPaymentReceived;
use App\Events\SubscriptionSuspended;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class LogBillingEvent
{
    public function handle(
        MembershipActivated|SubscriptionActivated|SubscriptionExpired|SubscriptionExpiringSoon|SubscriptionSuspended|SubscriptionPaymentReceived|SubscriptionPaymentFailed|BusinessApproved|BusinessActivated|BusinessSuspended $event,
    ): void {
        try {
            match (true) {
                $event instanceof MembershipActivated => $this->membershipActivated($event),
                $event instanceof SubscriptionActivated => $this->subscriptionActivated($event),
                $event instanceof SubscriptionExpired => $this->subscriptionExpired($event),
                $event instanceof SubscriptionExpiringSoon => $this->subscriptionExpiringSoon($event),
                $event instanceof SubscriptionSuspended => $this->subscriptionSuspended($event),
                $event instanceof SubscriptionPaymentReceived => $this->paymentReceived($event),
                $event instanceof SubscriptionPaymentFailed => $this->paymentFailed($event),
                $event instanceof BusinessApproved => $this->businessApproved($event),
                $event instanceof BusinessActivated => $this->businessActivated($event),
                $event instanceof BusinessSuspended => $this->businessSuspended($event),
            };
        } catch (\Throwable $e) {
            Log::error('LogBillingEvent failed', [
                'event_class' => $event::class,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function membershipActivated(MembershipActivated $event): void
    {
        AuditLogService::log(
            action: 'membership_activated',
            old: ['onboarding_status' => 'payment_processing'],
            new: ['onboarding_status' => 'active', 'membership_id' => $event->membershipId],
            actor: $event->actor,
            targetUserId: $event->user->id,
        );
    }

    protected function subscriptionActivated(SubscriptionActivated $event): void
    {
        AuditLogService::log(
            action: 'subscription_activated',
            old: ['status' => 'inactive'],
            new: ['status' => 'active', 'plan_id' => $event->subscription->subscription_plan_id],
            actor: $event->actor,
            targetUserId: $event->subscription->user_id,
        );
    }

    protected function subscriptionExpired(SubscriptionExpired $event): void
    {
        AuditLogService::log(
            action: 'subscription_expired',
            old: ['status' => 'active'],
            new: ['status' => 'expired'],
            targetUserId: $event->subscription->user_id,
        );
    }

    protected function subscriptionExpiringSoon(SubscriptionExpiringSoon $event): void
    {
        AuditLogService::log(
            action: 'subscription_expiring_soon',
            old: ['end_date' => $event->subscription->end_date?->toDateString()],
            new: ['days_remaining' => $event->daysRemaining],
            targetUserId: $event->subscription->user_id,
        );
    }

    protected function subscriptionSuspended(SubscriptionSuspended $event): void
    {
        AuditLogService::log(
            action: 'subscription_suspended',
            old: ['status' => 'active'],
            new: ['status' => 'suspended', 'reason' => $event->reason],
            actor: $event->actor,
            targetUserId: $event->subscription->user_id,
        );
    }

    protected function paymentReceived(SubscriptionPaymentReceived $event): void
    {
        AuditLogService::log(
            action: 'subscription_payment_received',
            old: ['balance_due' => true],
            new: ['amount' => $event->amount, 'payment_method' => $event->paymentMethod],
            targetUserId: $event->subscription->user_id,
        );
    }

    protected function paymentFailed(SubscriptionPaymentFailed $event): void
    {
        AuditLogService::log(
            action: 'subscription_payment_failed',
            old: [],
            new: ['reason' => $event->reason],
            targetUserId: $event->subscription->user_id,
        );
    }

    protected function businessApproved(BusinessApproved $event): void
    {
        AuditLogService::log(
            action: 'business_approved',
            old: ['status' => 'pending'],
            new: ['status' => 'approved', 'monthly_fee' => $event->monthlyFee],
            actor: $event->actor,
            targetUserId: $event->listing->user_id,
        );
    }

    protected function businessActivated(BusinessActivated $event): void
    {
        AuditLogService::log(
            action: 'business_activated',
            old: ['status' => 'approved'],
            new: ['status' => 'active'],
            actor: $event->actor,
            targetUserId: $event->listing->user_id,
        );
    }

    protected function businessSuspended(BusinessSuspended $event): void
    {
        AuditLogService::log(
            action: 'business_suspended',
            old: ['status' => 'active'],
            new: ['status' => 'suspended', 'reason' => $event->reason],
            actor: $event->actor,
            targetUserId: $event->listing->user_id,
        );
    }
}
