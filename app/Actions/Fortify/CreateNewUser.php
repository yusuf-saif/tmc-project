<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Models\MemberProfile;
use App\Models\UserProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Spatie\Permission\Models\Role;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
            'ref' => ['nullable', 'string', 'size:8', Rule::exists('users', 'referral_code')],
        ])->validate();

        return DB::transaction(function () use ($input) {
            $referredBy = null;

            if (! empty($input['ref']) && Schema::hasColumn('users', 'referral_code')) {
                $referredBy = User::query()->where('referral_code', $input['ref'])->value('id');
            }

            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
                'status' => 'draft',
                'referral_code' => $this->generateReferralCode(),
                'referred_by' => $referredBy,
            ]);

            // Ensure 'member' role exists (robust in environments where seeders weren't run)
            if (class_exists(Role::class)) {
                Role::query()->firstOrCreate(
                    ['name' => 'member', 'guard_name' => 'web'],
                    []
                );
            }
            $user->assignRole('member');

            if (Schema::hasTable('user_profiles')) {
                UserProfile::create([
                    'user_id' => $user->id,
                    'display_name' => $user->name,
                ]);
            }

            if (Schema::hasTable('member_profiles')) {
                MemberProfile::create([
                    'user_id' => $user->id,
                    'location_country' => 'Nigeria',
                    'onboarding_step' => 1,
                    'onboarding_status' => 'draft',
                ]);
            }

            return $user;
        });
    }

    protected function generateReferralCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (User::query()->where('referral_code', $code)->exists());

        return $code;
    }
}
