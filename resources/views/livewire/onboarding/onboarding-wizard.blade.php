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
                    @php($selected = in_array($interest->slug, $selectedInterests, true))
                    <button type="button" wire:click="toggleInterest('{{ $interest->slug }}')" class="px-4 py-3 text-sm font-semibold transition text-ink-md" style="border: 2px solid {{ $selected ? '#1A6B72' : '#E2E8F0' }}; background: {{ $selected ? '#1A6B72' : '#FAF8F3' }}; color: {{ $selected ? '#FFFFFF' : '#3D3A35' }}; border-radius: 999px;">
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
                    @php($selected = in_array($goal->slug, $selectedGoals, true))
                    <div wire:click="toggleGoal('{{ $goal->slug }}')" class="cursor-pointer rounded-lg p-4 transition" style="cursor: pointer; border: 2px solid {{ $selected ? '#1A6B72' : '#E2E8F0' }}; background: {{ $selected ? '#D6EDEF' : '#FFFFFF' }}; border-radius: 8px; padding: 1rem; transition: all 0.2s;">
                        <span style="font-size: 11px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; color: #C8A84B;">GOAL</span>
                        <h3 style="font-family: 'Dancing Script', cursive; font-size: 1.4rem; color: #0D3F44; margin: 4px 0;">{{ $goal->name }}</h3>
                        <p style="font-size: 0.875rem; color: #6B6760; line-height: 1.6; margin: 0;">{{ match($goal->slug) {
                            'community' => 'Build deeper sisterhood and belonging.',
                            'learning' => 'Stay rooted in beneficial reminders and study.',
                            'business' => 'Grow ideas, service, and sustainable income.',
                            'volunteering' => 'Offer time and skills in meaningful ways.',
                        } }}</p>
                    </div>
                @endforeach
            </div>

            @error('selectedGoals') <p class="tmc-error">{{ $message }}</p> @enderror
            @error('goals') <p class="tmc-error">{{ $message }}</p> @enderror
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
