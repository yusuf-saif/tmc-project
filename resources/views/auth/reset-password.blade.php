@extends('layouts.auth', ['title' => 'Reset Password'])

@section('content')
    <h1 class="tmc-auth-heading">Choose a new password</h1>
    <p class="tmc-auth-copy">Set a new password for your TMC account.</p>

    <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5"
          x-data="{
              password: '',
              confirmation: '',
              get min()   { return this.password.length >= 8; },
              get lower() { return /[a-z]/.test(this.password); },
              get upper() { return /[A-Z]/.test(this.password); },
              get number(){ return /[0-9]/.test(this.password); },
              get symbol(){ return /[^a-zA-Z0-9]/.test(this.password); },
              get allMet(){ return this.min && this.lower && this.upper && this.number && this.symbol; },
              get match() { return this.password && this.password === this.confirmation; }
          }">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="tmc-label">Email Address</label>
            <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autofocus class="tmc-input">
            @error('email') <p class="tmc-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="tmc-label">New Password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                   class="tmc-input" x-model="password">
            @error('password') <p class="tmc-error">{{ $message }}</p> @enderror

            <div x-show="password.length > 0" x-cloak class="pw-checklist">
                <p class="pw-checklist-item" :class="min    ? 'pw-met' : 'pw-unmet'"><span class="pw-icon" x-text="min    ? '✓' : '✗'"></span> At least 8 characters</p>
                <p class="pw-checklist-item" :class="lower  ? 'pw-met' : 'pw-unmet'"><span class="pw-icon" x-text="lower  ? '✓' : '✗'"></span> One lowercase letter</p>
                <p class="pw-checklist-item" :class="upper  ? 'pw-met' : 'pw-unmet'"><span class="pw-icon" x-text="upper  ? '✓' : '✗'"></span> One uppercase letter</p>
                <p class="pw-checklist-item" :class="number ? 'pw-met' : 'pw-unmet'"><span class="pw-icon" x-text="number ? '✓' : '✗'"></span> One number</p>
                <p class="pw-checklist-item" :class="symbol ? 'pw-met' : 'pw-unmet'"><span class="pw-icon" x-text="symbol ? '✓' : '✗'"></span> One symbol</p>
            </div>
        </div>

        <div>
            <label for="password_confirmation" class="tmc-label">Confirm Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                   class="tmc-input" x-model="confirmation">
            <p x-show="confirmation.length > 0" x-cloak
               class="pw-checklist-item" :class="match ? 'pw-met' : 'pw-unmet'"
               style="margin-top:8px;">
                <span class="pw-icon" x-text="match ? '✓' : '✗'"></span>
                <span x-text="match ? 'Passwords match' : 'Passwords do not match'"></span>
            </p>
        </div>

        <button type="submit" class="tmc-button-gold" x-bind:disabled="!allMet || !match"
                x-bind:class="{ 'opacity-50 cursor-not-allowed': !allMet || !match }">
            Reset Password
        </button>
    </form>
@endsection
