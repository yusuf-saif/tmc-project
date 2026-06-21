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
use App\Notifications\BusinessActivated as BusinessActivatedNotification;
use App\Notifications\BusinessApproved as BusinessApprovedNotification;
use App\Notifications\BusinessSuspended as BusinessSuspendedNotification;
use App\Notifications\SubscriptionActivated as SubscriptionActivatedNotification;
use App\Notifications\SubscriptionExpired as SubscriptionExpiredNotification;
use App\Notifications\SubscriptionExpiringSoon as SubscriptionExpiringSoonNotification;
use App\Notifications\SubscriptionPaymentFailed as SubscriptionPaymentFailedNotification;
use App\Notifications\SubscriptionPaymentReceived as SubscriptionPaymentReceivedNotification;
use App\Notifications\SubscriptionSuspended as SubscriptionSuspendedNotification;
use App\Services\HijriDateService;
use Illuminate\Support\Facades\Log;

class SendBillingNotifications
{
    public function handle(
        SubscriptionActivated|SubscriptionExpired|SubscriptionExpiringSoon|SubscriptionSuspended|SubscriptionPaymentReceived|SubscriptionPaymentFailed|BusinessApproved|BusinessActivated|BusinessSuspended $event,
    ): void {
        try {
            match (true) {
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
            Log::error('Failed to send billing notification', [
                'event_class' => $event::class,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function subscriptionActivated(SubscriptionActivated $event): void
    {
        $hijri = app(HijriDateService::class);
        $event->subscription->user->notify(new SubscriptionActivatedNotification(
            $event->subscription,
            $hijri->formatHijriDate($event->subscription->end_date, 'd M Y'),
        ));
    }

    protected function subscriptionExpired(SubscriptionExpired $event): void
    {
        $event->subscription->user->notify(new SubscriptionExpiredNotification(
            $event->subscription->plan?->name ?? 'Membership',
        ));
    }

    protected function subscriptionExpiringSoon(SubscriptionExpiringSoon $event): void
    {
        $event->subscription->user->notify(new SubscriptionExpiringSoonNotification(
            $event->subscription,
            $event->daysRemaining,
        ));
    }

    protected function subscriptionSuspended(SubscriptionSuspended $event): void
    {
        $event->subscription->user->notify(new SubscriptionSuspendedNotification(
            $event->reason,
        ));
    }

    protected function paymentReceived(SubscriptionPaymentReceived $event): void
    {
        $event->subscription->user->notify(new SubscriptionPaymentReceivedNotification(
            $event->amount,
            $event->subscription->plan?->name ?? 'Subscription',
        ));
    }

    protected function paymentFailed(SubscriptionPaymentFailed $event): void
    {
        $event->subscription->user->notify(new SubscriptionPaymentFailedNotification(
            $event->reason,
        ));
    }

    protected function businessApproved(BusinessApproved $event): void
    {
        $event->listing->owner->notify(new BusinessApprovedNotification(
            $event->listing->business_name,
            $event->monthlyFee,
        ));
    }

    protected function businessActivated(BusinessActivated $event): void
    {
        $event->listing->owner->notify(new BusinessActivatedNotification(
            $event->listing->business_name,
        ));
    }

    protected function businessSuspended(BusinessSuspended $event): void
    {
        $event->listing->owner->notify(new BusinessSuspendedNotification(
            $event->listing->business_name,
            $event->reason,
        ));
    }
}
