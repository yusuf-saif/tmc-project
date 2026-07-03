<?php

namespace App\Livewire\Profile;

use App\Models\Goal;
use App\Models\Interest;
use App\Services\ImageProcessingService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditProfile extends Component
{
    use WithFileUploads;

    public string $displayName = '';

    public $avatar = null;

    public array $selectedInterests = [];

    public array $selectedGoals = [];

    public function mount(): void
    {
        $user = auth()->user()->loadMissing(['memberProfile', 'interests:id', 'goals:id']);

        $this->displayName = (string) ($user->memberProfile?->display_name ?: $user->name);
        $this->selectedInterests = $user->interests->pluck('id')->all();
        $this->selectedGoals = $user->goals->pluck('id')->all();
    }

    public function toggleInterest(int $interestId): void
    {
        if (in_array($interestId, $this->selectedInterests, true)) {
            $this->selectedInterests = array_values(array_diff($this->selectedInterests, [$interestId]));

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

    public function save()
    {
        $this->validate([
            'displayName' => ['required', 'max:255'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $user = auth()->user();
        $profileData = ['display_name' => $this->displayName];

        if ($this->avatar) {
            /* @phpstan-ignore-next-line */
            $profileData['avatar_path'] = app(ImageProcessingService::class)->resizeAndStore($this->avatar, 'avatars', 400);
        }

        $user->memberProfile()->update($profileData);
        $user->update(['name' => $this->displayName]);
        $user->interests()->sync($this->selectedInterests);
        $user->goals()->sync($this->selectedGoals);

        session()->flash('success', 'Profile updated');

        return redirect()->route('profile');
    }

    public function render(): View
    {
        return view('livewire.profile.edit-profile', [
            'interests' => Interest::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'goals' => Goal::query()->where('is_active', true)->orderBy('id')->get(),
        ])->layout('layouts.app', ['title' => 'Edit Profile']);
    }
}
