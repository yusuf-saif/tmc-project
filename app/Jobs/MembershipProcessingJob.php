<?php

namespace App\Jobs;

use App\Events\MembershipSubmitted;
use App\Models\Goal;
use App\Models\Interest;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MembershipProcessingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    public function __construct(
        protected int $userId,
        protected array $data,
        protected int $profileId,
    ) {
        $this->onQueue('membership');
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        $profile = MemberProfile::find($this->profileId);

        if (! $user || ! $profile) {
            Log::warning('MembershipProcessingJob: user or profile not found', [
                'user_id' => $this->userId,
                'profile_id' => $this->profileId,
            ]);

            return;
        }

        Log::debug('MembershipProcessingJob: step 1 — syncing interests', ['user_id' => $user->id]);
        $this->syncInterestsAndGoals($user);

        Log::debug('MembershipProcessingJob: step 2 — dispatching event', ['user_id' => $user->id]);
        $this->dispatchSubmittedEvent($profile, $user);

        Log::debug('MembershipProcessingJob: complete', ['user_id' => $user->id]);
    }

    protected function syncInterestsAndGoals(User $user): void
    {
        try {
            $interestSlugs = $this->data['selected_interests'] ?? [];
            $goalSlugs = $this->data['selected_goals'] ?? [];

            if ($interestSlugs !== []) {
                $interestIds = Interest::query()->whereIn('slug', $interestSlugs)->pluck('id')->all();
                $user->interests()->sync($interestIds);
            }

            if ($goalSlugs !== []) {
                $goalIds = Goal::query()->whereIn('slug', $goalSlugs)->pluck('id')->all();
                $user->goals()->sync($goalIds);
            }
        } catch (\Throwable $e) {
            Log::error('MembershipProcessingJob: interest/goal sync failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function syncLegacyProfile(User $user): void
    {
        try {
            $legacy = $user->profile;

            if (! $legacy) {
                return;
            }

            $data = $this->data;

            $legacy->fill(array_filter([
                'display_name' => trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? '')) ?: $user->name,
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'nickname' => $data['nickname'] ?? null,
                'country' => $data['location_country'] ?? 'Nigeria',
                'state' => $data['location_state'] ?? null,
                'outside_nigeria_location' => $data['location_international'] ?? null,
                'age_group' => $data['age_group'] ?? null,
                'marital_status' => $data['marital_status'] ?? null,
                'phone' => $data['phone'] ?? null,
                'instagram_username' => $data['ig_username'] ?? null,
                'facebook_username' => $data['fb_username'] ?? null,
                'x_username' => $data['x_username'] ?? null,
                'tiktok_username' => $data['tiktok_username'] ?? null,
                'membership_status' => 'submitted',
                'application_submitted_at' => now(),
            ], fn ($value) => $value !== null));

            $legacy->save();
        } catch (\Throwable $e) {
            Log::error('MembershipProcessingJob: legacy profile sync failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function dispatchSubmittedEvent(MemberProfile $profile, User $user): void
    {
        try {
            MembershipSubmitted::dispatch($profile, $user);
        } catch (\Throwable $e) {
            Log::error('MembershipProcessingJob: event dispatch failed', [
                'profile_id' => $profile->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
