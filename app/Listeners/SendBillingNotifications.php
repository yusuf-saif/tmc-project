<?php

namespace App\Listeners;

use App\Events\BusinessActivated;
use App\Events\BusinessApproved;
use App\Events\BusinessSuspended;
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
use App\Models\Setting;
use App\Services\HijriDateService;
use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendBillingNotifications implements ShouldQueue
{
    public string $queue = 'billing';

    public int $timeout = 60;

    public array $backoff = [10, 30, 60];

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
        $user = $event->subscription->user;
        $hijri = app(HijriDateService::class);
        $user->notify(new SubscriptionActivatedNotification(
            $event->subscription,
            $hijri->formatHijriDate($event->subscription->end_date, 'd M Y'),
        ));

        app(PushNotificationService::class)->send(
            $user,
            'Subscription Activated',
            "Your {$event->subscription->planName()} subscription is now active.",
        );
    }

    protected function subscriptionExpired(SubscriptionExpired $event): void
    {
        $user = $event->subscription->user;
        $planName = $event->subscription->planName();
        $user->notify(new SubscriptionExpiredNotification($planName));

        app(PushNotificationService::class)->send(
            $user,
            'Subscription Expired',
            "Your {$planName} subscription has expired.",
            route('home'),
        );
    }

    protected function subscriptionExpiringSoon(SubscriptionExpiringSoon $event): void
    {
        $user = $event->subscription->user;
        $user->notify(new SubscriptionExpiringSoonNotification(
            $event->subscription,
            $event->daysRemaining,
        ));

        app(PushNotificationService::class)->send(
            $user,
            'Subscription Expiring Soon',
            "Your subscription will expire in {$event->daysRemaining} day(s).",
            route('home'),
        );
    }

    protected function subscriptionSuspended(SubscriptionSuspended $event): void
    {
        $user = $event->subscription->user;
        $user->notify(new SubscriptionSuspendedNotification($event->reason));

        app(PushNotificationService::class)->send(
            $user,
            'Subscription Suspended',
            "Your subscription has been suspended.",
            route('home'),
        );
    }

    protected function paymentReceived(SubscriptionPaymentReceived $event): void
    {
        $user = $event->subscription->user;
        $planName = $event->subscription->planName();
        $user->notify(new SubscriptionPaymentReceivedNotification(
            $event->amount,
            $planName,
        ));

        app(PushNotificationService::class)->send(
            $user,
            'Payment Received',
            "Payment received for {$planName}.",
        );
    }

    protected function paymentFailed(SubscriptionPaymentFailed $event): void
    {
        $user = $event->subscription->user;
        $user->notify(new SubscriptionPaymentFailedNotification($event->reason));

        app(PushNotificationService::class)->send(
            $user,
            'Payment Failed',
            "Payment failed. {$event->reason}",
            route('home'),
        );
    }

    protected function businessApproved(BusinessApproved $event): void
    {
        if (! (bool) Setting::get('notify_souq_approval_enabled')) {
            Log::info('Souq approval notification suppressed (disabled in settings)', [
                'listing_id' => $event->listing->id,
            ]);

            return;
        }

        $owner = $event->listing->owner;
        $owner->notify(new BusinessApprovedNotification(
            $event->listing->business_name,
            $event->monthlyFee,
        ));

        app(PushNotificationService::class)->send(
            $owner,
            'Listing Approved',
            "Your business listing \"{$event->listing->business_name}\" has been approved.",
            route('souq'),
        );
    }

    protected function businessActivated(BusinessActivated $event): void
    {
        $owner = $event->listing->owner;
        $owner->notify(new BusinessActivatedNotification(
            $event->listing->business_name,
        ));

        app(PushNotificationService::class)->send(
            $owner,
            'Listing Active',
            "Your business listing \"{$event->listing->business_name}\" is now active.",
            route('souq'),
        );
    }

    protected function businessSuspended(BusinessSuspended $event): void
    {
        $owner = $event->listing->owner;
        $owner->notify(new BusinessSuspendedNotification(
            $event->listing->business_name,
            $event->reason,
        ));

        app(PushNotificationService::class)->send(
            $owner,
            'Listing Suspended',
            "Your business listing \"{$event->listing->business_name}\" has been suspended.",
            route('souq'),
        );
    }
}
