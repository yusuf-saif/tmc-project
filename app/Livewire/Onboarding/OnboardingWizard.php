<?php

namespace App\Livewire\Onboarding;

use App\Models\Goal;
use App\Models\Interest;
use App\Models\JannahCoinsLedger;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class OnboardingWizard extends Component
{
    public int $step = 1;

    public array $selectedInterests = [];

    public array $selectedGoals = [];

    public array $notificationPreferences = [
        'events_halaqahs' => true,
        'announcements' => true,
        'coins_rewards' => true,
        'community_updates' => true,
    ];

    public function mount(): void
    {
        $this->step = max(1, min($this->step, 4));

        if (auth()->user()->profile?->onboarding_completed_at) {
            $this->redirectRoute('home', navigate: true);

            return;
        }

        $user = auth()->user()->loadMissing(['interests:id', 'goals:id,slug', 'profile']);

        $this->selectedInterests = $user->interests->pluck('id')->all();
        $this->selectedGoals = $user->goals->pluck('id')->all();
        $this->notificationPreferences = array_replace(
            $this->notificationPreferences,
            $user->profile?->notification_preferences ?? [],
        );
    }

    public function toggleInterest(int $interestId): void
    {
        if (in_array($interestId, $this->selectedInterests, true)) {
            $this->selectedInterests = array_values(array_diff($this->selectedInterests, [$interestId]));

            return;
        }

        if (count($this->selectedInterests) >= 5) {
            return;
        }

        $this->selectedInterests[] = $interestId;
    }

    public function toggleGoal(int $goalId): void
    {
        if (in_array($goalId, $this->selectedGoals, true)) {
            $this->selectedGoals = array_values(array_diff($this->selectedGoals, [$goalId]));

            return;
        }

        $this->selectedGoals[] = $goalId;
    }

    public function nextStep(): void
    {
        $this->step = max(1, min($this->step, 4));

        $this->validateCurrentStep();

        $this->persistCurrentStep();

        if ($this->step < 4) {
            $this->step++;
        }

        if ($this->step === 4) {
            $this->completeOnboarding();
        }
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function enterClub(): void
    {
        $this->completeOnboarding();

        $this->redirectRoute('home', navigate: true);
    }

    public function getProgressPercentageProperty(): int
    {
        return (int) round(($this->step / 4) * 100);
    }

    protected function validateCurrentStep(): void
    {
        if ($this->step === 1) {
            $this->validate([
                'selectedInterests' => ['array', 'min:1', 'max:5'],
            ]);
        }

        if ($this->step === 2) {
            $this->validate([
                'selectedGoals' => ['array', 'min:1'],
            ]);
        }
    }

    protected function persistCurrentStep(): void
    {
        $user = auth()->user();

        if ($this->step === 1) {
            $user->interests()->sync($this->selectedInterests);
        }

        if ($this->step === 2) {
            $user->goals()->sync($this->selectedGoals);
            $user->profile()->update([
                'goals' => Goal::query()->whereIn('id', $this->selectedGoals)->pluck('slug')->values()->all(),
            ]);
        }

        if ($this->step === 3) {
            $user->profile()->update([
                'notification_preferences' => $this->notificationPreferences,
            ]);
        }
    }

    protected function completeOnboarding(): void
    {
        $user = auth()->user();

        DB::transaction(function () use ($user): void {
            $profile = $user->profile()->lockForUpdate()->first();

            if (! $profile->onboarding_completed_at) {
                $profile->update([
                    'onboarding_completed_at' => now(),
                ]);
            }

            if (! JannahCoinsLedger::query()->where('user_id', $user->id)->where('reason', 'onboarding')->exists()) {
                JannahCoinsLedger::query()->create([
                    'user_id' => $user->id,
                    'type' => 'earned',
                    'reason' => 'onboarding',
                    'amount' => 50,
                ]);
            }
        });
    }

    public function render()
    {
        return view('livewire.onboarding.onboarding-wizard', [
            'interests' => Interest::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'goals' => Goal::query()->where('is_active', true)->orderBy('id')->get(),
        ])->layout('layouts.guest-livewire', [
            'title' => 'Onboarding',
        ]);
    }
}
