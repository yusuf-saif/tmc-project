<?php

namespace App\Services;

use App\Jobs\MembershipProcessingJob;
use App\Models\Goal;
use App\Models\Interest;
use App\Models\MemberProfile;
use App\Models\MembershipApplicationDraft;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class MembershipRegistrationService
{
    public function __construct(
        protected MembershipStateService $stateService,
    ) {}

    public function register(User $user, array $data): array
    {
        Log::debug('MembershipRegistrationService: step 1 — validation passed', ['user_id' => $user->id]);

        $profile = $this->saveProfile($user, $data);

        Log::debug('MembershipRegistrationService: step 2 — profile saved', ['profile_id' => $profile->id]);

        $this->ensureDraftSubmitted($user, $data);

        Log::debug('MembershipRegistrationService: step 3 — draft saved');

        $this->transitionToPendingReview($profile, $user);

        Log::debug('MembershipRegistrationService: step 4 — state transitioned');

        $this->dispatchProcessingJob($user, $data, $profile);

        Log::debug('MembershipRegistrationService: step 5 — job dispatched, returning');

        return [
            'profile' => $profile->fresh(),
            'queued' => true,
        ];
    }

    protected function saveProfile(User $user, array $data): MemberProfile
    {
        $profile = $user->memberProfile()->firstOrCreate(
            ['user_id' => $user->id],
            ['onboarding_step' => 6, 'onboarding_status' => 'pending_review'],
        );

        $profile->fill(Arr::only($data, [
            'first_name',
            'last_name',
            'nickname',
            'location_country',
            'location_state',
            'location_international',
            'age_group',
            'marital_status',
            'phone',
            'ig_username',
            'fb_username',
            'x_username',
            'tiktok_username',
        ]));

        $profile->onboarding_step = 6;
        $profile->submitted_at = now();

        try {
            $profile->save();
        } catch (\Throwable $e) {
            Log::error('MembershipRegistrationService: failed to save profile', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $profile->fresh();
    }

    protected function ensureDraftSubmitted(User $user, array $data): void
    {
        try {
            MembershipApplicationDraft::query()
                ->where('user_id', $user->id)
                ->whereNull('submitted_at')
                ->update(['submitted_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning('MembershipRegistrationService: draft submission failed (non-blocking)', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function transitionToPendingReview(MemberProfile $profile, User $user): void
    {
        try {
            $this->stateService->transition($profile, 'pending_review', $user);
        } catch (\Throwable $e) {
            Log::error('MembershipRegistrationService: state transition failed', [
                'profile_id' => $profile->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function dispatchProcessingJob(User $user, array $data, MemberProfile $profile): void
    {
        try {
            MembershipProcessingJob::dispatch($user->id, $data, $profile->id)
                ->onQueue('membership');
        } catch (\Throwable $e) {
            Log::error('MembershipRegistrationService: failed to dispatch job', [
                'user_id' => $user->id,
                'profile_id' => $profile->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
