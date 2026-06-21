<?php

namespace App\Livewire\Membership;

use App\Models\Goal;
use App\Models\Interest;
use App\Services\MembershipApplicationService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class MembershipOnboardingWizard extends Component
{
    public int $step = 1;

    public bool $submitting = false;

    public string $firstName = '';

    public string $lastName = '';

    public string $nickname = '';

    public string $locationCountry = 'Nigeria';

    public string $locationState = '';

    public string $locationInternational = '';

    public string $ageGroup = '';

    public string $maritalStatus = '';

    public string $phone = '';

    public array $selectedInterests = [];

    public array $selectedGoals = [];

    public string $igUsername = '';

    public string $fbUsername = '';

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

    
    public $interestsList;

    public $goalsList;

    public function mount(MembershipApplicationService $service): void
    {
        $user = auth()->user();

        if (! $user) {
            $this->redirectRoute('login');

            return;
        }

        $result = $service->loadOrCreateDraft($user);

        if (in_array($result['profile']->onboarding_status, ['pending_review', 'submitted', 'approved', 'active'], true)) {
            $this->redirectRoute('membership.pending', navigate: true);

            return;
        }

        $this->step = $result['step'];

        $draft = $result['draft'];
        $profile = $result['profile'];
        $legacy = $user->profile;

        $this->firstName = $draft['first_name'] ?? $profile?->first_name ?? $legacy?->first_name ?? '';
        $this->lastName = $draft['last_name'] ?? $profile?->last_name ?? $legacy?->last_name ?? '';
        $this->nickname = $draft['nickname'] ?? $profile?->nickname ?? $legacy?->nickname ?? '';
        $this->locationCountry = $draft['location_country'] ?? $draft['country'] ?? $profile?->location_country ?? $legacy?->country ?? 'Nigeria';
        $this->locationState = $draft['location_state'] ?? $draft['state'] ?? $profile?->location_state ?? $legacy?->state ?? '';
        $this->locationInternational = $draft['location_international'] ?? $draft['outside_nigeria_location'] ?? $profile?->location_international ?? $legacy?->outside_nigeria_location ?? '';
        $this->ageGroup = $draft['age_group'] ?? $profile?->age_group ?? $legacy?->age_group ?? '';
        $this->maritalStatus = $draft['marital_status'] ?? $profile?->marital_status ?? $legacy?->marital_status ?? '';
        $this->phone = $draft['phone'] ?? $profile?->phone ?? $legacy?->phone ?? '';
        $this->igUsername = $draft['ig_username'] ?? $draft['instagram_username'] ?? $profile?->ig_username ?? $legacy?->instagram_username ?? '';
        $this->fbUsername = $draft['fb_username'] ?? $draft['facebook_username'] ?? $profile?->fb_username ?? $legacy?->facebook_username ?? '';
        $this->xUsername = $draft['x_username'] ?? $profile?->x_username ?? $legacy?->x_username ?? '';
        $this->tiktokUsername = $draft['tiktok_username'] ?? $profile?->tiktok_username ?? $legacy?->tiktok_username ?? '';

        $this->selectedInterests = $result['interests'];
        $this->selectedGoals = $result['goals'];

        $this->interestsList = Interest::query()->where('is_active', true)->orderBy('sort_order')->get();
        $this->goalsList = Goal::query()->where('is_active', true)->orderBy('id')->get();
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

    public function nextStep(MembershipApplicationService $service): void
    {
        $this->validateCurrentStep();

        if ($this->step < 6) {
            $this->step++;
        }

        $service->saveStep(auth()->user(), $this->payload(), $this->step);
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function submit(MembershipApplicationService $service): void
    {
        $this->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'locationCountry' => ['required', 'string', 'max:255'],
            'locationState' => [Rule::requiredIf(fn () => $this->locationCountry === 'Nigeria'), 'nullable', 'string', 'max:255'],
            'locationInternational' => [Rule::requiredIf(fn () => $this->locationCountry !== 'Nigeria'), 'nullable', 'string', 'max:500'],
            'ageGroup' => ['required', 'string', 'max:50'],
            'maritalStatus' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:30'],
            'selectedInterests' => ['array', 'min:1', 'max:5'],
            'selectedGoals' => ['array', 'min:1'],
            'igUsername' => ['nullable', 'string', 'max:255'],
            'fbUsername' => ['nullable', 'string', 'max:255'],
            'xUsername' => ['nullable', 'string', 'max:255'],
            'tiktokUsername' => ['nullable', 'string', 'max:255'],
        ]);

        $user = auth()->user();

        if (! $user) {
            session()->flash('error', 'Authentication expired. Please log in again.');

            return;
        }

        $this->submitting = true;

        try {
            $profile = $service->submit($user, $this->payload());
            $service->dispatchSubmittedEvent($profile, $user);
        } catch (\Throwable $e) {
            report($e);
            $this->submitting = false;
            session()->flash('error', 'Submission failed. Please try again.');

            return;
        }

        session()->flash('membership_submitted', true);

        $this->redirectRoute('membership.pending', navigate: true);
    }

    public function getProgressPercentageProperty(): int
    {
        return (int) round(($this->step / 6) * 100);
    }

    public function pingAutoSave(): void
    {
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
        ];
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
            ],
            3 => [
                'phone' => ['required', 'string', 'max:30'],
            ],
            4 => [
                'igUsername' => ['nullable', 'string', 'max:255'],
                'fbUsername' => ['nullable', 'string', 'max:255'],
                'xUsername' => ['nullable', 'string', 'max:255'],
                'tiktokUsername' => ['nullable', 'string', 'max:255'],
            ],
            5 => [
                'selectedInterests' => ['array', 'min:1', 'max:5'],
                'selectedGoals' => ['array', 'min:1'],
            ],
            default => [],
        };

        if ($rules !== []) {
            $this->validate($rules);
        }
    }

    public function render()
    {
        return view('livewire.membership.onboarding-wizard', [
            'interests' => $this->interestsList ?? Interest::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'goals' => $this->goalsList ?? Goal::query()->where('is_active', true)->orderBy('id')->get(),
        ])->layout('layouts.guest-livewire', [
            'title' => 'Onboarding',
        ]);
    }
}
