@extends('layouts.auth', ['title' => 'Register'])

@section('content')
    <h1 class="tmc-auth-heading">Join the club</h1>
    <p class="tmc-auth-copy">Create your account to begin a calm, curated member experience.</p>

    @if (request()->filled('ref'))
        <p class="mt-6 rounded-sm bg-gold-pale px-4 py-3 text-sm text-teal-dk">You are joining through a sister's referral invitation.</p>
    @endif

    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
        @csrf

        <input type="hidden" name="ref" value="{{ old('ref', request('ref')) }}">

        <div>
            <label for="name" class="tmc-label">Full Name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" class="tmc-input">
            @error('name') <p class="tmc-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="tmc-label">Email Address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" class="tmc-input">
            @error('email') <p class="tmc-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="tmc-label">Password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" class="tmc-input">
            @error('password') <p class="tmc-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="tmc-label">Confirm Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="tmc-input">
        </div>

        <button type="submit" class="tmc-button-gold" x-data @click="$el.disabled=true;$el.textContent='Creating...'">Create Account</button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-soft">
        Already a member?
        <a href="{{ route('login') }}" class="tmc-link">Sign in</a>
    </p>
@endsection
