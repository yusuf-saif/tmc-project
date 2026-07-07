@php($profile = auth()->user()->profile)

<div class="space-y-6">
    @push('backButton')
        <a href="{{ route('profile') }}" class="back-btn">&larr; Profile</a>
    @endpush
    <h1 class="font-display text-[1.8rem] leading-none text-teal">Edit Profile</h1>

    <section class="space-y-5 rounded-[8px] bg-white p-5" style="border: 1px solid var(--border);">
        <div class="flex items-center gap-4">
            @if ($profile?->avatar_path && Storage::disk('public')->exists($profile->avatar_path))
                <img src="{{ Storage::url($profile->avatar_path) }}" alt="Avatar" class="h-15 w-15 h-[60px] w-[60px] rounded-full object-cover">
            @else
                <div class="flex h-[60px] w-[60px] items-center justify-center rounded-full bg-teal text-white">
                    <span class="font-display text-[1.5rem] leading-none">{{ strtoupper(mb_substr($displayName, 0, 1)) }}</span>
                </div>
            @endif
            <div>
                <label class="tmc-label">Change photo</label>
                <input type="file" wire:model="avatar" accept="image/*" class="block w-full text-sm text-ink-soft">
                @error('avatar') <p class="tmc-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="tmc-label">Display Name</label>
            <input type="text" wire:model="displayName" class="tmc-input">
            @error('displayName') <p class="tmc-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="tmc-label">Email</label>
            <input type="text" value="{{ auth()->user()->email }}" readonly class="tmc-input bg-slate-50 text-ink-soft">
        </div>

        <div class="space-y-3">
            <label class="tmc-label">Interests</label>
            <div class="flex flex-wrap gap-3">
                @foreach ($interests as $interest)
                    @php($selected = in_array($interest->id, $selectedInterests, true))
                    <button type="button" wire:click="toggleInterest({{ $interest->id }})" class="px-4 py-3 text-sm font-semibold transition {{ $selected ? 'bg-teal text-white' : 'border border-slate-200 bg-ivory text-ink-md' }}" style="border-radius: 999px;">
                        {{ $interest->name }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="space-y-3">
            <label class="tmc-label">Goals</label>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ($goals as $goal)
                    @php($selected = in_array($goal->id, $selectedGoals, true))
                    <button type="button" wire:click="toggleGoal({{ $goal->id }})" class="border p-5 text-left transition {{ $selected ? 'border-teal bg-teal-lt' : 'border-slate-200 bg-white' }}" style="border-radius: 2px;">
                        <h2 class="font-display text-3xl text-teal-dk">{{ $goal->name }}</h2>
                    </button>
                @endforeach
            </div>
        </div>

        <button type="button" wire:click="save" class="tmc-button-gold w-full">Save Profile</button>
    </section>
</div>
