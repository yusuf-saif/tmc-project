<?php

namespace App\Services;

use App\Events\MembershipSubmitted;
use App\Models\Goal;
use App\Models\Interest;
use App\Models\MemberProfile;
use App\Models\MembershipApplicationDraft;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MembershipApplicationService
{
    public function __construct(
        protected MembershipStateService $stateService,
    ) {}

    public function loadOrCreateDraft(User $user): array
    {
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

        $draft = MembershipApplicationDraft::query()
            ->where('user_id', $user->id)
            ->whereNull('submitted_at')
            ->latest('updated_at')
            ->first();

        $step = $profile->onboarding_step ?? 1;
        $draftData = [];

        if ($draft) {
            $step = $draft->current_step ?? $step;
            $draftData = $draft->data ?? [];
        }

        $user->loadMissing(['interests:id,slug', 'goals:id,slug']);

        return [
            'step' => max(1, (int) $step),
            'profile' => $profile,
            'draft' => $draftData,
            'interests' => $user->interests->pluck('slug')->all(),
            'goals' => $user->goals->pluck('slug')->all(),
        ];
    }

    public function saveStep(User $user, array $data, int $step): void
    {
        $profile = $user->memberProfile;

        if (! $profile) {
            return;
        }

        if (in_array($profile->onboarding_status, ['pending_review', 'submitted', 'approved', 'approved_pending_payment', 'payment_submitted', 'active'], true)) {
            return;
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

            if ($user->status === 'draft') {
                $user->forceFill(['status' => 'onboarding'])->saveQuietly();
            }

            $interestSlugs = $data['selected_interests'] ?? [];
            $goalSlugs = $data['selected_goals'] ?? [];

            if ($interestSlugs !== []) {
                $interestIds = Interest::query()->whereIn('slug', $interestSlugs)->pluck('id')->all();
                $user->interests()->sync($interestIds);
            }

            if ($goalSlugs !== []) {
                $goalIds = Goal::query()->whereIn('slug', $goalSlugs)->pluck('id')->all();
                $user->goals()->sync($goalIds);
            }

            MembershipApplicationDraft::query()->updateOrCreate(
                ['user_id' => $user->id, 'submitted_at' => null],
                [
                    'current_step' => $step,
                    'data' => $this->buildDraftData($data),
                ],
            );
        });
    }

    public function submit(User $user, array $data): MemberProfile
    {
        $profile = $user->memberProfile;

        if (! $profile) {
            throw new RuntimeException('No member profile found. Please restart onboarding.');
        }

        if (in_array($profile->onboarding_status, ['pending_review', 'approved_pending_payment', 'payment_submitted', 'active'], true)) {
            throw new RuntimeException('Application has already been submitted.');
        }

        return DB::transaction(function () use ($user, $profile, $data): MemberProfile {
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
            $profile->save();

            $this->stateService->transition($profile, 'pending_review', $user);

            $interestSlugs = $data['selected_interests'] ?? [];
            $goalSlugs = $data['selected_goals'] ?? [];

            $interestIds = Interest::query()->whereIn('slug', $interestSlugs)->pluck('id')->all();
            $goalIds = Goal::query()->whereIn('slug', $goalSlugs)->pluck('id')->all();

            $user->interests()->sync($interestIds);
            $user->goals()->sync($goalIds);

            MembershipApplicationDraft::query()
                ->where('user_id', $user->id)
                ->whereNull('submitted_at')
                ->update(['submitted_at' => now()]);

            $this->syncLegacyProfile($user, $data);

            return $profile->fresh();
        });
    }

    public function dispatchSubmittedEvent(MemberProfile $profile, User $user): void
    {
        MembershipSubmitted::dispatch($profile, $user);
    }

    protected function syncLegacyProfile(User $user, array $data): void
    {
        $legacy = $user->profile;

        if (! $legacy) {
            return;
        }

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
    }

    protected function buildDraftData(array $data): array
    {
        return [
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'nickname' => $data['nickname'] ?? null,
            'location_country' => $data['location_country'] ?? null,
            'location_state' => $data['location_state'] ?? null,
            'location_international' => $data['location_international'] ?? null,
            'age_group' => $data['age_group'] ?? null,
            'marital_status' => $data['marital_status'] ?? null,
            'phone' => $data['phone'] ?? null,
            'ig_username' => $data['ig_username'] ?? null,
            'fb_username' => $data['fb_username'] ?? null,
            'x_username' => $data['x_username'] ?? null,
            'tiktok_username' => $data['tiktok_username'] ?? null,
            'selected_interests' => $data['selected_interests'] ?? [],
            'selected_goals' => $data['selected_goals'] ?? [],
        ];
    }
}
