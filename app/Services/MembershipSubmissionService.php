<?php

namespace App\Services;

use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MembershipSubmissionService
{
    public function __construct(
        protected OnboardingService $onboardingService,
        protected NotificationService $notificationService,
    ) {}

    /**
     * Submit a membership application for review.
     *
     * Encapsulates the full submission lifecycle:
     * 1. Validate state transition
     * 2. Persist form data and transition status
     * 3. Dispatch notifications (queued, never blocks submit)
     * 4. Log every stage
     */
    public function submit(User $user, array $data): MemberProfile
    {
        Log::info('Membership submission started', [
            'user_id' => $user->id,
        ]);

        $this->validateTransition($user);

        $profile = $this->onboardingService->submitForReview($user, $data);

        Log::info('Membership submission persisted', [
            'user_id' => $user->id,
            'profile_id' => $profile->id,
        ]);

        $this->dispatchNotifications($profile);

        Log::info('Membership submission completed', [
            'user_id' => $user->id,
            'profile_id' => $profile->id,
        ]);

        return $profile;
    }

    protected function validateTransition(User $user): void
    {
        $profile = $user->memberProfile;

        if (! $profile) {
            throw new RuntimeException('No member profile found. Please restart onboarding.');
        }

        $currentStatus = $profile->onboarding_status;

        if (in_array($currentStatus, ['pending_review', 'approved', 'active'], true)) {
            throw new RuntimeException('Application has already been submitted.');
        }

        if (! in_array($currentStatus, ['draft', 'in_progress', null], true)) {
            Log::warning('Unexpected membership submission from non-draft state', [
                'user_id' => $user->id,
                'current_status' => $currentStatus,
            ]);
        }
    }

    protected function dispatchNotifications(MemberProfile $profile): void
    {
        try {
            $this->notificationService->notifyAdminsAboutSubmission($profile);
        } catch (\Throwable $e) {
            report($e);
            Log::error('Failed to notify admins about submission', [
                'profile_id' => $profile->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $this->notificationService->notifyApplicantUnderReview($profile);
        } catch (\Throwable $e) {
            report($e);
            Log::error('Failed to notify applicant about submission', [
                'profile_id' => $profile->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
