<?php

namespace App\Livewire\Community;

use App\Models\SupportApplication;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SupportForm extends Component
{
    public string $type;

    public string $name = '';

    public string $email = '';

    public string $motivation = '';

    public string $skillsOrFocus = '';

    public string $availability = '';

    public ?SupportApplication $existing = null;

    public bool $submitted = false;

    public function mount(string $type): void
    {
        abort_unless(in_array($type, ['volunteer', 'mentorship'], true), 404);

        $this->type = $type;
        $user = auth()->user();

        $this->existing = SupportApplication::query()
            ->where('user_id', auth()->id())
            ->where('type', $type)
            ->where('status', 'pending')
            ->first();

        $this->name = (string) ($user->memberProfile?->display_name ?: $user->name);
        $this->email = (string) $user->email;
    }

    public function submit(): void
    {
        if ($this->existing) {
            return;
        }

        $this->validate([
            'name' => ['required', 'max:255'],
            'email' => ['required', 'email'],
            'skillsOrFocus' => ['required'],
            'motivation' => ['required'],
            'availability' => ['nullable', 'max:255'],
        ]);

        SupportApplication::query()->create([
            'user_id' => auth()->id(),
            'type' => $this->type,
            'name' => $this->name,
            'email' => $this->email,
            'motivation' => $this->motivation,
            'skills_or_focus' => $this->skillsOrFocus,
            'availability' => $this->availability ?: null,
            'status' => 'pending',
        ]);

        $this->submitted = true;
        $this->existing = SupportApplication::query()
            ->where('user_id', auth()->id())
            ->where('type', $this->type)
            ->where('status', 'pending')
            ->first();
    }

    public function heading(): string
    {
        return $this->type === 'volunteer' ? 'Volunteer with Us' : 'Mentorship Programme';
    }

    public function skillsLabel(): string
    {
        return $this->type === 'volunteer' ? 'Your Skills & Experience' : 'Your Focus Area';
    }

    public function render(): View
    {
        return view('livewire.community.support-form')
            ->layout('layouts.app', ['title' => $this->heading()]);
    }
}
