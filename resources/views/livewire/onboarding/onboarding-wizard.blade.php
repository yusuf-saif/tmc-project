<div class="mx-auto flex min-h-screen w-full max-w-2xl items-center justify-center px-6 py-12">
    <section class="w-full max-w-xl rounded bg-white p-10 shadow-[0_4px_24px_rgba(0,0,0,0.06)]">
        <div class="mb-8">
            <div class="mb-3 flex items-center justify-between text-[11px] font-semibold uppercase tracking-[2px] text-gold">
                <span>Step {{ $step }} of 4</span>
                <span>{{ auth()->user()->name }}</span>
            </div>
            <div class="h-2 overflow-hidden rounded-sm bg-ivory">
                <div class="h-full bg-gold transition-all duration-200" style="width: {{ $this->progressPercentage }}%"></div>
            </div>
        </div>

        @if ($step === 1)
        <div class="space-y-6">
            <div>
                <h1 class="font-display text-5xl leading-none text-teal-dk">Choose your interests</h1>
                <p class="mt-3 text-sm font-light leading-7 text-ink-soft">Select up to five areas you want your TMC experience to revolve around.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                @foreach ($interests as $interest)
                    @php($selected = in_array($interest->id, $selectedInterests, true))
                    <button type="button" wire:click="toggleInterest({{ $interest->id }})" class="px-4 py-3 text-sm font-semibold transition {{ $selected ? 'bg-teal text-white' : 'border border-slate-200 bg-ivory text-ink-md' }}" style="border-radius: 2px;">
                        {{ $interest->name }}
                    </button>
                @endforeach
            </div>

            <div class="flex items-center justify-between text-sm text-ink-soft">
                <p>{{ count($selectedInterests) }}/5 selected</p>
                @error('selectedInterests') <p class="tmc-error mt-0">{{ $message }}</p> @enderror
            </div>
        </div>
        @elseif ($step === 2)
        <div class="space-y-6">
            <div>
                <h1 class="font-display text-5xl leading-none text-teal-dk">Set your goals</h1>
                <p class="mt-3 text-sm font-light leading-7 text-ink-soft">Tell us what you want this season of membership to support most.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ($goals as $goal)
                    @php($selected = in_array($goal->id, $selectedGoals, true))
                    <button type="button" wire:click="toggleGoal({{ $goal->id }})" class="border p-5 text-left transition {{ $selected ? 'border-teal bg-teal-lt' : 'border-slate-200 bg-white' }}" style="border-radius: 2px;">
                        <p class="text-xs font-semibold uppercase tracking-[1.4px] text-gold">Goal</p>
                        <h2 class="mt-3 font-display text-3xl text-teal-dk">{{ $goal->name }}</h2>
                        <p class="mt-2 text-sm font-light leading-7 text-ink-soft">{{ match($goal->slug) {
                            'community' => 'Build deeper sisterhood and belonging.',
                            'learning' => 'Stay rooted in beneficial reminders and study.',
                            'business' => 'Grow ideas, service, and sustainable income.',
                            'volunteering' => 'Offer time and skills in meaningful ways.',
                        } }}</p>
                    </button>
                @endforeach
            </div>

            @error('selectedGoals') <p class="tmc-error">{{ $message }}</p> @enderror
        </div>
        @elseif ($step === 3)
        <div class="space-y-6">
            <div>
                <h1 class="font-display text-5xl leading-none text-teal-dk">Choose your updates</h1>
                <p class="mt-3 text-sm font-light leading-7 text-ink-soft">Pick the reminders and updates you want us to prioritise.</p>
            </div>

            <div class="space-y-4">
                @foreach (['events_halaqahs' => 'Events & Halaqahs', 'announcements' => 'Announcements', 'coins_rewards' => 'Coins & Rewards', 'community_updates' => 'Community Updates'] as $key => $label)
                    <label class="flex items-center justify-between border border-slate-200 bg-white px-4 py-4" style="border-radius: 2px;">
                        <span class="text-sm text-ink-md">{{ $label }}</span>
                        <button type="button" wire:click="$toggle('notificationPreferences.{{ $key }}')" class="relative h-7 w-12 transition {{ $notificationPreferences[$key] ? 'bg-teal' : 'bg-slate-300' }}" style="border-radius: 2px;">
                            <span class="absolute top-1 h-5 w-5 bg-white transition {{ $notificationPreferences[$key] ? 'left-6' : 'left-1' }}" style="border-radius: 2px;"></span>
                        </button>
                    </label>
                @endforeach
            </div>
        </div>
        @elseif ($step === 4)
        <div class="space-y-8 text-center">
            <img src="{{ asset('images/img2.png') }}" alt="The Muhsinat Club" class="mx-auto w-48 max-w-full object-contain">
            <div>
                <h1 class="font-display text-5xl leading-none text-teal-dk">Welcome to The Muhsinat Club</h1>
                <p class="mt-4 font-display text-3xl text-gold">You've earned 50 Jannah Coins</p>
                <p class="mt-4 text-sm font-light leading-7 text-ink-soft">Your profile is ready. Step inside your member space and begin with calm intention.</p>
            </div>

            <button type="button" wire:click="enterClub" class="tmc-button-gold">Enter the Club →</button>
        </div>
        @endif

        <div class="mt-10 flex items-center justify-between gap-4">
            <button type="button" wire:click="previousStep" class="tmc-button-outline max-w-[150px]" @disabled($step === 1)>
                Back
            </button>

            @if ($step < 4)
                <button type="button" wire:click="nextStep" class="tmc-button-gold max-w-[180px]">
                    Continue
                </button>
            @endif
        </div>
    </section>
</div>
