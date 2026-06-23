<?php

namespace App\Livewire\Membership;

use App\Models\Goal;
use App\Models\Interest;
use App\Models\MembershipOnboardingDraft;
use App\Models\Setting;
use App\Services\MembershipSignupService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;

class MembershipSignupWizard extends Component
{
    public string $draftUuid = '';

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

    public string $preferredBillingCycle = 'monthly';

    public array $selectedInterests = [];

    public array $selectedGoals = [];

    public bool $submitting = false;

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

    public array $billingOptions = [];

    public function mount(?string $ref = null, ?string $draft = null)
    {
        $this->referralCode = $ref ?? request()->query('ref', '');

        if (auth()->check()) {
            $user = auth()->user();
            $profile = $user->memberProfile;

            if ($profile && in_array($profile->onboarding_status, ['pending_review', 'submitted', 'under_review', 'approved_pending_payment', 'payment_submitted', 'approved', 'active'], true)) {
                $this->redirect(route('membership.pending'));

                return;
            }
        }

        $draftParam = $draft ?? request()->query('draft');

        if ($draftParam) {
            $draftModel = MembershipOnboardingDraft::findOrFail($draftParam);
            $this->loadFromDraft($draftModel);
            $this->loadBillingOptions();

            return;
        }

        $draftModel = MembershipOnboardingDraft::create([
            'payload' => [],
            'step' => 1,
            'status' => 'draft',
            'referral_code' => $this->referralCode ?: null,
        ]);

        $this->redirect(route('membership.signup', array_filter([
            'draft' => $draftModel->id,
            'ref' => $this->referralCode ?: null,
        ])));
    }

    protected function loadFromDraft(MembershipOnboardingDraft $draft): void
    {
        $this->draftUuid = $draft->id;
        $this->step = $draft->step;
        $this->referralCode = $draft->referral_code ?? $this->referralCode;

        $payload = $draft->payload;
        $this->firstName = $payload['first_name'] ?? '';
        $this->lastName = $payload['last_name'] ?? '';
        $this->email = $payload['email'] ?? '';
        $this->locationCountry = $payload['location_country'] ?? 'Nigeria';
        $this->locationState = $payload['location_state'] ?? '';
        $this->locationInternational = $payload['location_international'] ?? '';
        $this->ageGroup = $payload['age_group'] ?? '';
        $this->maritalStatus = $payload['marital_status'] ?? '';
        $this->phone = $payload['phone'] ?? '';
        $this->igUsername = $payload['ig_username'] ?? '';
        $this->fbUsername = $payload['fb_username'] ?? '';
        $this->xUsername = $payload['x_username'] ?? '';
        $this->tiktokUsername = $payload['tiktok_username'] ?? '';
        $this->preferredBillingCycle = $payload['preferred_billing_cycle'] ?? 'monthly';
        $this->selectedInterests = $payload['selected_interests'] ?? [];
        $this->selectedGoals = $payload['selected_goals'] ?? [];
    }

    protected function loadBillingOptions(): void
    {
        $this->billingOptions = [
            'monthly' => [
                'label' => 'Monthly',
                'price' => (int) Setting::getValue('membership_fee_monthly', '5000'),
                'interval' => 'per month',
            ],
            'quarterly' => [
                'label' => 'Quarterly',
                'price' => (int) Setting::getValue('membership_fee_quarterly', '12000'),
                'interval' => 'per quarter',
            ],
            'yearly' => [
                'label' => 'Yearly',
                'price' => (int) Setting::getValue('membership_fee_yearly', '40000'),
                'interval' => 'per year',
            ],
        ];
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
        $this->validateCurrentStep();

        if ($this->step === 1) {
            $this->saveStepToDraft(['password' => Hash::make($this->password)]);
            $this->password = '';
            $this->passwordConfirmation = '';
        } else {
            $this->saveStepToDraft();
        }

        if ($this->step < 6) {
            $this->step++;
        }
    }

    protected function saveStepToDraft(array $extra = []): void
    {
        $draft = MembershipOnboardingDraft::find($this->draftUuid);

        if (! $draft) {
            return;
        }

        $draft->update([
            'step' => $this->step,
            'payload' => array_merge($draft->payload, $this->draftPayload(), $extra),
        ]);
    }

    protected function draftPayload(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
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
            'preferred_billing_cycle' => $this->preferredBillingCycle,
            'selected_interests' => $this->selectedInterests,
            'selected_goals' => $this->selectedGoals,
        ];
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function submit(MembershipSignupService $service)
    {
        if ($this->submitting) {
            return;
        }

        $draft = MembershipOnboardingDraft::find($this->draftUuid);

        if (! $draft || $draft->status === 'submitted') {
            $this->redirect(route('membership.pending'));

            return;
        }

        $this->submitting = true;

        $this->validate($this->fullValidationRules());

        $passwordHash = $draft->payload['password'] ?? '';

        if (empty($passwordHash)) {
            if (! empty($this->password)) {
                $passwordHash = Hash::make($this->password);
            } else {
                $this->submitting = false;
                $this->addError('password', 'Session expired. Please start over from step 1.');

                return;
            }
        }

        try {
            $service->register(
                firstName: $this->firstName,
                lastName: $this->lastName,
                email: $this->email,
                password: $passwordHash,
                referralCode: $this->referralCode ?: null,
                data: $this->payload(),
                passwordIsHashed: true,
            );
        } catch (\Throwable $e) {
            Log::error('MembershipSignupWizard: submit failed', [
                'error' => $e->getMessage(),
            ]);
            $this->submitting = false;
            $this->addError('submit', 'Submission failed. Please try again.');

            return;
        }

        $payload = $draft->payload;
        unset($payload['password']);
        $draft->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'payload' => $payload,
        ]);

        $this->submitting = false;

        $this->redirect(route('membership.pending'));
    }

    public function getProgressPercentageProperty(): int
    {
        return (int) round(($this->step / 6) * 100);
    }

    protected function payload(): array
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
            'preferred_billing_cycle' => $this->preferredBillingCycle,
            'selected_interests' => $this->selectedInterests,
            'selected_goals' => $this->selectedGoals,
        ];
    }

    protected function fullValidationRules(): array
    {
        $hasPassword = false;
        if ($this->draftUuid) {
            $draft = MembershipOnboardingDraft::find($this->draftUuid);
            $hasPassword = $draft && ! empty($draft->payload['password'] ?? '');
        }

        return [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore(auth()->id())],
            'password' => $hasPassword ? ['nullable'] : ['required', 'string', 'min:8', 'confirmed:passwordConfirmation'],
            'locationCountry' => ['required', 'string', 'max:255'],
            'locationState' => [Rule::requiredIf(fn () => $this->locationCountry === 'Nigeria'), 'nullable', 'string', 'max:255'],
            'locationInternational' => [Rule::requiredIf(fn () => $this->locationCountry !== 'Nigeria'), 'nullable', 'string', 'max:500'],
            'ageGroup' => ['required', 'string', 'max:50'],
            'maritalStatus' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:30'],
            'preferredBillingCycle' => ['required', 'string', Rule::in(['monthly', 'quarterly', 'yearly'])],
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
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore(auth()->id())],
                'password' => ['required', 'string', 'min:8', 'confirmed:passwordConfirmation'],
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
                'preferredBillingCycle' => ['required', 'string', Rule::in(['monthly', 'quarterly', 'yearly'])],
            ],
            6 => [
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
