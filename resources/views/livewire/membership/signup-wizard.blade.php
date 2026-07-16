<div class="mx-auto flex min-h-screen w-full max-w-2xl items-center justify-center px-6 py-12">
    <section class="w-full max-w-xl rounded bg-white p-10 shadow-[0_4px_24px_rgba(0,0,0,0.06)]">
        <div class="mb-8">
            <div class="mb-3 flex items-center justify-between text-[11px] font-semibold uppercase tracking-[2px] text-gold">
                <span>Step {{ $step }} of 5</span>
            </div>
            <div class="h-2 overflow-hidden rounded-sm bg-ivory">
                <div class="h-full bg-gold transition-all duration-200" style="width: {{ $this->progressPercentage }}%"></div>
            </div>
        </div>

        @if ($errors->any() && $step === 5)
            <div class="mb-6 rounded-sm bg-red-50 p-4 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if ($step === 1)
        <div class="space-y-5">
            <div>
                <h1 class="font-display text-4xl leading-none text-teal-dk">Create Account</h1>
                <p class="mt-3 text-sm font-light leading-7 text-ink-soft">Set up your login credentials.</p>
            </div>

            <div>
                <label class="tmc-label" for="referralCode">Referral Code <span class="text-ink-soft">(optional)</span></label>
                <input id="referralCode" type="text" wire:model.blur="referralCode" class="tmc-input" placeholder="Enter code" maxlength="8">
                @error('referralCode') <p class="tmc-error">{{ $message }}</p> @enderror
                @if ($referralCode && ! $errors->has('referralCode'))
                    <p class="mt-1 text-xs text-teal">Referral invitation applied</p>
                @endif
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
                <label class="tmc-label" for="email">Email Address *</label>
                <input id="email" type="email" wire:model="email" class="tmc-input" autocomplete="email">
                @error('email') <p class="tmc-error">{{ $message }}</p> @enderror

                @if ($existingMemberDetected && $step === 1)
                    <div class="mt-3 rounded-sm bg-teal-lt p-4 text-sm text-teal-dk">
                        <p style="margin:0;">{{ $existingMemberMessage }}</p>
                        @error('existingMember') <p class="tmc-error mt-1">{{ $message }}</p> @enderror
                        @if ($showResendButton)
                            <button type="button" wire:click="resendInvitation" class="tmc-button-gold mt-3" style="font-size:11px;padding:7px 16px;min-height:auto;">
                                Resend Invitation
                            </button>
                        @else
                            <a href="{{ route('login') }}" class="tmc-link mt-2 inline-block">Go to Login</a>
                            <a href="{{ route('password.request') }}" class="tmc-link mt-2 inline-block ml-3">Forgot Password?</a>
                        @endif
                        <button type="button" wire:click="clearExistingMember" class="tmc-link mt-2 inline-block ml-3" style="font-size:11px;">Dismiss</button>
                    </div>
                @endif
            </div>

            <div>
                <label class="tmc-label" for="password">Password *</label>
                <input id="password" type="password" wire:model="password" class="tmc-input" autocomplete="new-password">
                @error('password') <p class="tmc-error">{{ $message }}</p> @enderror

                <div x-show="$wire.password && $wire.password.length > 0" x-cloak class="pw-checklist">
                    <p class="pw-checklist-item" :class="$wire.password.length >= 8                   ? 'pw-met' : 'pw-unmet'"><span class="pw-icon" x-text="$wire.password.length >= 8                   ? '✓' : '✗'"></span> At least 8 characters</p>
                    <p class="pw-checklist-item" :class="/[a-z]/.test($wire.password)                    ? 'pw-met' : 'pw-unmet'"><span class="pw-icon" x-text="/[a-z]/.test($wire.password)                    ? '✓' : '✗'"></span> One lowercase letter</p>
                    <p class="pw-checklist-item" :class="/[A-Z]/.test($wire.password)                    ? 'pw-met' : 'pw-unmet'"><span class="pw-icon" x-text="/[A-Z]/.test($wire.password)                    ? '✓' : '✗'"></span> One uppercase letter</p>
                    <p class="pw-checklist-item" :class="/[0-9]/.test($wire.password)                    ? 'pw-met' : 'pw-unmet'"><span class="pw-icon" x-text="/[0-9]/.test($wire.password)                    ? '✓' : '✗'"></span> One number</p>
                    <p class="pw-checklist-item" :class="/[^a-zA-Z0-9]/.test($wire.password)             ? 'pw-met' : 'pw-unmet'"><span class="pw-icon" x-text="/[^a-zA-Z0-9]/.test($wire.password)             ? '✓' : '✗'"></span> One symbol</p>
                </div>
            </div>

            <div>
                <label class="tmc-label" for="passwordConfirmation">Confirm Password *</label>
                <input id="passwordConfirmation" type="password" wire:model="passwordConfirmation" class="tmc-input" autocomplete="new-password">
                @error('passwordConfirmation') <p class="tmc-error">{{ $message }}</p> @enderror

                <p x-show="$wire.passwordConfirmation && $wire.passwordConfirmation.length > 0" x-cloak
                   class="pw-checklist-item" style="margin-top:8px;"
                   :class="$wire.password === $wire.passwordConfirmation ? 'pw-met' : 'pw-unmet'">
                    <span class="pw-icon" x-text="$wire.password === $wire.passwordConfirmation ? '✓' : '✗'"></span>
                    <span x-text="$wire.password === $wire.passwordConfirmation ? 'Passwords match' : 'Passwords do not match'"></span>
                </p>
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
                <h1 class="font-display text-4xl leading-none text-teal-dk">Review & Submit</h1>
                <p class="mt-3 text-sm font-light leading-7 text-ink-soft">Please review your information before submitting.</p>
            </div>

            <div class="space-y-4 text-sm">
                <div class="border-b border-ivory pb-3">
                    <h4 class="text-[11px] font-semibold uppercase tracking-[1.5px] text-gold">Account</h4>
                    <p class="text-ink-md">{{ $email }}</p>
                </div>

                <div class="border-b border-ivory pb-3">
                    <h4 class="text-[11px] font-semibold uppercase tracking-[1.5px] text-gold">Personal Info</h4>
                    <p class="text-ink-md">{{ $firstName }} {{ $lastName }}</p>
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
                    <p class="text-ink-md">{{ $phone ?: 'Not provided' }}</p>
                </div>

                @if ($referralCode)
                <div class="border-b border-ivory pb-3">
                    <h4 class="text-[11px] font-semibold uppercase tracking-[1.5px] text-gold">Referral</h4>
                    <p class="text-ink-md">Code: {{ $referralCode }}</p>
                </div>
                @endif

                <div class="border-b border-ivory pb-3">
                    <h4 class="text-[11px] font-semibold uppercase tracking-[1.5px] text-gold">Social Media</h4>
                    <p class="text-ink-md">
                        @if($igUsername || $fbUsername || $xUsername || $tiktokUsername)
                            {{ $igUsername ? "IG: @{$igUsername}" : '' }}{{ $fbUsername && $igUsername ? ' | ' : '' }}{{ $fbUsername ? "FB: {$fbUsername}" : '' }}{{ ($xUsername && ($igUsername || $fbUsername)) ? ' | ' : '' }}{{ $xUsername ? "X: @{$xUsername}" : '' }}{{ ($tiktokUsername && ($igUsername || $fbUsername || $xUsername)) ? ' | ' : '' }}{{ $tiktokUsername ? "TT: @{$tiktokUsername}" : '' }}
                        @else
                            Not provided
                        @endif
                    </p>
                </div>

                <div>
                    <h4 class="text-[11px] font-semibold uppercase tracking-[1.5px] text-gold">Interests (up to 5)</h4>
                    <div class="flex flex-wrap gap-2 mt-2">
                        @foreach ($interests as $interest)
                            @php($selected = in_array($interest->slug, $selectedInterests, true))
                            <button type="button" wire:click="toggleInterest('{{ $interest->slug }}')" class="px-3 py-2 text-xs font-semibold rounded-full transition" style="border: 2px solid {{ $selected ? '#1A6B72' : '#E2E8F0' }}; background: {{ $selected ? '#1A6B72' : '#FAF8F3' }}; color: {{ $selected ? '#FFFFFF' : '#3D3A35' }};">
                                {{ $interest->name }}
                            </button>
                        @endforeach
                    </div>
                    <div class="mt-1 flex items-center justify-between text-xs text-ink-soft">
                        <p>{{ count($selectedInterests) }}/5 selected</p>
                        @error('selectedInterests') <p class="tmc-error mt-0">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <h4 class="text-[11px] font-semibold uppercase tracking-[1.5px] text-gold">Goals</h4>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 mt-2">
                        @foreach ($goals as $goal)
                            @php($selected = in_array($goal->slug, $selectedGoals, true))
                            <div wire:click="toggleGoal('{{ $goal->slug }}')" class="cursor-pointer rounded-lg p-3 transition" style="border: 2px solid {{ $selected ? '#1A6B72' : '#E2E8F0' }}; background: {{ $selected ? '#D6EDEF' : '#FFFFFF' }};">
                                <h3 class="font-display text-lg text-teal-dk">{{ $goal->name }}</h3>
                                <p class="text-xs text-ink-soft">{{ $goal->description ?? '' }}</p>
                            </div>
                        @endforeach
                    </div>
                    @error('selectedGoals') <p class="tmc-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-4 rounded-sm bg-ivory p-4 text-sm text-ink-soft">
                <p>By submitting, you agree to our membership terms.</p>
            </div>
        </div>
        @endif

        <div class="mt-10 flex items-center justify-between gap-4">
            <button type="button" wire:click="previousStep" class="tmc-button-outline max-w-[150px]" @disabled($step === 1)>
                Back
            </button>

            @if ($step < 5)
                <button type="button" wire:click="nextStep" class="tmc-button-gold max-w-[180px]" wire:loading.attr="disabled" wire:target="nextStep">
                    <span wire:loading.remove wire:target="nextStep">Continue</span>
                    <span wire:loading wire:target="nextStep">Loading...</span>
                </button>
            @else
                <button type="button" wire:click="submit" class="tmc-button-gold max-w-[180px]" wire:loading.attr="disabled" wire:target="submit">
                    <span wire:loading.remove wire:target="submit">Submit Application</span>
                    <span wire:loading wire:target="submit">Submitting...</span>
                </button>
            @endif
        </div>
    </section>
</div>
