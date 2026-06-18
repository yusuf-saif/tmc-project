<?php

namespace App\Services;

use App\Models\MemberProfile;
use App\Models\User;
use App\Notifications\MembershipApproved;
use App\Notifications\MembershipApplicationSubmitted;
use App\Notifications\MembershipRejected;
use App\Notifications\MembershipUnderReviewNotification;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
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
        $profile->user->notify(new MembershipApproved($profile->user, $membershipId, $profile->membership_type ?? 'M'));
    }

    public function notifyApplicantRejected(MemberProfile $profile, string $reason): void
    {
        $profile->user->notify(new MembershipRejected($reason));
    }
}
