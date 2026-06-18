<?php

namespace App\Services;

use App\Models\MemberProfile;
use App\Models\MembershipApplicationDraft;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class OnboardingService
{
    public function resolveForUser(User $user): MemberProfile
    {
        return DB::transaction(function () use ($user): MemberProfile {
            $profile = $user->memberProfile()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'location_country' => 'Nigeria',
                    'onboarding_step' => 1,
                    'onboarding_status' => 'draft',
                ],
            );

            if ($user->status === 'draft') {
                $user->forceFill(['status' => 'onboarding'])->saveQuietly();
            }

            return $profile;
        });
    }

    public function saveProgress(User $user, array $data, int $step): MemberProfile
    {
        $profile = $this->resolveForUser($user);

        if (in_array($profile->onboarding_status, ['pending_review', 'approved', 'active'], true)) {
            return $profile;
        }

        DB::transaction(function () use ($user, $profile, $data, $step): void {
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

            $profile->onboarding_step = $step;
            $profile->onboarding_status = 'in_progress';
            $profile->save();

            $user->forceFill(['status' => 'onboarding'])->saveQuietly();

            if (array_key_exists('interest_ids', $data)) {
                $user->interests()->sync($data['interest_ids'] ?? []);
            }

            if (array_key_exists('goal_ids', $data)) {
                $user->goals()->sync($data['goal_ids'] ?? []);
            }

            MembershipApplicationDraft::query()->updateOrCreate(
                ['user_id' => $user->id, 'submitted_at' => null],
                [
                    'current_step' => $step,
                    'data' => $this->draftPayload($data),
                ],
            );

            $this->syncLegacyProfile($user, $data, false);
        });

        return $profile->refresh();
    }

    public function submitForReview(User $user, array $data): MemberProfile
    {
        $profile = $this->saveProgress($user, $data, 6);

        DB::transaction(function () use ($user, $profile, $data): void {
            $profile->fill([
                'onboarding_status' => 'pending_review',
                'submitted_at' => now(),
                'onboarding_step' => 6,
            ]);
            $profile->save();

            $user->forceFill(['status' => 'pending_review'])->saveQuietly();

            MembershipApplicationDraft::query()->updateOrCreate(
                ['user_id' => $user->id, 'submitted_at' => null],
                [
                    'current_step' => 6,
                    'data' => $this->draftPayload($data),
                    'submitted_at' => now(),
                ],
            );

            $this->syncLegacyProfile($user, $data, true);
        });

        return $profile->refresh();
    }

    protected function syncLegacyProfile(User $user, array $data, bool $submitted): void
    {
        if (! $user->profile) {
            return;
        }

        $legacy = $user->profile;
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
            'membership_status' => $submitted ? 'submitted' : 'draft',
            'application_submitted_at' => $submitted ? now() : $legacy->application_submitted_at,
        ], fn ($value) => $value !== null));

        if (array_key_exists('selected_goals', $data)) {
            $legacy->goals = $data['selected_goals'];
        }

        $legacy->save();
    }

    protected function draftPayload(array $data): array
    {
        return [
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'nickname' => $data['nickname'] ?? null,
            'country' => $data['location_country'] ?? null,
            'state' => $data['location_state'] ?? null,
            'outside_nigeria_location' => $data['location_international'] ?? null,
            'age_group' => $data['age_group'] ?? null,
            'marital_status' => $data['marital_status'] ?? null,
            'phone' => $data['phone'] ?? null,
            'selected_interests' => $data['selected_interests'] ?? [],
            'selected_goals' => $data['selected_goals'] ?? [],
            'instagram_username' => $data['ig_username'] ?? null,
            'facebook_username' => $data['fb_username'] ?? null,
            'x_username' => $data['x_username'] ?? null,
            'tiktok_username' => $data['tiktok_username'] ?? null,
        ];
    }
}
