<?php

namespace App\Livewire\Membership;

use App\Models\Goal;
use App\Models\Interest;
use App\Models\MemberProfile;
use App\Services\NotificationService;
use App\Services\OnboardingService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class MembershipOnboardingWizard extends Component
{
    public int $step = 1;

    public string $firstName = '';

    public string $lastName = '';

    public string $nickname = '';

    public string $locationCountry = 'Nigeria';

    public string $country = 'Nigeria';

    public string $locationState = '';

    public string $state = '';

    public string $locationInternational = '';

    public string $outsideNigeriaLocation = '';

    public string $ageGroup = '';

    public string $maritalStatus = '';

    public string $phone = '';

    public array $selectedInterests = [];

    public array $selectedGoals = [];

    public string $igUsername = '';

    public string $instagramUsername = '';

    public string $fbUsername = '';

    public string $facebookUsername = '';

    public string $xUsername = '';

    public string $tiktokUsername = '';

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

    protected ?MemberProfile $profile = null;

    protected function onboardingService(): OnboardingService
    {
        return app(OnboardingService::class);
    }

    protected function profile(): MemberProfile
    {
        return $this->profile ??= $this->onboardingService()->resolveForUser(auth()->user());
    }

    public function mount(): void
    {
        $user = auth()->user();
        $this->profile = $this->onboardingService()->resolveForUser($user);

        if (in_array($this->profile()->onboarding_status, ['pending_review', 'approved', 'active'], true)) {
            $this->redirectRoute('membership.pending', navigate: true);

            return;
        }

        $this->step = max(1, (int) ($this->profile()->onboarding_step ?: 1));
        $this->loadStateFromProfile($user);
    }

    public function updated($name): void
    {
        if ($name === 'country') {
            $this->locationCountry = $this->country;
        }

        if ($name === 'state') {
            $this->locationState = $this->state;
        }

        if ($name === 'outsideNigeriaLocation') {
            $this->locationInternational = $this->outsideNigeriaLocation;
        }

        if ($name === 'instagramUsername') {
            $this->igUsername = $this->instagramUsername;
        }

        if ($name === 'facebookUsername') {
            $this->fbUsername = $this->facebookUsername;
        }

        if (! in_array($name, [
            'firstName', 'lastName', 'nickname', 'locationCountry', 'locationState', 'locationInternational',
            'ageGroup', 'maritalStatus', 'phone', 'igUsername', 'fbUsername', 'xUsername', 'tiktokUsername',
            'selectedInterests', 'selectedGoals', 'country', 'state', 'outsideNigeriaLocation', 'instagramUsername', 'facebookUsername',
        ], true)) {
            return;
        }

        $this->autoSave();
    }

    protected function loadStateFromProfile($user): void
    {
        $legacy = $user->profile;

        $this->firstName = $this->profile->first_name ?? $legacy?->first_name ?? '';
        $this->lastName = $this->profile->last_name ?? $legacy?->last_name ?? '';
        $this->nickname = $this->profile->nickname ?? $legacy?->nickname ?? '';
        $this->locationCountry = $this->profile->location_country ?? $legacy?->country ?? 'Nigeria';
        $this->country = $this->locationCountry;
        $this->locationState = $this->profile->location_state ?? $legacy?->state ?? '';
        $this->state = $this->locationState;
        $this->locationInternational = $this->profile->location_international ?? $legacy?->outside_nigeria_location ?? '';
        $this->outsideNigeriaLocation = $this->locationInternational;
        $this->ageGroup = $this->profile->age_group ?? $legacy?->age_group ?? '';
        $this->maritalStatus = $this->profile->marital_status ?? $legacy?->marital_status ?? '';
        $this->phone = $this->profile->phone ?? $legacy?->phone ?? '';
        $this->igUsername = $this->profile->ig_username ?? $legacy?->instagram_username ?? '';
        $this->instagramUsername = $this->igUsername;
        $this->fbUsername = $this->profile->fb_username ?? $legacy?->facebook_username ?? '';
        $this->facebookUsername = $this->fbUsername;
        $this->xUsername = $this->profile->x_username ?? $legacy?->x_username ?? '';
        $this->tiktokUsername = $this->profile->tiktok_username ?? $legacy?->tiktok_username ?? '';

        $user->loadMissing(['interests:id,slug', 'goals:id,slug']);
        $this->selectedInterests = $user->interests->pluck('slug')->all();
        $this->selectedGoals = $user->goals->pluck('slug')->all();
    }

    protected function payload(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'nickname' => $this->nickname,
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
            'interest_ids' => Interest::query()->whereIn('slug', $this->selectedInterests)->pluck('id')->all(),
            'goal_ids' => Goal::query()->whereIn('slug', $this->selectedGoals)->pluck('id')->all(),
        ];
    }

    protected function autoSave(): void
    {
        if (in_array($this->profile()->onboarding_status, ['pending_review', 'approved', 'active'], true)) {
            return;
        }

        $this->onboardingService()->saveProgress(auth()->user(), $this->payload(), $this->step);
    }

    public function toggleInterest(string $slug): void
    {
        if (in_array($slug, $this->selectedInterests, true)) {
            $this->selectedInterests = array_values(array_filter($this->selectedInterests, fn ($selected): bool => $selected !== $slug));
            $this->autoSave();

            return;
        }

        if (count($this->selectedInterests) >= 5) {
            return;
        }

        $this->selectedInterests[] = $slug;
        $this->autoSave();
    }

    public function toggleGoal(string $slug): void
    {
        if (in_array($slug, $this->selectedGoals, true)) {
            $this->selectedGoals = array_values(array_filter($this->selectedGoals, fn ($selected): bool => $selected !== $slug));
            $this->autoSave();

            return;
        }

        $this->selectedGoals[] = $slug;
        $this->autoSave();
    }

    public function nextStep(): void
    {
        $this->validateCurrentStep();
        $this->autoSave();

        if ($this->step < 6) {
            $this->step++;
        }
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function getProgressPercentageProperty(): int
    {
        return (int) round(($this->step / 6) * 100);
    }

    protected function validateCurrentStep(): void
    {
        $rules = match ($this->step) {
            1 => [
                'firstName' => ['required', 'string', 'max:255'],
                'lastName' => ['required', 'string', 'max:255'],
                'nickname' => ['nullable', 'string', 'max:255'],
            ],
            2 => [
                'locationCountry' => ['required', 'string', 'max:255'],
                'locationState' => [Rule::requiredIf(fn () => $this->locationCountry === 'Nigeria'), 'nullable', 'string', 'max:255'],
                'locationInternational' => [Rule::requiredIf(fn () => $this->locationCountry !== 'Nigeria'), 'nullable', 'string', 'max:500'],
                'ageGroup' => ['required', 'string', 'max:50'],
                'maritalStatus' => ['required', 'string', 'max:50'],
                'phone' => ['nullable', 'string', 'max:30'],
            ],
            3 => [
                'selectedInterests' => ['array', 'min:1', 'max:5'],
            ],
            4 => [
                'selectedGoals' => ['array', 'min:1'],
            ],
            5 => [
                'igUsername' => ['nullable', 'string', 'max:255'],
                'fbUsername' => ['nullable', 'string', 'max:255'],
                'xUsername' => ['nullable', 'string', 'max:255'],
                'tiktokUsername' => ['nullable', 'string', 'max:255'],
            ],
            default => [],
        };

        if ($rules !== []) {
            $this->validate($rules);
        }
    }

    public function submit(): void
    {
        $this->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'locationCountry' => ['required', 'string', 'max:255'],
            'locationState' => [Rule::requiredIf(fn () => $this->locationCountry === 'Nigeria'), 'nullable', 'string', 'max:255'],
            'locationInternational' => [Rule::requiredIf(fn () => $this->locationCountry !== 'Nigeria'), 'nullable', 'string', 'max:500'],
            'ageGroup' => ['required', 'string', 'max:50'],
            'maritalStatus' => ['required', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
            'selectedInterests' => ['array', 'min:1', 'max:5'],
            'selectedGoals' => ['array', 'min:1'],
            'igUsername' => ['nullable', 'string', 'max:255'],
            'fbUsername' => ['nullable', 'string', 'max:255'],
            'xUsername' => ['nullable', 'string', 'max:255'],
            'tiktokUsername' => ['nullable', 'string', 'max:255'],
        ]);

        $profile = $this->onboardingService()->submitForReview(auth()->user(), $this->payload());

        app(NotificationService::class)->notifyAdminsAboutSubmission($profile);
        app(NotificationService::class)->notifyApplicantUnderReview($profile);

        session()->flash('membership_submitted', true);

        $this->redirectRoute('membership.pending', navigate: true);
    }

    public function pingAutoSave(): void
    {
        // No-op: used by wire:poll to confirm the component is alive
    }

    public function render()
    {
        return view('livewire.membership.onboarding-wizard', [
            'interests' => Interest::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'goals' => Goal::query()->where('is_active', true)->orderBy('id')->get(),
        ])->layout('layouts.guest-livewire', [
            'title' => 'Onboarding',
        ]);
    }
}
