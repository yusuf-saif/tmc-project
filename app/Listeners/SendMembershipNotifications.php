<?php

namespace App\Listeners;

use App\Events\MembershipActivated;
use App\Events\MembershipApproved;
use App\Events\MembershipNeedsCorrection;
use App\Events\MembershipRejected;
use App\Events\MembershipSubmitted;
use App\Events\PaymentConfirmed;
use App\Events\PaymentSubmitted;
use App\Models\User;
use App\Notifications\MembershipApplicationSubmitted;
use App\Notifications\MembershipApproved as MembershipApprovedNotification;
use App\Notifications\MembershipNeedsCorrection as MembershipNeedsCorrectionNotification;
use App\Notifications\MembershipPaymentConfirmed as MembershipPaymentConfirmedNotification;
use App\Notifications\MembershipRejected as MembershipRejectedNotification;
use App\Notifications\MembershipUnderReviewNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendMembershipNotifications
{
    public function handle(
        MembershipActivated|MembershipSubmitted|MembershipApproved|MembershipRejected|MembershipNeedsCorrection|PaymentSubmitted|PaymentConfirmed $event,
    ): void {
        try {
            match (true) {
                $event instanceof MembershipActivated => $this->sendActivatedNotification($event),
                $event instanceof MembershipSubmitted => $this->sendSubmittedNotifications($event),
                $event instanceof MembershipApproved => $this->sendApprovedNotification($event),
                $event instanceof MembershipRejected => $this->sendRejectedNotification($event),
                $event instanceof MembershipNeedsCorrection => $this->sendNeedsCorrectionNotification($event),
                $event instanceof PaymentSubmitted => $this->sendPaymentSubmittedNotifications($event),
                $event instanceof PaymentConfirmed => $this->sendPaymentConfirmedNotification($event),
            };
        } catch (\Throwable $e) {
            Log::error('Failed to send membership notification', [
                'event_class' => $event::class,
                'profile_id' => $event->profile->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function sendActivatedNotification(MembershipActivated $event): void
    {
        $user = $event->user instanceof User ? $event->user : User::find($event->user);
        if (! $user) {
            return;
        }

        $profile = $user->memberProfile;

        if ($profile && $profile->email_sent_at !== null) {
            Log::info('SendMembershipNotifications: email already sent, skipping', [
                'user_id' => $user->id,
            ]);

            return;
        }

        $user->notify(new MembershipPaymentConfirmedNotification(
            $event->membershipId,
        ));

        if ($profile) {
            $profile->forceFill(['email_sent_at' => now()])->saveQuietly();
        }
    }

    protected function sendSubmittedNotifications(MembershipSubmitted $event): void
    {
        $admins = User::query()->role(['super_admin', 'admin', 'moderator'])->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new MembershipApplicationSubmitted($event->actor));
        }

        $event->actor->notify(new MembershipUnderReviewNotification($event->profile));
    }

    protected function sendApprovedNotification(MembershipApproved $event): void
    {
        $event->profile->user->notify(new MembershipApprovedNotification(
            $event->profile->user,
            $event->membershipId,
            $event->membershipType,
        ));
    }

    protected function sendRejectedNotification(MembershipRejected $event): void
    {
        $event->profile->user->notify(new MembershipRejectedNotification($event->reason));
    }

    protected function sendNeedsCorrectionNotification(MembershipNeedsCorrection $event): void
    {
        $event->profile->user->notify(new MembershipNeedsCorrectionNotification($event->notes));
    }

    protected function sendPaymentSubmittedNotifications(PaymentSubmitted $event): void
    {
        $admins = User::query()->role(['super_admin', 'admin', 'moderator'])->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new MembershipApplicationSubmitted($event->actor));
        }
    }

    protected function sendPaymentConfirmedNotification(PaymentConfirmed $event): void
    {
        $event->profile->user->notify(new MembershipPaymentConfirmedNotification(
            $event->profile->membership_id ?? 'N/A',
        ));
    }
}
