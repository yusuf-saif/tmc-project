<?php

namespace App\Services;

use App\Events\MembershipSubmitted;
use App\Models\Goal;
use App\Models\Interest;
use App\Models\MemberProfile;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class MembershipSignupService
{
    public function register(
        string $firstName,
        string $lastName,
        string $email,
        string $password,
        ?string $referralCode,
        array $data,
        bool $passwordIsHashed = false,
    ): MemberProfile {
        $result = DB::transaction(function () use ($firstName, $lastName, $email, $password, $referralCode, $data, $passwordIsHashed) {
            $referredBy = null;

            if ($referralCode) {
                $referredBy = User::where('referral_code', $referralCode)->value('id');
            }

            $user = User::create([
                'name' => trim("{$firstName} {$lastName}"),
                'email' => $email,
                'password' => $passwordIsHashed ? $password : Hash::make($password),
                'status' => 'pending_review',
                'referral_code' => $this->generateReferralCode(),
                'referred_by' => $referredBy,
            ]);

            if (class_exists(Role::class)) {
                Role::query()->firstOrCreate(
                    ['name' => 'member', 'guard_name' => 'web'],
                    []
                );
            }
            $user->assignRole('member');

            UserProfile::create([
                'user_id' => $user->id,
                'display_name' => trim("{$firstName} {$lastName}"),
            ]);

            $profile = MemberProfile::create([
                'user_id' => $user->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'nickname' => $data['nickname'] ?? null,
                'location_country' => $data['location_country'] ?? 'Nigeria',
                'location_state' => $data['location_state'] ?? null,
                'location_international' => $data['location_international'] ?? null,
                'age_group' => $data['age_group'] ?? null,
                'marital_status' => $data['marital_status'] ?? null,
                'phone' => $data['phone'] ?? null,
                'ig_username' => $data['ig_username'] ?? null,
                'fb_username' => $data['fb_username'] ?? null,
                'x_username' => $data['x_username'] ?? null,
                'tiktok_username' => $data['tiktok_username'] ?? null,
                'onboarding_status' => 'pending_review',
                'submitted_at' => now(),
                'preferred_billing_cycle' => $data['preferred_billing_cycle'] ?? 'monthly',
            ]);

            $interestSlugs = $data['selected_interests'] ?? [];
            if ($interestSlugs !== []) {
                $interestIds = Interest::whereIn('slug', $interestSlugs)->pluck('id')->all();
                $user->interests()->sync($interestIds);
            }

            $goalSlugs = $data['selected_goals'] ?? [];
            if ($goalSlugs !== []) {
                $goalIds = Goal::whereIn('slug', $goalSlugs)->pluck('id')->all();
                $user->goals()->sync($goalIds);
            }

            return ['user' => $user, 'profile' => $profile];
        });

        $user = $result['user'];
        $profile = $result['profile'];

        Auth::login($user);

        Log::info('MembershipSignupService: user created and pending review', [
            'user_id' => $user->id,
            'profile_id' => $profile->id,
        ]);

        try {
            MembershipSubmitted::dispatch($profile, $user);
        } catch (\Throwable $e) {
            Log::error('MembershipSignupService: failed to dispatch submitted event', [
                'profile_id' => $profile->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $profile;
    }

    protected function generateReferralCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }
}
