<?php

namespace App\Livewire\Membership;

use App\Models\Goal;
use App\Models\Interest;
use App\Models\User;
use App\Notifications\OnboardingInvitationNotification;
use App\Services\MembershipSignupService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Component;

class MembershipSignupWizard extends Component
{
    public int $step = 1;

    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public string $referralCode = '';

    public string $locationCountry = 'Nigeria';

    public string $locationState = '';

    public string $locationInternational = '';

    public string $ageGroup = '';

    public string $maritalStatus = '';

    public string $phone = '';

    public string $igUsername = '';

    public string $fbUsername = '';

    public string $xUsername = '';

    public string $tiktokUsername = '';

    public array $selectedInterests = [];

    public array $selectedGoals = [];

    public bool $submitting = false;

    public bool $existingMemberDetected = false;

    public string $existingMemberMessage = '';

    public bool $showResendButton = false;

    public string $existingMemberEmail = '';

    public array $nigerianStates = [
        'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue',
        'Borno', 'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu',
        'FCT (Abuja)', 'Gombe', 'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina',
        'Kebbi', 'Kogi', 'Kwara', 'Lagos', 'Nasarawa', 'Niger', 'Ogun', 'Ondo',
        'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara',
    ];

    public array $ageGroups = [
        'under_18' => 'Under 18',
        '18_24' => '18 - 24',
        '25_34' => '25 - 34',
        '35_44' => '35 - 44',
        '45_54' => '45 - 54',
        '55_above' => '55+',
    ];

    public array $maritalStatuses = [
        'single' => 'Single',
        'married' => 'Married',
        'divorced' => 'Divorced',
        'widowed' => 'Widowed',
    ];

    public function boot(): void
    {
        if ($this->step > 5) {
            $this->step = 1;
        }
    }

    public function mount(?string $ref = null): void
    {
        $this->referralCode = $ref ?? request()->query('ref', '');

        if (auth()->check()) {
            $user = auth()->user();
            $profile = $user->memberProfile;

            if ($profile && in_array($profile->onboarding_status, ['active', 'member'], true)) {
                $this->redirect(route('home'));

                return;
            }
        }
    }

    public function toggleInterest(string $slug): void
    {
        if (in_array($slug, $this->selectedInterests, true)) {
            $this->selectedInterests = array_values(array_filter($this->selectedInterests, fn ($s): bool => $s !== $slug));
        } elseif (count($this->selectedInterests) < 5) {
            $this->selectedInterests[] = $slug;
        }
    }

    public function toggleGoal(string $slug): void
    {
        if (in_array($slug, $this->selectedGoals, true)) {
            $this->selectedGoals = array_values(array_filter($this->selectedGoals, fn ($s): bool => $s !== $slug));
        } else {
            $this->selectedGoals[] = $slug;
        }
    }

    public function nextStep(): void
    {
        // Check for existing member BEFORE step 1 validation
        // so the detection can intercept before the unique rule rejects it
        if ($this->step === 1) {
            $email = strtolower(trim($this->email));
            $existingUser = User::query()->whereEmail($email)->first();

            if ($existingUser) {
                $this->existingMemberEmail = $email;

                if ($existingUser->status === 'pending_onboarding'
                    || ($existingUser->memberProfile && $existingUser->memberProfile->onboarding_status === 'pending_onboarding')) {
                    $this->existingMemberMessage = "You're already a member — check your inbox for your invitation.";
                    $this->showResendButton = true;
                } else {
                    $this->existingMemberMessage = 'An account with this email already exists. Please log in or reset your password.';
                    $this->showResendButton = false;
                }

                $this->existingMemberDetected = true;

                return;
            }
        }

        $this->validateCurrentStep();

        if ($this->step < 5) {
            $this->step++;
        }
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function clearExistingMember(): void
    {
        $this->existingMemberDetected = false;
        $this->existingMemberMessage = '';
        $this->showResendButton = false;
        $this->existingMemberEmail = '';
    }

    public function resendInvitation(): void
    {
        $rateKey = 'resend-invite:'.$this->existingMemberEmail;

        if (RateLimiter::tooManyAttempts($rateKey, 1)) {
            $seconds = RateLimiter::availableIn($rateKey);
            $this->addError('existingMember', "Please wait {$seconds} seconds before requesting another invitation.");

            return;
        }

        RateLimiter::hit($rateKey, 3600);

        $user = User::query()->whereEmail($this->existingMemberEmail)->first();

        if (! $user) {
            $this->addError('existingMember', 'User not found.');

            return;
        }

        try {
            $token = Password::broker('onboarding')->createToken($user);
            $membershipId = $user->member_id ?? $user->memberProfile?->membership_id ?? 'N/A';
            Notification::send($user, new OnboardingInvitationNotification($token, $membershipId));
            $user->update(['invited_at' => now()]);

            $this->existingMemberMessage = 'Invitation resent! Check your inbox for the link.';
            $this->showResendButton = false;
        } catch (\Throwable $e) {
            Log::error('MembershipSignupWizard: resend invitation failed', [
                'email' => $this->existingMemberEmail,
                'error' => $e->getMessage(),
            ]);
            $this->addError('existingMember', 'Failed to resend invitation. Please try again.');
        }
    }

    public function submit(MembershipSignupService $service)
    {
        if ($this->submitting) {
            return;
        }

        $key = 'signup:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('submit', "Too many signup attempts. Please try again in {$seconds} seconds.");

            return;
        }

        RateLimiter::hit($key, 3600);

        $this->validate($this->fullValidationRules());

        $this->submitting = true;

        try {
            $profile = $service->register(
                firstName: $this->firstName,
                lastName: $this->lastName,
                email: $this->email,
                password: Hash::make($this->password),
                referralCode: $this->referralCode ?: null,
                data: $this->dataPayload(),
                passwordIsHashed: true,
            );

            $user = User::find($profile->user_id);

            session()->regenerate();

            Auth::login($user);
        } catch (\Throwable $e) {
            Log::error('MembershipSignupWizard: submit failed', [
                'error' => $e->getMessage(),
            ]);
            $this->submitting = false;
            $this->addError('submit', 'Submission failed. Please try again.');

            return;
        }

        $this->submitting = false;

        $this->redirect(route('home'));
    }

    public function getProgressPercentageProperty(): int
    {
        return (int) round(($this->step / 5) * 100);
    }

    protected function dataPayload(): array
    {
        return [
            'nickname' => '',
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'location_country' => $this->locationCountry,
            'location_state' => $this->locationState,
            'location_international' => $this->locationInternational,
            'age_group' => $this->ageGroup,
            'marital_status' => $this->maritalStatus,
            'phone' => $this->phone,
            'ig_username' => $this->igUsername,
            'fb_username' => $this->fbUsername,
            'x_username' => $this->xUsername,
            'tiktok_username' => $this->tiktokUsername,
            'selected_interests' => $this->selectedInterests,
            'selected_goals' => $this->selectedGoals,
        ];
    }

    protected function fullValidationRules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore(auth()->id())->where(fn ($query) => $query->whereRaw('LOWER(email) = LOWER(?)', [$this->email]))],
            'password' => ['required', 'string', PasswordRule::min(8)->mixedCase()->numbers()->symbols()->uncompromised(), 'confirmed:passwordConfirmation'],
            'referralCode' => ['nullable', 'string', 'max:8', function ($attribute, $value, $fail) {
                if ($value && ! User::where('referral_code', $value)->exists()) {
                    $fail('The referral code is invalid.');
                }
            }],
            'locationState' => [Rule::requiredIf(fn () => $this->locationCountry === 'Nigeria'), 'nullable', 'string', 'max:255'],
            'locationInternational' => [Rule::requiredIf(fn () => $this->locationCountry !== 'Nigeria'), 'nullable', 'string', 'max:500'],
            'ageGroup' => ['required', 'string', 'max:50'],
            'maritalStatus' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:30'],
            'selectedInterests' => ['required', 'array', 'min:1', 'max:5'],
            'selectedGoals' => ['required', 'array', 'min:1'],
        ];
    }

    protected function validateCurrentStep(): void
    {
        $rules = match ($this->step) {
            1 => [
                'firstName' => ['required', 'string', 'max:255'],
                'lastName' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore(auth()->id())->where(fn ($query) => $query->whereRaw('LOWER(email) = LOWER(?)', [$this->email]))],
                'password' => ['required', 'string', PasswordRule::min(8)->mixedCase()->numbers()->symbols()->uncompromised(), 'confirmed:passwordConfirmation'],
            ],
            2 => [
                'locationCountry' => ['required', 'string', 'max:255'],
                'locationState' => [Rule::requiredIf(fn () => $this->locationCountry === 'Nigeria'), 'nullable', 'string', 'max:255'],
                'locationInternational' => [Rule::requiredIf(fn () => $this->locationCountry !== 'Nigeria'), 'nullable', 'string', 'max:500'],
                'ageGroup' => ['required', 'string', 'max:50'],
                'maritalStatus' => ['required', 'string', 'max:50'],
            ],
            3 => [
                'phone' => ['required', 'string', 'max:30'],
            ],
            4 => [],
            5 => [
                'selectedInterests' => ['required', 'array', 'min:1', 'max:5'],
                'selectedGoals' => ['required', 'array', 'min:1'],
            ],
            default => [],
        };

        if ($rules !== []) {
            $this->validate($rules);
        }
    }

    public function render()
    {
        $interests = Interest::query()->where('is_active', true)->orderBy('sort_order')->get();
        $goals = Goal::query()->where('is_active', true)->orderBy('id')->get();

        return view('livewire.membership.signup-wizard', [
            'interests' => $interests,
            'goals' => $goals,
        ])->layout('layouts.guest-livewire', [
            'title' => 'Signup',
        ]);
    }
}
