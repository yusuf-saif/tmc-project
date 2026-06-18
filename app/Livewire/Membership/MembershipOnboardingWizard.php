<?php

namespace App\Livewire\Membership;

use App\Models\Goal;
use App\Models\Interest;
use App\Models\MembershipApplicationDraft;
use App\Models\User;
use App\Notifications\MembershipApplicationSubmitted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Livewire\Component;

class MembershipOnboardingWizard extends Component
{
    public int $step = 1;

    public string $firstName = '';

    public string $lastName = '';

    public string $nickname = '';

    public string $country = 'Nigeria';

    public string $state = '';

    public string $outsideNigeriaLocation = '';

    public string $ageGroup = '';

    public string $maritalStatus = '';

    public string $phone = '';

    public array $selectedInterests = [];

    public array $selectedGoals = [];

    public string $instagramUsername = '';

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
        '18_24' => '18 – 24',
        '25_34' => '25 – 34',
        '35_44' => '35 – 44',
        '45_54' => '45 – 54',
        '55_above' => '55+',
    ];

    public array $maritalStatuses = [
        'single' => 'Single',
        'married' => 'Married',
        'divorced' => 'Divorced',
        'widowed' => 'Widowed',
    ];

    public function mount(): void
    {
        $user = auth()->user();
        $profile = $user->profile;

        if ($profile && in_array($profile->membership_status, ['active', 'approved_pending_payment', 'payment_submitted'], true)) {
            $this->redirectRoute('home', navigate: true);

            return;
        }

        $draft = MembershipApplicationDraft::query()
            ->where('user_id', $user->id)
            ->whereNull('submitted_at')
            ->first();

        if ($draft) {
            $this->step = $draft->current_step;
            $this->restoreFromDraft($draft->data);

            return;
        }

        $this->loadExistingProfileData($user);
    }

    protected function loadExistingProfileData($user): void
    {
        $profile = $user->profile;

        if (! $profile) {
            return;
        }

        $this->firstName = $profile->first_name ?? '';
        $this->lastName = $profile->last_name ?? '';
        $this->nickname = $profile->nickname ?? '';
        $this->country = $profile->country ?? 'Nigeria';
        $this->state = $profile->state ?? '';
        $this->outsideNigeriaLocation = $profile->outside_nigeria_location ?? '';
        $this->ageGroup = $profile->age_group ?? '';
        $this->maritalStatus = $profile->marital_status ?? '';
        $this->phone = $profile->phone ?? '';
        $this->instagramUsername = $profile->instagram_username ?? '';
        $this->facebookUsername = $profile->facebook_username ?? '';
        $this->xUsername = $profile->x_username ?? '';
        $this->tiktokUsername = $profile->tiktok_username ?? '';

        $user->loadMissing(['interests:id,slug', 'goals:id,slug']);
        $this->selectedInterests = $user->interests->pluck('slug')->all();
        $this->selectedGoals = $user->goals->pluck('slug')->all();
    }

    protected function restoreFromDraft(array $data): void
    {
        $this->firstName = $data['first_name'] ?? '';
        $this->lastName = $data['last_name'] ?? '';
        $this->nickname = $data['nickname'] ?? '';
        $this->country = $data['country'] ?? 'Nigeria';
        $this->state = $data['state'] ?? '';
        $this->outsideNigeriaLocation = $data['outside_nigeria_location'] ?? '';
        $this->ageGroup = $data['age_group'] ?? '';
        $this->maritalStatus = $data['marital_status'] ?? '';
        $this->phone = $data['phone'] ?? '';
        $this->selectedInterests = $data['selected_interests'] ?? [];
        $this->selectedGoals = $data['selected_goals'] ?? [];
        $this->instagramUsername = $data['instagram_username'] ?? '';
        $this->facebookUsername = $data['facebook_username'] ?? '';
        $this->xUsername = $data['x_username'] ?? '';
        $this->tiktokUsername = $data['tiktok_username'] ?? '';
    }

    protected function getDraftData(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'nickname' => $this->nickname,
            'country' => $this->country,
            'state' => $this->state,
            'outside_nigeria_location' => $this->outsideNigeriaLocation,
            'age_group' => $this->ageGroup,
            'marital_status' => $this->maritalStatus,
            'phone' => $this->phone,
            'selected_interests' => $this->selectedInterests,
            'selected_goals' => $this->selectedGoals,
            'instagram_username' => $this->instagramUsername,
            'facebook_username' => $this->facebookUsername,
            'x_username' => $this->xUsername,
            'tiktok_username' => $this->tiktokUsername,
        ];
    }

    public function toggleInterest(string $slug): void
    {
        if (in_array($slug, $this->selectedInterests, true)) {
            $this->selectedInterests = array_values(
                array_filter($this->selectedInterests, fn ($selected): bool => $selected !== $slug),
            );

            return;
        }

        if (count($this->selectedInterests) >= 5) {
            return;
        }

        $this->selectedInterests[] = $slug;
    }

    public function toggleGoal(string $slug): void
    {
        if (in_array($slug, $this->selectedGoals, true)) {
            $this->selectedGoals = array_values(
                array_filter($this->selectedGoals, fn ($selected): bool => $selected !== $slug),
            );

            return;
        }

        $this->selectedGoals[] = $slug;
    }

    public function nextStep(): void
    {
        $this->validateCurrentStep();
        $this->saveDraft();

        if ($this->step < 6) {
            $this->step++;
        }
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function submit(): void
    {
        $this->validateAllSteps();

        $user = auth()->user();

        DB::transaction(function () use ($user): void {
            $profile = $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => $this->firstName,
                    'last_name' => $this->lastName,
                    'nickname' => $this->nickname,
                    'display_name' => $this->firstName.' '.$this->lastName,
                    'country' => $this->country,
                    'state' => $this->country === 'Nigeria' ? $this->state : null,
                    'outside_nigeria_location' => $this->country !== 'Nigeria' ? $this->outsideNigeriaLocation : null,
                    'age_group' => $this->ageGroup,
                    'marital_status' => $this->maritalStatus,
                    'phone' => $this->phone,
                    'instagram_username' => $this->instagramUsername,
                    'facebook_username' => $this->facebookUsername,
                    'x_username' => $this->xUsername,
                    'tiktok_username' => $this->tiktokUsername,
                    'membership_status' => 'submitted',
                    'application_submitted_at' => now(),
                ],
            );

            $interestIds = Interest::query()
                ->whereIn('slug', $this->selectedInterests)
                ->pluck('id')
                ->all();

            $user->interests()->sync($interestIds);

            $goalIds = Goal::query()
                ->whereIn('slug', $this->selectedGoals)
                ->pluck('id')
                ->all();

            $user->goals()->sync($goalIds);

            $profile->updateQuietly([
                'goals' => Goal::query()->whereIn('slug', $this->selectedGoals)->pluck('slug')->values()->all(),
            ]);

            MembershipApplicationDraft::query()
                ->where('user_id', $user->id)
                ->whereNull('submitted_at')
                ->update(['submitted_at' => now()]);
        });

        $admins = User::role(['super_admin', 'admin', 'moderator'])->get();
        Notification::send($admins, new MembershipApplicationSubmitted($user));

        session()->flash('membership_submitted', true);

        $this->redirectRoute('membership.pending', navigate: true);
    }

    public function getProgressPercentageProperty(): int
    {
        return (int) round(($this->step / 6) * 100);
    }

    protected function saveDraft(): void
    {
        $user = auth()->user();

        MembershipApplicationDraft::query()->updateOrCreate(
            ['user_id' => $user->id, 'submitted_at' => null],
            [
                'current_step' => $this->step,
                'data' => $this->getDraftData(),
            ],
        );
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
                'country' => ['required', 'string'],
                'state' => [Rule::requiredIf(fn () => $this->country === 'Nigeria'), 'nullable', 'string'],
                'outsideNigeriaLocation' => [Rule::requiredIf(fn () => $this->country !== 'Nigeria'), 'nullable', 'string', 'max:500'],
            ],
            3 => [
                'phone' => ['nullable', 'string', 'max:30'],
                'ageGroup' => ['required', 'string'],
                'maritalStatus' => ['required', 'string'],
            ],
            4 => [
                'instagramUsername' => ['nullable', 'string', 'max:255'],
                'facebookUsername' => ['nullable', 'string', 'max:255'],
                'xUsername' => ['nullable', 'string', 'max:255'],
                'tiktokUsername' => ['nullable', 'string', 'max:255'],
            ],
            5 => [
                'selectedInterests' => ['array', 'min:1', 'max:5'],
                'selectedGoals' => ['array', 'min:1'],
            ],
            default => [],
        };

        $this->validate($rules);
    }

    protected function validateAllSteps(): void
    {
        $this->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string'],
            'state' => [Rule::requiredIf(fn () => $this->country === 'Nigeria'), 'nullable', 'string'],
            'outsideNigeriaLocation' => [Rule::requiredIf(fn () => $this->country !== 'Nigeria'), 'nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
            'ageGroup' => ['required', 'string'],
            'maritalStatus' => ['required', 'string'],
            'instagramUsername' => ['nullable', 'string', 'max:255'],
            'facebookUsername' => ['nullable', 'string', 'max:255'],
            'xUsername' => ['nullable', 'string', 'max:255'],
            'tiktokUsername' => ['nullable', 'string', 'max:255'],
            'selectedInterests' => ['array', 'min:1', 'max:5'],
            'selectedGoals' => ['array', 'min:1'],
        ]);
    }

    public function updatedCountry(): void
    {
        if ($this->country !== 'Nigeria') {
            $this->state = '';
        }
    }

    public function render()
    {
        return view('livewire.membership.membership-onboarding-wizard', [
            'interests' => Interest::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'goals' => Goal::query()->where('is_active', true)->orderBy('id')->get(),
        ])->layout('layouts.guest-livewire', [
            'title' => 'Membership Application',
        ]);
    }
}
