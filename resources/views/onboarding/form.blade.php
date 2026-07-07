@extends('layouts.auth', ['title' => 'Complete Your Account'])

@php
$nigerianStates = ['Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno', 'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT (Abuja)', 'Gombe', 'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos', 'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara'];
$ageGroups = ['under_18' => 'Under 18', '18_24' => '18 - 24', '25_34' => '25 - 34', '35_44' => '35 - 44', '45_54' => '45 - 54', '55_above' => '55+'];
$maritalStatuses = ['single' => 'Single', 'married' => 'Married', 'divorced' => 'Divorced', 'widowed' => 'Widowed'];
@endphp

@section('content')
    <h1 class="tmc-auth-heading">Assalamu Alaykum</h1>
    <p class="tmc-auth-copy">Complete your account setup for <strong>{{ $name }}</strong>.</p>

    <div class="mt-6 space-y-1 rounded-sm bg-teal-pale px-4 py-3 text-sm text-teal-dk">
        <p><strong>Membership ID:</strong> {{ $memberId }}</p>
        <p><strong>Name:</strong> {{ $name }}</p>
        <p><strong>Nickname:</strong> {{ $nickname }}</p>
        <p><strong>Email:</strong> {{ $email }}</p>
    </div>

    <form method="POST" action="{{ route('onboarding.complete') }}" class="mt-8 space-y-5" x-data="{ submitting: false, showPassword: false, showPasswordConfirm: false, country: '{{ old('location_country', 'Nigeria') }}' }" @submit="submitting = true">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">
        <input type="hidden" name="member_id" value="{{ $memberId }}">

        <h2 class="text-[11px] font-semibold uppercase tracking-[2px] text-gold">Password</h2>

        <div>
            <label for="password" class="tmc-label">Password *</label>
            <input id="password" name="password" x-bind:type="showPassword ? 'text' : 'password'" required minlength="8" class="tmc-input">
            <button type="button" class="mt-1 text-xs font-semibold uppercase tracking-[1px] text-teal" @click="showPassword = ! showPassword">
                <span x-text="showPassword ? 'Hide' : 'Show'"></span>
            </button>
            @error('password') <p class="tmc-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="tmc-label">Confirm Password *</label>
            <input id="password_confirmation" name="password_confirmation" x-bind:type="showPasswordConfirm ? 'text' : 'password'" required minlength="8" class="tmc-input">
            <button type="button" class="mt-1 text-xs font-semibold uppercase tracking-[1px] text-teal" @click="showPasswordConfirm = ! showPasswordConfirm">
                <span x-text="showPasswordConfirm ? 'Hide' : 'Show'"></span>
            </button>
        </div>

        <hr class="border-ivory">

        <h2 class="text-[11px] font-semibold uppercase tracking-[2px] text-gold">Location & Demographics</h2>

        <div>
            <label for="location_country" class="tmc-label">Country *</label>
            <select id="location_country" name="location_country" x-model="country" class="tmc-input">
                <option value="Nigeria">Nigeria</option>
                <option value="Outside Nigeria" @selected(old('location_country') === 'Outside Nigeria')>Outside Nigeria</option>
            </select>
            @error('location_country') <p class="tmc-error">{{ $message }}</p> @enderror
        </div>

        <template x-if="country === 'Nigeria'">
            <div>
                <label for="location_state" class="tmc-label">State *</label>
                <select id="location_state" name="location_state" class="tmc-input">
                    <option value="">Select state</option>
                    @foreach ($nigerianStates as $state)
                        <option value="{{ $state }}" @selected(old('location_state') === $state)>{{ $state }}</option>
                    @endforeach
                </select>
                @error('location_state') <p class="tmc-error">{{ $message }}</p> @enderror
            </div>
        </template>

        <template x-if="country !== 'Nigeria'">
            <div>
                <label for="location_international" class="tmc-label">Country & City *</label>
                <textarea id="location_international" name="location_international" class="tmc-input" rows="2" placeholder="Tell us your country and city">{{ old('location_international') }}</textarea>
                @error('location_international') <p class="tmc-error">{{ $message }}</p> @enderror
            </div>
        </template>

        <div>
            <label for="age_group" class="tmc-label">Age Group *</label>
            <select id="age_group" name="age_group" class="tmc-input">
                <option value="">Select age group</option>
                @foreach ($ageGroups as $value => $label)
                    <option value="{{ $value }}" @selected(old('age_group') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('age_group') <p class="tmc-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="marital_status" class="tmc-label">Marital Status *</label>
            <select id="marital_status" name="marital_status" class="tmc-input">
                <option value="">Select marital status</option>
                @foreach ($maritalStatuses as $value => $label)
                    <option value="{{ $value }}" @selected(old('marital_status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('marital_status') <p class="tmc-error">{{ $message }}</p> @enderror
        </div>

        <hr class="border-ivory">

        <h2 class="text-[11px] font-semibold uppercase tracking-[2px] text-gold">Contact</h2>

        <div>
            <label for="phone" class="tmc-label">Phone Number *</label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" class="tmc-input" placeholder="+234 800 000 0000">
            @error('phone') <p class="tmc-error">{{ $message }}</p> @enderror
        </div>

        <hr class="border-ivory">

        <h2 class="text-[11px] font-semibold uppercase tracking-[2px] text-gold">Social Media <span class="text-ink-soft normal-case tracking-normal font-normal">(optional)</span></h2>

        <div>
            <label for="ig_username" class="tmc-label">Instagram</label>
            <input id="ig_username" name="ig_username" type="text" value="{{ old('ig_username') }}" class="tmc-input" placeholder="@username">
            @error('ig_username') <p class="tmc-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="fb_username" class="tmc-label">Facebook</label>
            <input id="fb_username" name="fb_username" type="text" value="{{ old('fb_username') }}" class="tmc-input" placeholder="username">
            @error('fb_username') <p class="tmc-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="x_username" class="tmc-label">X (Twitter)</label>
            <input id="x_username" name="x_username" type="text" value="{{ old('x_username') }}" class="tmc-input" placeholder="@username">
            @error('x_username') <p class="tmc-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="tiktok_username" class="tmc-label">TikTok</label>
            <input id="tiktok_username" name="tiktok_username" type="text" value="{{ old('tiktok_username') }}" class="tmc-input" placeholder="@username">
            @error('tiktok_username') <p class="tmc-error">{{ $message }}</p> @enderror
        </div>

        <hr class="border-ivory">

        <h2 class="text-[11px] font-semibold uppercase tracking-[2px] text-gold">Interests <span class="text-ink-soft normal-case tracking-normal font-normal">(up to 5)</span></h2>

        <div class="flex flex-wrap gap-2" x-data="{ selected: [] }">
            @foreach ($interests as $interest)
                <label class="cursor-pointer select-none rounded-full border-2 px-4 py-2 text-xs font-semibold transition"
                       :class="selected.includes('{{ $interest->slug }}') ? 'border-teal bg-teal text-white' : 'border-ivory bg-ivory text-ink-md hover:border-teal'">
                    <input type="checkbox" name="interests[]" value="{{ $interest->slug }}"
                           class="hidden"
                           x-model="selected"
                           @change="if (selected.length > 5) { selected.splice(selected.indexOf('{{ $interest->slug }}'), 1); }">
                    {{ $interest->name }}
                </label>
            @endforeach
        </div>
        @error('interests') <p class="tmc-error">{{ $message }}</p> @enderror

        <hr class="border-ivory">

        <h2 class="text-[11px] font-semibold uppercase tracking-[2px] text-gold">Goals</h2>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2" x-data="{ selectedGoals: {{ json_encode(old('goals', [])) }} }">
            @foreach ($goals as $goal)
                <label class="cursor-pointer rounded-lg border-2 p-3 transition"
                       :class="selectedGoals.includes('{{ $goal->slug }}') ? 'border-teal bg-teal-pale' : 'border-ivory bg-white hover:border-teal'">
                    <input type="checkbox" name="goals[]" value="{{ $goal->slug }}" class="hidden"
                           x-model="selectedGoals">
                    <h3 class="font-display text-lg text-teal-dk">{{ $goal->name }}</h3>
                    @if ($goal->description)
                        <p class="text-xs text-ink-soft">{{ $goal->description }}</p>
                    @endif
                </label>
            @endforeach
        </div>
        @error('goals') <p class="tmc-error">{{ $message }}</p> @enderror

        <button type="submit" class="tmc-button-gold mt-6" :disabled="submitting" x-text="submitting ? 'Setting Up...' : 'Complete My Account'"></button>
    </form>
@endsection
