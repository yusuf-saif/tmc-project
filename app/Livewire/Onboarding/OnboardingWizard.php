<?php

namespace App\Livewire\Onboarding;

use App\Models\Goal;
use App\Models\Interest;
use App\Models\JannahCoinsLedger;
use App\Services\CoinsService;
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

        $user = auth()->user()->loadMissing(['interests:id,slug', 'goals:id,slug', 'profile']);

        $this->selectedInterests = $user->interests->pluck('slug')->all();
        $this->selectedGoals = $user->goals->pluck('slug')->all();
        $this->notificationPreferences = array_replace(
            $this->notificationPreferences,
            $user->profile?->notification_preferences ?? [],
        );
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
        $this->step = max(1, min($this->step, 4));

        if ($this->step === 2 && empty($this->selectedGoals)) {
            $this->addError('goals', 'Please select at least one goal.');

            return;
        }

        $this->validateCurrentStep();

        $this->persistCurrentStep();

        if ($this->step < 4) {
            $this->step++;
        }

        if ($this->step === 4) {
            $this->persistOnboardingCompletion();
        }
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function enterClub()
    {
        return $this->completeOnboarding();
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
            $this->resetErrorBag('goals');
        }
    }

    protected function persistCurrentStep(): void
    {
        $user = auth()->user();

        if ($this->step === 1) {
            $interestIds = Interest::query()
                ->whereIn('slug', $this->selectedInterests)
                ->pluck('id')
                ->all();

            $user->interests()->sync($interestIds);
        }

        if ($this->step === 2) {
            $goalIds = Goal::query()
                ->whereIn('slug', $this->selectedGoals)
                ->pluck('id')
                ->all();

            $user->goals()->sync($goalIds);
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'display_name' => $user->profile?->display_name ?? $user->name,
                    'goals' => Goal::query()->whereIn('slug', $this->selectedGoals)->pluck('slug')->values()->all(),
                ],
            );
        }

        if ($this->step === 3) {
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'display_name' => $user->profile?->display_name ?? $user->name,
                    'notification_preferences' => $this->notificationPreferences,
                ],
            );
        }
    }

    protected function completeOnboarding()
    {
        $this->persistOnboardingCompletion();

        return redirect()->route('home');
    }

    protected function persistOnboardingCompletion(): void
    {
        $user = auth()->user();

        DB::transaction(function () use ($user): void {
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'display_name' => $user->profile?->display_name ?? $user->name,
                    'onboarding_completed_at' => now(),
                ],
            );

            $already = JannahCoinsLedger::query()
                ->where('user_id', $user->id)
                ->where('reason', 'onboarding')
                ->exists();

            if (! $already) {
                CoinsService::award($user, 50, 'onboarding');
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
