<?php

namespace App\Services;

use App\Jobs\SendBroadcastNotificationJob;
use App\Jobs\SendNewsletterEmailJob;
use App\Models\Broadcast;
use App\Models\InAppAnnouncement;
use App\Models\MemberProfile;
use App\Models\Newsletter;
use App\Models\User;
use App\Notifications\MembershipApplicationSubmitted;
use App\Notifications\MembershipApproved;
use App\Notifications\MembershipNeedsCorrection;
use App\Notifications\MembershipPaymentConfirmed;
use App\Notifications\MembershipRejected;
use App\Notifications\MembershipUnderReviewNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    // ─── Membership Notifications ───────────────────────────────────

    public function notifyAdminsAboutSubmission(MemberProfile $profile): void
    {
        $admins = User::query()->role(['super_admin', 'admin', 'moderator'])->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new MembershipApplicationSubmitted($profile->user));
        }
    }

    public function notifyApplicantUnderReview(MemberProfile $profile): void
    {
        $profile->user->notify(new MembershipUnderReviewNotification($profile));
    }

    public function notifyApplicantApproved(MemberProfile $profile, string $membershipId): void
    {
        $profile->user->notify(new MembershipApproved(
            $profile->user,
            $membershipId,
            $profile->membership_type ?? 'M',
        ));
    }

    public function notifyApplicantRejected(MemberProfile $profile, string $reason): void
    {
        $profile->user->notify(new MembershipRejected($reason));
    }

    public function notifyApplicantNeedsCorrection(MemberProfile $profile, string $notes): void
    {
        $profile->user->notify(new MembershipNeedsCorrection($notes));
    }

    public function notifyApplicantPaymentConfirmed(MemberProfile $profile, string $membershipId): void
    {
        $profile->user->notify(new MembershipPaymentConfirmed($membershipId));
    }

    // ─── In-App Announcements ──────────────────────────────────────

    public function createAnnouncement(array $data): InAppAnnouncement
    {
        return InAppAnnouncement::create([
            'title' => $data['title'],
            'body' => $data['body'],
            'type' => $data['type'] ?? 'info',
            'priority' => $data['priority'] ?? 'medium',
            'start_at' => $data['start_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'dismissible' => $data['dismissible'] ?? true,
            'status' => $data['status'] ?? 'active',
            'created_by' => auth()->id(),
        ]);
    }

    public function getVisibleAnnouncementsForUser(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return InAppAnnouncement::forUser($user)
            ->priorityOrdered()
            ->get();
    }

    public function dismissAnnouncement(User $user, InAppAnnouncement $announcement): void
    {
        if ($announcement->dismissible) {
            $user->dismissedAnnouncements()->syncWithoutDetaching([
                $announcement->id => ['dismissed_at' => now()],
            ]);
        }
    }

    // ─── Broadcast Push Notifications ──────────────────────────────

    public function queueBroadcast(Broadcast $broadcast): void
    {
        if ($broadcast->expires_at && $broadcast->expires_at->isPast()) {
            $broadcast->update(['status' => 'failed']);

            return;
        }

        $broadcast->update(['status' => 'queued']);
        SendBroadcastNotificationJob::dispatch($broadcast);
    }

    // ─── Newsletter Email Campaigns ────────────────────────────────

    public function queueNewsletter(Newsletter $newsletter): void
    {
        $newsletter->update(['status' => 'sending']);
        SendNewsletterEmailJob::dispatch($newsletter);
    }

    // ─── Audience Resolution ───────────────────────────────────────

    public function resolveAudience(string $audienceType, ?array $audienceValue = null): Collection
    {
        $query = User::query()->whereHas('profile', fn ($q) => $q->whereNotNull('onboarding_completed_at'));

        return match ($audienceType) {
            'members' => $query->role('member')->get(),
            'exco' => User::query()->role(['super_admin', 'admin', 'moderator'])->get(),
            'interest' => $query->whereHas('interests', fn ($q) => $q->whereIn('interests.id', $audienceValue ?? []))->distinct()->get(),
            'goal' => $query->whereHas('goals', fn ($q) => $q->whereIn('goals.id', $audienceValue ?? []))->distinct()->get(),
            default => $query->get(),
        };
    }

    public function countAudience(string $audienceType, ?array $audienceValue = null): int
    {
        return $this->resolveAudience($audienceType, $audienceValue)->count();
    }
}
