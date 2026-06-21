<div class="mx-auto flex min-h-screen w-full max-w-2xl items-center justify-center px-6 py-12">
    <section class="w-full max-w-xl rounded bg-white p-10 shadow-[0_4px_24px_rgba(0,0,0,0.06)]">
        <div class="mb-8">
            <div class="mb-3 flex items-center justify-between text-[11px] font-semibold uppercase tracking-[2px] text-gold">
                <span>Step {{ $step }} of 6</span>
                <span>{{ auth()->user()->name }}</span>
            </div>
            <div class="h-2 overflow-hidden rounded-sm bg-ivory">
                <div class="h-full bg-gold transition-all duration-200" style="width: {{ $this->progressPercentage }}%"></div>
            </div>
        </div>

        @if ($step === 1)
        <div class="space-y-5">
            <div>
                <h1 class="font-display text-4xl leading-none text-teal-dk">Personal Info</h1>
                <p class="mt-3 text-sm font-light leading-7 text-ink-soft">Tell us who you are.</p>
            </div>

            <div>
                <label class="tmc-label" for="firstName">First Name *</label>
                <input id="firstName" type="text" wire:model="firstName" class="tmc-input">
                @error('firstName') <p class="tmc-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="tmc-label" for="lastName">Last Name *</label>
                <input id="lastName" type="text" wire:model="lastName" class="tmc-input">
                @error('lastName') <p class="tmc-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="tmc-label" for="nickname">Nickname</label>
                <input id="nickname" type="text" wire:model="nickname" class="tmc-input" placeholder="What do you go by?">
                @error('nickname') <p class="tmc-error">{{ $message }}</p> @enderror
            </div>
        </div>

        @elseif ($step === 2)
        <div class="space-y-5">
            <div>
                <h1 class="font-display text-4xl leading-none text-teal-dk">Location & Demographics</h1>
                <p class="mt-3 text-sm font-light leading-7 text-ink-soft">Where are you based, and what should we know about you?</p>
            </div>

            <div>
                <label class="tmc-label" for="locationCountry">Country *</label>
                <select id="locationCountry" wire:model="locationCountry" class="tmc-input">
                    <option value="Nigeria">Nigeria</option>
                    <option value="Outside Nigeria">Outside Nigeria</option>
                </select>
                @error('locationCountry') <p class="tmc-error">{{ $message }}</p> @enderror
            </div>

            @if ($locationCountry === 'Nigeria')
            <div>
                <label class="tmc-label" for="locationState">State *</label>
                <select id="locationState" wire:model="locationState" class="tmc-input">
                    <option value="">Select state</option>
                    @foreach ($nigerianStates as $state)
                        <option value="{{ $state }}">{{ $state }}</option>
                    @endforeach
                </select>
                @error('locationState') <p class="tmc-error">{{ $message }}</p> @enderror
            </div>
            @else
            <div>
                <label class="tmc-label" for="locationInternational">Country & City *</label>
                <textarea id="locationInternational" wire:model="locationInternational" class="tmc-input" rows="2" placeholder="Tell us your country and city"></textarea>
                @error('locationInternational') <p class="tmc-error">{{ $message }}</p> @enderror
            </div>
            @endif

            <div>
                <label class="tmc-label" for="ageGroup">Age Group *</label>
                <select id="ageGroup" wire:model="ageGroup" class="tmc-input">
                    <option value="">Select age group</option>
                    @foreach ($ageGroups as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('ageGroup') <p class="tmc-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="tmc-label" for="maritalStatus">Marital Status *</label>
                <select id="maritalStatus" wire:model="maritalStatus" class="tmc-input">
                    <option value="">Select marital status</option>
                    @foreach ($maritalStatuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('maritalStatus') <p class="tmc-error">{{ $message }}</p> @enderror
            </div>
        </div>

        @elseif ($step === 3)
        <div class="space-y-5">
            <div>
                <h1 class="font-display text-4xl leading-none text-teal-dk">Contact Details</h1>
                <p class="mt-3 text-sm font-light leading-7 text-ink-soft">How can we reach you?</p>
            </div>

            <div>
                <label class="tmc-label" for="email">Email</label>
                <input id="email" type="email" value="{{ auth()->user()->email }}" class="tmc-input" readonly style="background:#F5F3EE;color:#6B6B6B;cursor:not-allowed;">
            </div>

            <div>
                <label class="tmc-label" for="phone">Phone Number *</label>
                <input id="phone" type="text" wire:model="phone" class="tmc-input" placeholder="+234 800 000 0000">
                @error('phone') <p class="tmc-error">{{ $message }}</p> @enderror
            </div>
        </div>

        @elseif ($step === 4)
        <div class="space-y-5">
            <div>
                <h1 class="font-display text-4xl leading-none text-teal-dk">Social Media</h1>
                <p class="mt-3 text-sm font-light leading-7 text-ink-soft">Share your usernames so we can connect with you.</p>
            </div>

            <div>
                <label class="tmc-label" for="igUsername">Instagram</label>
                <input id="igUsername" type="text" wire:model="igUsername" class="tmc-input" placeholder="@username">
                @error('igUsername') <p class="tmc-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="tmc-label" for="fbUsername">Facebook</label>
                <input id="fbUsername" type="text" wire:model="fbUsername" class="tmc-input" placeholder="username">
                @error('fbUsername') <p class="tmc-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="tmc-label" for="xUsername">X (Twitter)</label>
                <input id="xUsername" type="text" wire:model="xUsername" class="tmc-input" placeholder="@username">
                @error('xUsername') <p class="tmc-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="tmc-label" for="tiktokUsername">TikTok</label>
                <input id="tiktokUsername" type="text" wire:model="tiktokUsername" class="tmc-input" placeholder="@username">
                @error('tiktokUsername') <p class="tmc-error">{{ $message }}</p> @enderror
            </div>
        </div>

        @elseif ($step === 5)
        <div class="space-y-6">
            <div>
                <h1 class="font-display text-4xl leading-none text-teal-dk">Interests & Goals</h1>
                <p class="mt-3 text-sm font-light leading-7 text-ink-soft">Select your interests and goals for your membership journey.</p>
            </div>

            <div>
                <h4 class="text-[11px] font-semibold uppercase tracking-[1.5px] text-gold mb-3">Interests (up to 5)</h4>
                <div class="flex flex-wrap gap-3">
                    @foreach ($interests as $interest)
                        @php($selected = in_array($interest->slug, $selectedInterests, true))
                        <button type="button" wire:click="toggleInterest('{{ $interest->slug }}')" class="px-4 py-3 text-sm font-semibold transition" style="border: 2px solid {{ $selected ? '#1A6B72' : '#E2E8F0' }}; background: {{ $selected ? '#1A6B72' : '#FAF8F3' }}; color: {{ $selected ? '#FFFFFF' : '#3D3A35' }}; border-radius: 999px;">
                            {{ $interest->name }}
                        </button>
                    @endforeach
                </div>
                <div class="mt-2 flex items-center justify-between text-sm text-ink-soft">
                    <p>{{ count($selectedInterests) }}/5 selected</p>
                    @error('selectedInterests') <p class="tmc-error mt-0">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="pt-2">
                <h4 class="text-[11px] font-semibold uppercase tracking-[1.5px] text-gold mb-3">Goals</h4>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach ($goals as $goal)
                        @php($selected = in_array($goal->slug, $selectedGoals, true))
                        <div wire:click="toggleGoal('{{ $goal->slug }}')" class="cursor-pointer rounded-lg p-4 transition" style="border: 2px solid {{ $selected ? '#1A6B72' : '#E2E8F0' }}; background: {{ $selected ? '#D6EDEF' : '#FFFFFF' }}; border-radius: 8px;">
                            <span class="text-[11px] font-semibold uppercase tracking-[1.5px] text-gold">GOAL</span>
                            <h3 class="font-display text-[1.4rem] text-teal-dk">{{ $goal->name }}</h3>
                            <p class="text-sm text-ink-soft">{{ $goal->description ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
                @error('selectedGoals') <p class="tmc-error">{{ $message }}</p> @enderror
            </div>
        </div>

        @elseif ($step === 6)
        <div class="space-y-6">
            <div>
                <h1 class="font-display text-4xl leading-none text-teal-dk">Review & Submit</h1>
                <p class="mt-3 text-sm font-light leading-7 text-ink-soft">Please review your information before submitting.</p>
            </div>

            <div class="space-y-4 text-sm">
                <div class="border-b border-ivory pb-3">
                    <h4 class="text-[11px] font-semibold uppercase tracking-[1.5px] text-gold">Personal Info</h4>
                    <p class="text-ink-md">{{ $firstName }} {{ $lastName }}@if($nickname) ({{ $nickname }})@endif</p>
                </div>

                <div class="border-b border-ivory pb-3">
                    <h4 class="text-[11px] font-semibold uppercase tracking-[1.5px] text-gold">Location</h4>
                    <p class="text-ink-md">{{ $locationCountry === 'Nigeria' ? $locationState.', Nigeria' : $locationInternational }}</p>
                </div>

                <div class="border-b border-ivory pb-3">
                    <h4 class="text-[11px] font-semibold uppercase tracking-[1.5px] text-gold">Demographics</h4>
                    <p class="text-ink-md">{{ $ageGroups[$ageGroup] ?? $ageGroup }} | {{ $maritalStatuses[$maritalStatus] ?? $maritalStatus }}</p>
                </div>

                <div class="border-b border-ivory pb-3">
                    <h4 class="text-[11px] font-semibold uppercase tracking-[1.5px] text-gold">Contact</h4>
                    <p class="text-ink-md">{{ auth()->user()->email }}</p>
                    <p class="text-ink-md">{{ $phone ?: 'Not provided' }}</p>
                </div>

                <div class="border-b border-ivory pb-3">
                    <h4 class="text-[11px] font-semibold uppercase tracking-[1.5px] text-gold">Interests</h4>
                    <p class="text-ink-md">{{ implode(', ', $selectedInterests) }}</p>
                </div>

                <div class="border-b border-ivory pb-3">
                    <h4 class="text-[11px] font-semibold uppercase tracking-[1.5px] text-gold">Goals</h4>
                    <p class="text-ink-md">{{ implode(', ', $selectedGoals) }}</p>
                </div>

                <div>
                    <h4 class="text-[11px] font-semibold uppercase tracking-[1.5px] text-gold">Social Media</h4>
                    <p class="text-ink-md">
                        @if($igUsername || $fbUsername || $xUsername || $tiktokUsername)
                            {{ $igUsername ? "IG: @{$igUsername}" : '' }}{{ $fbUsername && $igUsername ? ' | ' : '' }}{{ $fbUsername ? "FB: {$fbUsername}" : '' }}{{ ($xUsername && ($igUsername || $fbUsername)) ? ' | ' : '' }}{{ $xUsername ? "X: @{$xUsername}" : '' }}{{ ($tiktokUsername && ($igUsername || $fbUsername || $xUsername)) ? ' | ' : '' }}{{ $tiktokUsername ? "TT: @{$tiktokUsername}" : '' }}
                        @else
                            Not provided
                        @endif
                    </p>
                </div>
            </div>
        </div>
        @endif

        <div class="mt-10 flex items-center justify-between gap-4">
            <button type="button" wire:click="previousStep" class="tmc-button-outline max-w-[150px]" @disabled($step === 1)>
                Back
            </button>

            @if ($step < 6)
                <button type="button" wire:click="nextStep" class="tmc-button-gold max-w-[180px]">
                    Continue
                </button>
            @else
                <button type="button" wire:click="submit" class="tmc-button-gold max-w-[180px]" @disabled($submitting)>
                    @if ($submitting)
                        Submitting...
                    @else
                        Submit for Review
                    @endif
                </button>
            @endif
        </div>
    </section>
</div>
